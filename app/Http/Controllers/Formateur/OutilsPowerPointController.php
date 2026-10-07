<?php

namespace App\Http\Controllers\Formateur;

use App\Domains\ModulesFormateur\Support\AccesModule;
use App\Http\Controllers\Controller;
use App\Jobs\ConvertLectureSlides;
use App\Models\Category;
use App\Models\Module;
use App\Models\ModuleLecture;
use App\Models\ModuleSection;
use App\Support\Outils\EtatsOutils;
use App\Support\Slides\SlideConversionEnvironment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OutilsPowerPointController extends Controller
{
    public function __construct(private readonly AccesModule $accesModule)
    {
        abort_unless(EtatsOutils::actif('powerpoint_module'), 404);
    }

    public function index(SlideConversionEnvironment $environment): View
    {
        $categories = Category::query()
            ->with(['subcategories' => fn ($query) => $query->orderBy('subcategory_name')])
            ->orderBy('category_name')
            ->get();

        $presentations = Module::query()
            ->authoredByTrainer((int) auth()->id())
            ->whereHas('lectures', fn ($query) => $query->where('content_type', 'slides'))
            ->with([
                'category:id,category_name',
                'subCategory:id,subcategory_name',
                'lectures' => fn ($query) => $query
                    ->where('content_type', 'slides')
                    ->latest('id'),
            ])
            ->latest()
            ->paginate(12);

        return view('formateur.outils.powerpoint_index', [
            'categories' => $categories,
            'categoryPayload' => $categories->map(fn (Category $category): array => [
                'id' => (string) $category->id,
                'label' => (string) $category->category_name,
                'subcategories' => $category->subcategories->map(fn ($subcategory): array => [
                    'id' => (string) $subcategory->id,
                    'label' => (string) $subcategory->subcategory_name,
                ])->values()->all(),
            ])->values()->all(),
            'presentations' => $presentations,
            'conversionEnvironment' => $environment->status(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $categoryId = (int) $request->input('category_id');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'subcategory_id' => [
                'required',
                'integer',
                Rule::exists('subcategories', 'id')->where('category_id', $categoryId),
            ],
            'section_title' => ['nullable', 'string', 'max:255'],
            'lecture_title' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'slides_file' => ['required', 'file', 'mimes:ppt,pptx,pdf', 'max:51200'],
        ]);

        $title = trim((string) $data['title']);
        $file = $request->file('slides_file');
        $storedPath = null;

        try {
            $module = DB::transaction(function () use ($data, $file, $title, &$storedPath): Module {
                $module = Module::query()->create([
                    'category_id' => (int) $data['category_id'],
                    'subcategory_id' => (int) $data['subcategory_id'],
                    'formateur_id' => (int) auth()->id(),
                    'created_by' => (int) auth()->id(),
                    'is_trainer_authored' => true,
                    'module_title' => $title,
                    'module_name' => $title,
                    'module_name_slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
                    'description' => trim((string) ($data['description'] ?? '')) ?: null,
                    'certificat' => false,
                    'status' => false,
                ]);

                $section = ModuleSection::query()->create([
                    'module_id' => $module->id,
                    'section_title' => trim((string) ($data['section_title'] ?? '')) ?: 'Présentation',
                ]);

                $lecture = ModuleLecture::query()->create([
                    'module_id' => $module->id,
                    'section_id' => $section->id,
                    'lecture_title' => trim((string) ($data['lecture_title'] ?? '')) ?: $title,
                    'position' => 1,
                    'content_type' => 'slides',
                    'duration' => isset($data['duration']) ? (int) $data['duration'] : null,
                    'slide_count' => 0,
                    'slides_status' => 'pending',
                    'slides_path' => null,
                    'slides_source_path' => null,
                    'slides_error' => null,
                    'slides_converted_at' => null,
                    'quiz_enabled' => false,
                    'quiz_questions_per_attempt' => 0,
                ]);

                $storedPath = $file->storeAs(
                    'slides/sources/lecture_'.$lecture->id,
                    'lecture_'.$lecture->id.'_'.Str::uuid().'.'.Str::lower($file->getClientOriginalExtension()),
                    'local'
                );

                $lecture->update(['slides_source_path' => $storedPath]);

                return $module;
            });
        } catch (\Throwable $exception) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }

        $lecture = $this->slideLecture($module);
        ConvertLectureSlides::dispatch($lecture->id, (string) $lecture->slides_source_path)->afterResponse();

        return redirect()
            ->route('formateur.outils.powerpoint.show', $module)
            ->with('success', 'Le module a été créé. La conversion des diapositives est en cours.');
    }

    public function show(Module $module): View
    {
        $this->assertOwnership($module);
        $module->load(['category:id,category_name', 'subCategory:id,subcategory_name']);
        $lecture = $this->slideLecture($module);

        return view('formateur.outils.powerpoint_show', [
            'module' => $module,
            'lecture' => $lecture,
            'slides' => $this->slideUrls($lecture),
        ]);
    }

    public function status(Module $module): JsonResponse
    {
        $this->assertOwnership($module);
        $lecture = $this->slideLecture($module);

        return response()->json([
            'status' => (string) $lecture->slides_status,
            'slide_count' => (int) $lecture->slide_count,
            'error' => $lecture->slides_error,
            'published' => (bool) $module->status,
            'ready' => $lecture->slides_status === 'ready',
        ]);
    }

    public function retry(Module $module): RedirectResponse
    {
        $this->assertOwnership($module);
        $lecture = $this->slideLecture($module);
        $sourcePath = (string) ($lecture->slides_source_path ?? '');

        if ($sourcePath === '' || ! Storage::disk('local')->exists($sourcePath)) {
            return back()->with('error', 'Le fichier source est introuvable. Créez un nouveau module avec le fichier PowerPoint.');
        }

        $lecture->update([
            'slides_status' => 'pending',
            'slides_error' => null,
        ]);

        ConvertLectureSlides::dispatch($lecture->id, $sourcePath)->afterResponse();

        return back()->with('success', 'La conversion a été relancée.');
    }

    public function publish(Request $request, Module $module): RedirectResponse
    {
        $this->assertOwnership($module);
        $lecture = $this->slideLecture($module);

        if ($lecture->slides_status !== 'ready') {
            return back()->with('error', 'Attendez la fin de la conversion avant de publier ce module.');
        }

        $data = $request->validate([
            'published' => ['required', 'boolean'],
        ]);

        $module->update(['status' => (bool) $data['published']]);

        return back()->with(
            'success',
            $module->status ? 'Le module est maintenant publié.' : 'Le module est revenu en brouillon.'
        );
    }

    private function assertOwnership(Module $module): void
    {
        $this->accesModule->assertOwner($module);
    }

    private function slideLecture(Module $module): ModuleLecture
    {
        return $module->lectures()
            ->where('content_type', 'slides')
            ->latest('id')
            ->firstOrFail();
    }

    /**
     * @return array<int, string>
     */
    private function slideUrls(ModuleLecture $lecture): array
    {
        if ($lecture->slides_status !== 'ready' || blank($lecture->slides_path)) {
            return [];
        }

        return collect(Storage::disk('public')->files($lecture->slides_path))
            ->filter(fn (string $file): bool => (bool) preg_match('/^slide[-_]\d+\.jpg$/i', basename($file)))
            ->sortBy(function (string $file): int {
                preg_match('/(\d+)\.jpg$/i', basename($file), $matches);

                return isset($matches[1]) ? (int) $matches[1] : PHP_INT_MAX;
            })
            ->values()
            ->map(fn (string $file): string => route('media.storage', ['path' => $file], false))
            ->all();
    }
}
