<?php

// /home/laurents/Oneduc_Dev/app/Http/Controllers/Backend/ModuleController.php

namespace App\Http\Controllers\Backend;

use App\Domains\CatalogueFormations\Actions\PublierVersionFormationCatalogue;
use App\Http\Controllers\Concerns\InteractsWithLectureProgressStats;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Evaluation;
use App\Models\Group;
use App\Models\Module;
use App\Models\ModuleLecture;
use App\Models\ModuleSection;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptQuestion;
use App\Models\ScormInteraction;
use App\Models\ScormScore;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\VideoSegmentTracking;
use App\Models\WordCloud;
use App\Services\ModuleCompletionNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    use InteractsWithLectureProgressStats;

    public function __construct(
        private readonly PublierVersionFormationCatalogue $publierVersionFormationCatalogue,
    ) {}

    private function viewBase(): string
    {
        $name = optional(request()->route())->getName();

        if (is_string($name) && str_starts_with($name, 'formateur.')) {
            return 'formateur.formations';
        }

        if (is_string($name) && str_starts_with($name, 'observateur.')) {
            return 'observateur.formations';
        }

        return 'stagiaire.formations';
    }

    public function Modules()
    {
        $modules = Module::query()
            ->with([
                'formateur:id,name',
                'category:id,category_name',
            ])
            ->withCount(['sections', 'lectures'])
            ->withSum('lectures as quiz_questions_planned', 'quiz_questions_per_attempt')
            ->latest('id')
            ->get();

        return view('admin.backend.modules.modules', compact('modules'));
    }

    public function toggleStatus(Module $module)
    {
        if ($module->is_trainer_authored) {
            return back()->with('error', "Une création personnelle de formateur ne peut pas être modifiée depuis l'administration.");
        }

        if ($module->estArchivee()) {
            return back()->with('error', 'Une version archivée ne peut pas être réactivée. Créez une nouvelle version brouillon.');
        }

        if ($module->estPubliee()) {
            $module->forceFill([
                'publication_state' => Module::PUBLICATION_ARCHIVED,
                // Les groupes existants doivent conserver leur version.
                'status' => $module->groups()->exists(),
            ])->save();

            return back()->with('success', 'Module archivé.');
        }

        $this->publierVersionFormationCatalogue->execute($module);

        return back()->with('success', 'Module publié.');
    }

    public function AddModule()
    {
        $categories = Category::orderBy('category_name', 'asc')->get();
        $subcategories = SubCategory::orderBy('subcategory_name', 'asc')->get();
        $formateurs = User::where('role', 'formateur')->orderBy('name')->get();
        $evaluations = Evaluation::orderBy('titre')->get();

        return view('admin.backend.modules.add_module', compact('categories', 'subcategories', 'formateurs', 'evaluations'));
    }

    public function StoreModule(Request $request)
    {
        $request->validate([
            'module_name' => 'required|string|max:255',
            'module_title' => 'required|string|max:255',
            'formateur_id' => 'nullable|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:subcategories,id',
            'certificat' => 'required|in:1,0',
            'label' => 'nullable|string|max:255',
            'duree' => 'nullable|string|max:100',
            'estimated_question_seconds' => 'nullable|integer|min:1|max:600',
            'resources' => 'nullable|string|max:255',
            'prerequi' => 'nullable|string',
            'module_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'header_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'module_video' => 'nullable|string|max:255',
            'module_video_file' => 'nullable|file|mimes:mp4,m4v,mov,avi,webm|max:307200',
            'evaluation_id' => 'nullable|exists:evaluations,id',
        ]);

        $moduleVideo = trim((string) $request->input('module_video', ''));
        $moduleVideo = $moduleVideo === '' ? null : $moduleVideo;

        $imagePath = null;
        if ($request->hasFile('module_image')) {
            $image = $request->file('module_image');
            $imageName = time().'_'.Str::slug($request->module_name).'.'.$image->getClientOriginalExtension();
            $image->storeAs('uploads/modules/images', $imageName, 'public');
            $imagePath = 'uploads/modules/images/'.$imageName;
        }

        $headerImagePath = null;
        if ($request->hasFile('header_image')) {
            $headerImage = $request->file('header_image');
            $headerImageName = time().'_header_'.Str::slug($request->module_name).'.'.$headerImage->getClientOriginalExtension();
            $headerImage->storeAs('uploads/modules/headers', $headerImageName, 'public');
            $headerImagePath = 'uploads/modules/headers/'.$headerImageName;
        }

        $module = Module::create([
            'category_id' => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'formateur_id' => $request->formateur_id,
            'created_by' => auth()->id(),
            'is_trainer_authored' => false,
            'module_name' => $request->module_name,
            'module_name_slug' => Str::slug($request->module_name),
            'module_title' => $request->module_title,
            'description' => $request->description,
            'module_image' => $imagePath,
            'header_image' => $headerImagePath,
            'module_video' => $moduleVideo,
            'label' => $request->label,
            'duree' => $request->duree,
            'estimated_question_seconds' => (int) ($request->input('estimated_question_seconds', 30) ?: 30),
            'resources' => $request->resources,
            'certificat' => $request->certificat,
            'prerequi' => $request->prerequi,
            'bestseller' => $request->has('bestseller') ? 1 : 0,
            'vedette' => $request->has('vedette') ? 1 : 0,
            'surevalue' => $request->has('surevalue') ? 1 : 0,
            'status' => false,
            'publication_state' => Module::PUBLICATION_DRAFT,
            'published_at' => null,
            'evaluation_id' => $request->evaluation_id,
        ]);

        if ($request->hasFile('module_video_file')) {
            $module->update([
                'module_video' => $this->storeModuleVideo($module, $request->file('module_video_file')),
            ]);
        }

        return redirect()->route('admin.modules')->with('success', 'Brouillon de module ajouté avec succès !');
    }

    public function EditModule($id)
    {
        $module = Module::findOrFail($id);
        $categories = Category::orderBy('category_name', 'asc')->get();
        $subcategories = SubCategory::orderBy('subcategory_name', 'asc')->get();
        $formateurs = User::where('role', 'formateur')->orderBy('name')->get();
        $evaluations = Evaluation::orderBy('titre')->get();

        return view('admin.backend.modules.edit_module', compact('module', 'categories', 'subcategories', 'formateurs', 'evaluations'));
    }

    public function UpdateModule(Request $request, $id)
    {
        $module = Module::findOrFail($id);

        if ($module->is_trainer_authored) {
            return back()->with('error', "Une création personnelle de formateur ne peut pas être modifiée depuis l'administration.");
        }

        if (! $module->estBrouillonCatalogue()) {
            return back()->with('error', 'Une formation publiée ou archivée est immuable. Créez une nouvelle version brouillon.');
        }

        $request->validate([
            'module_name' => 'required|string|max:255',
            'module_title' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'subcategory_id' => 'required|integer|exists:subcategories,id',
            'certificat' => 'required|in:1,0',
            'module_video' => 'nullable|string|max:255',
            'module_video_file' => 'nullable|file|mimes:mp4,m4v,mov,avi,webm|max:307200',
            'evaluation_id' => 'nullable|exists:evaluations,id',
            'formateur_id' => 'nullable|exists:users,id',
            'module_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'header_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'remove_module_image' => 'nullable|boolean',
            'remove_header_image' => 'nullable|boolean',
            'estimated_question_seconds' => 'nullable|integer|min:1|max:600',
        ]);

        $moduleVideo = $module->module_video;

        if ($request->has('module_video')) {
            $moduleVideo = trim((string) $request->input('module_video', ''));
            $moduleVideo = $moduleVideo === '' ? null : $moduleVideo;
        }

        $imagePath = $module->module_image;
        if ($request->hasFile('module_image')) {
            if ($module->module_image) {
                Storage::disk('public')->delete($module->module_image);
            }
            $image = $request->file('module_image');
            $imageName = time().'_'.Str::slug($request->module_name).'.'.$image->getClientOriginalExtension();
            $image->storeAs('uploads/modules/images', $imageName, 'public');
            $imagePath = 'uploads/modules/images/'.$imageName;
        } elseif ($request->boolean('remove_module_image') && $module->module_image) {
            Storage::disk('public')->delete($module->module_image);
            $imagePath = null;
        }

        $headerImagePath = $module->header_image;
        if ($request->hasFile('header_image')) {
            if ($module->header_image) {
                Storage::disk('public')->delete($module->header_image);
            }
            $headerImage = $request->file('header_image');
            $headerImageName = time().'_header_'.Str::slug($request->module_name).'.'.$headerImage->getClientOriginalExtension();
            $headerImage->storeAs('uploads/modules/headers', $headerImageName, 'public');
            $headerImagePath = 'uploads/modules/headers/'.$headerImageName;
        } elseif ($request->boolean('remove_header_image') && $module->header_image) {
            Storage::disk('public')->delete($module->header_image);
            $headerImagePath = null;
        }

        if ($request->hasFile('module_video_file')) {
            $moduleVideo = $this->storeModuleVideo($module, $request->file('module_video_file'));
        } elseif ($module->module_video !== $moduleVideo) {
            $this->deleteManagedModuleVideo($module->module_video, $module->id);
        }

        $publier = $request->has('status');

        $module->update([
            'category_id' => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'formateur_id' => $request->formateur_id,
            'module_name' => $request->module_name,
            'module_name_slug' => Str::slug($request->module_name),
            'module_title' => $request->module_title,
            'description' => $request->description,
            'module_image' => $imagePath,
            'header_image' => $headerImagePath,
            'module_video' => $moduleVideo,
            'label' => $request->label,
            'duree' => $request->duree,
            'estimated_question_seconds' => (int) ($request->input('estimated_question_seconds', 30) ?: 30),
            'resources' => $request->resources,
            'certificat' => $request->certificat,
            'prerequi' => $request->prerequi,
            'bestseller' => $request->has('bestseller') ? 1 : 0,
            'vedette' => $request->has('vedette') ? 1 : 0,
            'surevalue' => $request->has('surevalue') ? 1 : 0,
            'evaluation_id' => $request->evaluation_id,
        ]);

        if ($publier) {
            $this->publierVersionFormationCatalogue->execute($module->fresh());
        }

        return redirect()->route('admin.modules')->with(
            'success',
            $publier ? 'Module mis à jour et publié !' : 'Brouillon de module mis à jour !',
        );
    }

    public function DeleteModule($id)
    {
        $module = Module::findOrFail($id);

        if ($module->is_trainer_authored || ! $module->estBrouillonCatalogue()) {
            return back()->with('error', 'Seul un brouillon du catalogue peut être supprimé.');
        }

        if ($module->module_image) {
            Storage::disk('public')->delete($module->module_image);
        }
        if ($module->header_image) {
            Storage::disk('public')->delete($module->header_image);
        }
        $this->deleteManagedModuleVideo($module->module_video, $module->id);

        $module->delete();

        return redirect()->route('admin.modules')->with('success', 'Module supprimé avec succès !');
    }

    public function show($id)
    {
        $module = Module::with('sections.lectures.objectives')->findOrFail($id);

        if (! $module->isVisibleTo(auth()->user())) {
            abort(404);
        }

        $lessonObjectives = $module->sections
            ->flatMap->lectures
            ->flatMap(function ($lecture) {
                return $lecture->objectives->pluck('title');
            })
            ->map(fn ($title) => trim((string) $title))
            ->filter()
            ->unique()
            ->values();

        return view('frontend.contenu.module_detail', compact('module', 'lessonObjectives'));
    }

    public function section(Request $request, Module $module, ModuleSection $section)
    {
        $user = auth()->user();
        $isFormateurRoute = $request->routeIs('formateur.*');

        abort_unless((int) $section->module_id === (int) $module->id, 404);
        abort_unless($module->isVisibleTo($user), 404);

        $module->load([
            'sections' => function ($q) {
                $q->orderBy('id')
                    ->with(['lectures' => function ($qq) {
                        $qq->orderBy('position')
                            ->orderBy('id')
                            ->with(['objectives' => function ($oq) {
                                $oq->orderBy('position')->orderBy('id');
                            }]);
                    }]);
            },
        ]);

        $mode = (string) $request->query('mode', 'groupe');
        $isStaff = in_array($user->role ?? null, ['formateur', 'admin', 'observateur'], true);
        $includeHidden = $request->boolean('include_hidden') && $isStaff;
        $anonymous = $request->boolean('anonymous') || ($user->role ?? null) === 'observateur';

        $groupId = $this->resolveGroupIdForContext($request, $user, (int) $module->id);

        if ($mode !== 'officiel') {
            $this->applyGroupLessonOverrides($module, $groupId, ! $includeHidden);
        }

        $section = $module->sections->firstWhere('id', (int) $section->id);
        abort_unless($section, 404);

        $lectures = $module->sections->flatMap(fn ($s) => $s->lectures)->values();

        $moduleResources = $module->moduleResources()
            ->when(! $isFormateurRoute, fn ($query) => $query->where('is_visible_to_stagiaire', true))
            ->get();

        $whiteboardGroups = collect();
        $currentWhiteboardGroup = null;
        $toolGroups = collect();
        $wordClouds = collect();

        $lectureStats = $anonymous ? [] : $this->buildLectureStats($lectures, (int) $user->id);

        $sectionStatuses = $anonymous
            ? collect()
            : $module->sections->mapWithKeys(function ($sec) use ($lectureStats) {
                $total = $sec->lectures->count();
                if ($total === 0) {
                    return [$sec->id => 'not_started'];
                }

                $ok = $sec->lectures->filter(function ($lec) use ($lectureStats) {
                    $st = $lectureStats[$lec->id]['status'] ?? null;

                    return $st === 'completed';
                })->count();

                return [$sec->id => ($ok === $total ? 'completed' : ($ok > 0 ? 'in_progress' : 'not_started'))];
            });

        $contextQuery = array_filter([
            'mode' => $mode,
            'group_id' => ($mode !== 'officiel' ? ($groupId ?: null) : null),
            'include_hidden' => ($includeHidden ? 1 : null),
            'anonymous' => ($anonymous ? 1 : null),
        ]);

        if ($isFormateurRoute && ($user->role ?? null) === 'formateur') {
            $whiteboardGroups = $module->groups()
                ->accessibleByTrainer((int) $user->id)
                ->with('whiteboard')
                ->orderBy('groups.name')
                ->get(['groups.id', 'groups.name', 'groups.description'])
                ->map(function ($group) use ($groupId) {
                    return [
                        'id' => (int) $group->id,
                        'name' => (string) $group->name,
                        'description' => (string) ($group->description ?? ''),
                        'is_current' => (int) $group->id === (int) $groupId,
                        'has_whiteboard' => ! is_null($group->whiteboard),
                        'whiteboard_url' => route('formateur.groupes.whiteboard.show', ['group' => $group->id]),
                    ];
                })
                ->values();

            $currentWhiteboardGroup = $whiteboardGroups->firstWhere('is_current', true);
            [$toolGroups, $wordClouds] = $this->buildToolGroupsAndWordClouds($user, $module);
        }

        $view = match (true) {
            $anonymous && ($user->role ?? null) === 'formateur' => 'formateur.formations.anonyme.chapitre',
            $anonymous && ($user->role ?? null) === 'observateur' => 'observateur.formations.anonyme.chapitre',
            default => $this->viewBase().'.chapitre',
        };

        return view($view, [
            'module' => $module,
            'selectedSection' => $section,
            'section' => $section,
            'selectedLecture' => null,
            'lectureStats' => $lectureStats,
            'sectionStatuses' => $sectionStatuses,
            'formateur' => $module->formateur ?? null,
            'contextQuery' => $contextQuery,
            'mode' => $mode,
            'groupId' => $groupId,
            'includeHidden' => $includeHidden,
            'anonymous' => $anonymous,
            'lessonResources' => $moduleResources,
            'moduleResources' => $moduleResources,
            'whiteboardGroups' => $whiteboardGroups,
            'currentWhiteboardGroup' => $currentWhiteboardGroup,
            'toolGroups' => $toolGroups,
            'wordClouds' => $wordClouds,
        ]);
    }

    public function lire(Request $request, Module $module, ModuleSection $section, ModuleLecture $lecture)
    {
        $user = auth()->user();
        $isFormateurRoute = $request->routeIs('formateur.*');
        $isObserverRoute = $request->routeIs('observateur.*');

        abort_unless((int) $section->module_id === (int) $module->id, 404);
        abort_unless((int) $lecture->module_id === (int) $module->id, 404);
        abort_unless((int) $lecture->section_id === (int) $section->id, 404);
        abort_unless($module->isVisibleTo($user), 404);

        $lecture->load(['scormPackage.activeVersion', 'scormPackageVersion']);

        $module->load([
            'sections' => function ($q) {
                $q->orderBy('id')
                    ->with(['lectures' => function ($qq) {
                        $qq->orderBy('position')->orderBy('id');
                    }]);
            },
        ]);

        $mode = (string) $request->query('mode', 'groupe');
        $isStaff = in_array($user->role ?? null, ['formateur', 'admin', 'observateur'], true);
        $includeHidden = $request->boolean('include_hidden') && $isStaff;
        $anonymous = $request->boolean('anonymous') || ($user->role ?? null) === 'observateur';

        $groupId = $this->resolveGroupIdForContext($request, $user, (int) $module->id);

        if ($mode !== 'officiel') {
            $this->applyGroupLessonOverrides($module, $groupId, ! $includeHidden);
        }

        $section = $module->sections->firstWhere('id', (int) $section->id);
        abort_unless($section, 404);

        $visibleIds = $module->sections->flatMap(fn ($s) => $s->lectures)->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (! in_array((int) $lecture->id, $visibleIds, true)) {
            if ($mode !== 'officiel' && ! ($isStaff && $includeHidden)) {
                abort(404);
            }
        }

        $lectures = $module->sections->flatMap(fn ($s) => $s->lectures)->values();
        $lectureStats = $anonymous ? [] : $this->buildLectureStats($lectures, (int) $user->id);

        $lectureRouteName = $isFormateurRoute
            ? 'formateur.formations.lecture'
            : ($isObserverRoute ? 'observateur.formations.lecture' : 'stagiaire.module.lecture');
        $sectionRouteName = $isFormateurRoute
            ? 'formateur.formations.section'
            : ($isObserverRoute ? 'observateur.formations.section' : 'stagiaire.module.section');
        $finalRouteName = $isFormateurRoute
            ? 'formateur.formations.detail'
            : ($isObserverRoute ? 'observateur.groupes.index' : 'stagiaire.module.fin');

        $nextLecturePayload = null;
        $currentSectionLectures = $section->lectures ?? collect();
        $idx = $currentSectionLectures->search(fn ($l) => (int) $l->id === (int) $lecture->id);

        if ($idx !== false && isset($currentSectionLectures[$idx + 1])) {
            $nextLec = $currentSectionLectures[$idx + 1];
            $nextLecturePayload = [
                'type' => 'lecture',
                'id' => (int) $nextLec->id,
                'section_id' => (int) $nextLec->section_id,
                'url' => route($lectureRouteName, [
                    'module' => $module->id,
                    'section' => (int) $nextLec->section_id,
                    'lecture' => (int) $nextLec->id,
                ]),
            ];
        } else {
            $sections = $module->sections->sortBy('id')->values();
            $currentSectionIndex = $sections->search(fn ($s) => (int) $s->id === (int) $section->id);

            $nextSection = null;
            if ($currentSectionIndex !== false) {
                $nextSection = $sections
                    ->slice($currentSectionIndex + 1)
                    ->first(fn ($candidate) => $candidate->lectures->isNotEmpty());
            }

            if ($nextSection) {
                $nextLecturePayload = [
                    'type' => 'section',
                    'id' => (int) $nextSection->id,
                    'url' => route($sectionRouteName, [
                        'module' => $module->id,
                        'section' => (int) $nextSection->id,
                    ]),
                ];
            } else {
                $nextLecturePayload = [
                    'type' => 'fin',
                    'url' => route($finalRouteName, ['module' => $module->id]),
                ];
            }
        }

        $sectionStatuses = $anonymous
            ? collect()
            : $module->sections->mapWithKeys(function ($sec) use ($lectureStats) {
                $total = $sec->lectures->count();
                if ($total === 0) {
                    return [$sec->id => 'not_started'];
                }
                $ok = $sec->lectures->filter(function ($lec) use ($lectureStats) {
                    return ($lectureStats[$lec->id]['status'] ?? null) === 'completed';
                })->count();

                return [$sec->id => ($ok === $total ? 'completed' : ($ok > 0 ? 'in_progress' : 'not_started'))];
            });

        $contextQuery = array_filter([
            'mode' => $mode,
            'group_id' => ($mode !== 'officiel' ? ($groupId ?: null) : null),
            'include_hidden' => ($includeHidden ? 1 : null),
            'anonymous' => $anonymous,
        ]);

        $quizData = null;
        $moduleResources = collect();
        $whiteboardGroups = collect();
        $currentWhiteboardGroup = null;
        $toolGroups = collect();
        $wordClouds = collect();

        if ($isStaff && $lecture->quiz_enabled) {
            $quizData = $lecture->quizQuestions()
                ->with('answers')
                ->orderBy('id')
                ->get();
        }

        $moduleResources = $module->moduleResources()
            ->when(! $isFormateurRoute, fn ($query) => $query->where('is_visible_to_stagiaire', true))
            ->get();

        if ($isFormateurRoute && ($user->role ?? null) === 'formateur') {
            $whiteboardGroups = $module->groups()
                ->accessibleByTrainer((int) $user->id)
                ->with('whiteboard')
                ->orderBy('groups.name')
                ->get(['groups.id', 'groups.name', 'groups.description'])
                ->map(function ($group) use ($groupId, $module, $section, $lecture) {
                    return [
                        'id' => (int) $group->id,
                        'name' => (string) $group->name,
                        'description' => (string) ($group->description ?? ''),
                        'is_current' => (int) $group->id === (int) $groupId,
                        'has_whiteboard' => ! is_null($group->whiteboard),
                        'whiteboard_url' => route('formateur.groupes.whiteboard.show', ['group' => $group->id]),
                        'lesson_url' => route('formateur.formations.lecture', [
                            'module' => $module->id,
                            'section' => $section->id,
                            'lecture' => $lecture->id,
                            'mode' => 'groupe',
                            'group_id' => $group->id,
                        ]),
                    ];
                })
                ->values();

            $currentWhiteboardGroup = $whiteboardGroups->firstWhere('is_current', true);
            [$toolGroups, $wordClouds] = $this->buildToolGroupsAndWordClouds($user, $module);
        }

        $view = match (true) {
            $anonymous && ($user->role ?? null) === 'formateur' => 'formateur.formations.anonyme.lecon',
            $anonymous && ($user->role ?? null) === 'observateur' => 'observateur.formations.anonyme.lecon',
            default => $this->viewBase().'.lecon',
        };

        return view($view, [
            'module' => $module,
            'section' => $section,
            'selectedSection' => $section,
            'lecture' => $lecture,
            'selectedLecture' => $lecture,
            'lectureStats' => $lectureStats,
            'sectionStatuses' => $sectionStatuses,
            'nextLecture' => $nextLecturePayload,
            'formateur' => $module->formateur ?? null,
            'contextQuery' => $contextQuery,
            'mode' => $mode,
            'groupId' => $groupId,
            'includeHidden' => $includeHidden,
            'anonymous' => $anonymous,
            'quizData' => $quizData,
            'lessonResources' => $moduleResources,
            'moduleResources' => $moduleResources,
            'whiteboardGroups' => $whiteboardGroups,
            'currentWhiteboardGroup' => $currentWhiteboardGroup,
            'toolGroups' => $toolGroups,
            'wordClouds' => $wordClouds,
        ]);
    }

    public function finModule($moduleId)
    {
        $userId = (int) auth()->id();

        $module = Module::with('sections.lectures')->findOrFail($moduleId);
        if (! $module->isVisibleTo(auth()->user())) {
            abort(404);
        }

        $sections = $module->sections;
        $lectures = $sections->flatMap->lectures->values();
        $lectureIds = $lectures->pluck('id')->map(fn ($id) => (int) $id)->all();

        $totalSections = $sections->count();
        $totalLectures = $lectures->count();

        $totalQuestionsPlanned = 0;
        $questionsAnswered = 0;
        $totalCorrectAnswers = 0;

        $latestAttempts = collect();
        $quizAttemptAgg = collect();
        $scormAgg = collect();

        $quizTimeSeconds = 0;
        $scormTimeSeconds = 0;
        $videoTimeSeconds = 0;

        $quizLatencyTotalSeconds = 0;
        $quizLatencySamples = 0;
        $scormLatencyTotalSeconds = 0;
        $scormLatencySamples = 0;

        $scormInteractionsCount = 0;
        $videoSegmentsCount = 0;
        $videoReplayCount = 0;

        if (! empty($lectureIds)) {
            $latestAttempts = QuizAttempt::query()
                ->where('user_id', $userId)
                ->whereIn('lecture_id', $lectureIds)
                ->orderByDesc('finished_at')
                ->orderByDesc('id')
                ->get()
                ->groupBy('lecture_id')
                ->map(fn ($rows) => $rows->first());

            $latestAttemptIds = $latestAttempts->pluck('id')->all();

            if (! empty($latestAttemptIds)) {
                $quizAttemptAgg = QuizAttemptQuestion::query()
                    ->select([
                        'attempt_id',
                        DB::raw('SUM(CASE WHEN answered_at IS NOT NULL THEN 1 ELSE 0 END) as answered'),
                        DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct'),
                        DB::raw('SUM(time_seconds) as time_spent'),
                    ])
                    ->whereIn('attempt_id', $latestAttemptIds)
                    ->groupBy('attempt_id')
                    ->get()
                    ->keyBy('attempt_id');

                $quizLatencyTotalSeconds = (int) $quizAttemptAgg->sum(fn ($agg) => (int) ($agg->time_spent ?? 0));
                $quizLatencySamples = (int) $quizAttemptAgg->sum(fn ($agg) => (int) ($agg->answered ?? 0));
            }

            $quizTimeSeconds = (int) $latestAttempts->sum(fn ($attempt) => (int) ($attempt->total_time_seconds ?? 0));

            $scormAgg = ScormInteraction::query()
                ->where('user_id', $userId)
                ->whereIn('lecture_id', $lectureIds)
                ->whereIn('result', ['correct', 'wrong'])
                ->select([
                    'lecture_id',
                    DB::raw('COUNT(*) as answered'),
                    DB::raw("SUM(CASE WHEN result = 'correct' THEN 1 ELSE 0 END) as correct"),
                ])
                ->groupBy('lecture_id')
                ->get()
                ->keyBy('lecture_id');

            $scormInteractionsCount = (int) ScormInteraction::query()
                ->where('user_id', $userId)
                ->whereIn('lecture_id', $lectureIds)
                ->count();

            $scormTimeSeconds = (int) ScormScore::query()
                ->where('user_id', $userId)
                ->whereIn('lecture_id', $lectureIds)
                ->sum('session_time');

            $scormLatencies = ScormInteraction::query()
                ->where('user_id', $userId)
                ->whereIn('lecture_id', $lectureIds)
                ->whereIn('result', ['correct', 'wrong'])
                ->whereNotNull('latency')
                ->pluck('latency');

            foreach ($scormLatencies as $latency) {
                try {
                    [$h, $m, $s] = array_pad(explode(':', (string) $latency), 3, 0);
                    $scormLatencyTotalSeconds += ((int) $h * 3600 + (int) $m * 60 + (int) $s);
                    $scormLatencySamples++;
                } catch (\Throwable $e) {
                    // Ignore les latences SCORM mal formatées.
                }
            }

            $videoRows = VideoSegmentTracking::query()
                ->where('user_id', $userId)
                ->whereIn('lecture_id', $lectureIds)
                ->get(['watch_count', 'total_watch_time']);

            $videoSegmentsCount = $videoRows->count();
            $videoTimeSeconds = (int) round((float) $videoRows->sum('total_watch_time'));
            $videoReplayCount = (int) $videoRows->sum(fn ($row) => max(0, ((int) $row->watch_count) - 1));
        }

        foreach ($lectures as $lecture) {
            $plannedScorm = (int) ($lecture->question_count ?? 0);
            $plannedQuiz = (bool) ($lecture->quiz_enabled ?? false)
                ? (int) ($lecture->quiz_questions_per_attempt ?? 0)
                : 0;
            $planned = max($plannedScorm, $plannedQuiz);

            $scormAnswered = (int) ($scormAgg->get($lecture->id)?->answered ?? 0);
            $scormCorrect = (int) ($scormAgg->get($lecture->id)?->correct ?? 0);

            $quizAnswered = 0;
            $quizCorrect = 0;
            $attempt = $latestAttempts->get($lecture->id);

            if ($attempt) {
                $attemptAgg = $quizAttemptAgg->get($attempt->id);
                $quizAnswered = (int) ($attemptAgg?->answered ?? 0);
                $quizCorrect = (int) ($attemptAgg?->correct ?? 0);
            }

            $answered = max($scormAnswered, $quizAnswered);
            $correct = max($scormCorrect, $quizCorrect);

            if ($planned > 0) {
                $answered = min($answered, $planned);
                $correct = min($correct, $answered);
            } elseif ($answered > 0) {
                $planned = $answered;
            }

            $totalQuestionsPlanned += $planned;
            $questionsAnswered += $answered;
            $totalCorrectAnswers += $correct;
        }

        $lectureStats = ! empty($lectureIds)
            ? $this->buildLectureStats($lectures, $userId)
            : [];

        $completedLectures = collect($lectureStats)
            ->filter(fn ($stats) => ($stats['status'] ?? null) === 'completed')
            ->count();

        $completedSections = $sections->filter(function ($section) use ($lectureStats) {
            if ($section->lectures->isEmpty()) {
                return false;
            }

            return $section->lectures->every(function ($lecture) use ($lectureStats) {
                return ($lectureStats[$lecture->id]['status'] ?? null) === 'completed';
            });
        })->count();

        $moduleCompletionPercent = $totalLectures > 0
            ? (int) round(($completedLectures / $totalLectures) * 100)
            : 100;

        $sectionCompletionPercent = $totalSections > 0
            ? (int) round(($completedSections / $totalSections) * 100)
            : 100;

        $successRatePercent = $questionsAnswered > 0
            ? (int) round(($totalCorrectAnswers / $questionsAnswered) * 100)
            : null;

        $latencySamples = $quizLatencySamples + $scormLatencySamples;
        $averageLatencySeconds = $latencySamples > 0
            ? (int) round(($quizLatencyTotalSeconds + $scormLatencyTotalSeconds) / $latencySamples)
            : null;

        $totalLearningSeconds = $scormTimeSeconds + $quizTimeSeconds + $videoTimeSeconds;
        $trackedInteractions = $scormInteractionsCount + $quizLatencySamples + $videoSegmentsCount;
        $completionToast = null;

        if ($moduleCompletionPercent >= 100 && auth()->user()?->role === 'stagiaire') {
            $notifyResult = app(ModuleCompletionNotifier::class)->notify($module, auth()->user());

            if (($notifyResult['created_for_stagiaire'] ?? false) === true) {
                $completionToast = 'Vous avez termine le module "'.$module->module_name.'".';
            }
        }

        return view('stagiaire.fin_module', [
            'module' => $module,
            'totalSections' => $totalSections,
            'totalLectures' => $totalLectures,
            'totalQuestionsPlanned' => $totalQuestionsPlanned,
            'questionsAnswered' => $questionsAnswered,
            'usabilityStats' => [
                'completed_lectures' => $completedLectures,
                'completed_sections' => $completedSections,
                'module_completion_percent' => $moduleCompletionPercent,
                'section_completion_percent' => $sectionCompletionPercent,
                'success_rate_percent' => $successRatePercent,
                'total_learning_seconds' => $totalLearningSeconds,
                'scorm_time_seconds' => $scormTimeSeconds,
                'quiz_time_seconds' => $quizTimeSeconds,
                'video_time_seconds' => $videoTimeSeconds,
                'average_latency_seconds' => $averageLatencySeconds,
                'tracked_interactions' => $trackedInteractions,
                'scorm_interactions' => $scormInteractionsCount,
                'quiz_answers' => $quizLatencySamples,
                'video_segments' => $videoSegmentsCount,
                'video_replays' => $videoReplayCount,
            ],
            'completionToast' => $completionToast,
        ]);
    }

    private function storeModuleVideo(Module $module, UploadedFile $video): string
    {
        $videosBase = trim((string) config('learning_assets.videos_base', 'modules/videos'), '/');
        $relativeFolder = 'modules/module_'.$module->id;
        $storageFolder = $videosBase.'/'.$relativeFolder;
        $disk = Storage::disk('public');

        if (! $disk->exists($storageFolder)) {
            $disk->makeDirectory($storageFolder);
        }

        $this->deleteManagedModuleVideo($module->module_video, $module->id);

        $baseName = Str::slug(pathinfo((string) $video->getClientOriginalName(), PATHINFO_FILENAME));
        if ($baseName === '') {
            $baseName = 'module-video';
        }

        $extension = strtolower((string) $video->getClientOriginalExtension());
        if ($extension === '') {
            $extension = 'mp4';
        }

        $fileName = now()->format('Ymd_His').'_'.Str::random(6).'_'.$baseName.'.'.$extension;
        $disk->putFileAs($storageFolder, $video, $fileName);

        return route('media.storage', ['path' => $storageFolder.'/'.$fileName], false);
    }

    private function deleteManagedModuleVideo(?string $videoPath, int $moduleId): void
    {
        $videoPath = trim((string) $videoPath);
        if ($videoPath === '') {
            return;
        }

        $videosBase = trim((string) config('learning_assets.videos_base', 'modules/videos'), '/');
        $relativeFolder = 'modules/module_'.$moduleId;
        $normalized = ltrim($videoPath, '/');
        $disk = Storage::disk('public');
        $candidatePaths = [];

        if (Str::startsWith($videoPath, '/media/storage/')) {
            $candidatePaths[] = Str::after($videoPath, '/media/storage/');
        }
        if (Str::startsWith($videoPath, $relativeFolder.'/')) {
            $candidatePaths[] = $videosBase.'/'.$videoPath;
        }
        if (Str::startsWith($normalized, $videosBase.'/'.$relativeFolder.'/')) {
            $candidatePaths[] = $normalized;
        }

        foreach (array_unique($candidatePaths) as $candidatePath) {
            if ($disk->exists($candidatePath)) {
                $disk->delete($candidatePath);
            }
        }

        $folderPath = $videosBase.'/'.$relativeFolder;
        if ($disk->exists($folderPath) && count($disk->allFiles($folderPath)) === 0) {
            $disk->deleteDirectory($folderPath);
        }
    }

    private function resolveGroupIdForContext(Request $request, $user, int $moduleId): ?int
    {
        $forcedGroupId = (int) $request->query('group_id', 0);
        $isStaff = in_array($user->role ?? null, ['formateur', 'admin', 'observateur'], true);

        if ($forcedGroupId > 0 && $isStaff) {
            $hasModule = DB::table('group_module')
                ->where('group_id', $forcedGroupId)
                ->where('module_id', $moduleId)
                ->exists();

            if (! $hasModule) {
                return null;
            }

            if (($user->role ?? null) === 'formateur') {
                $isAccessible = Group::query()
                    ->accessibleByTrainer((int) $user->id)
                    ->where('id', $forcedGroupId)
                    ->exists();

                if (! $isAccessible) {
                    return null;
                }
            }

            if (($user->role ?? null) === 'observateur') {
                $isObserved = DB::table('group_user')
                    ->where('group_id', $forcedGroupId)
                    ->where('user_id', (int) $user->id)
                    ->where('role_in_group', 'observateur')
                    ->exists();

                if (! $isObserved) {
                    return null;
                }
            }

            return $forcedGroupId;
        }

        return $this->resolveGroupIdForUserAndModule((int) $user->id, $moduleId);
    }

    private function resolveGroupIdForUserAndModule(int $userId, int $moduleId): ?int
    {
        $formateurGroupId = Group::query()
            ->accessibleByTrainer($userId)
            ->whereHas('modules', fn ($query) => $query->where('modules.id', $moduleId))
            ->value('groups.id');

        if ($formateurGroupId) {
            return (int) $formateurGroupId;
        }

        $gid = DB::table('group_user')
            ->join('group_module', 'group_module.group_id', '=', 'group_user.group_id')
            ->where('group_user.user_id', $userId)
            ->where('group_module.module_id', $moduleId)
            ->value('group_user.group_id');

        return $gid ? (int) $gid : null;
    }

    private function buildToolGroupsAndWordClouds($user, Module $module): array
    {
        $toolGroups = Group::query()
            ->accessibleByTrainer((int) $user->id)
            ->with([
                'modules' => function ($query): void {
                    $query->orderBy('group_module.position')
                        ->with([
                            'sections' => function ($sectionQuery): void {
                                $sectionQuery->orderBy('id')
                                    ->with([
                                        'lectures' => function ($lectureQuery): void {
                                            $lectureQuery
                                                ->orderBy('position')
                                                ->orderBy('id')
                                                ->select('id', 'module_id', 'section_id', 'lecture_title', 'position');
                                        },
                                    ])
                                    ->select('id', 'module_id', 'section_title');
                            },
                        ])
                        ->select('modules.id', 'modules.module_name', 'modules.module_title');
                },
            ])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Group $group) {
                $modules = $group->modules->map(function (Module $groupModule) use ($group) {
                    $lectures = $groupModule->sections
                        ->flatMap(function (ModuleSection $moduleSection) {
                            return $moduleSection->lectures->map(function (ModuleLecture $moduleLecture) use ($moduleSection) {
                                return [
                                    'id' => (int) $moduleLecture->id,
                                    'section_id' => (int) $moduleLecture->section_id,
                                    'title' => (string) $moduleLecture->lecture_title,
                                    'label' => trim((string) $moduleSection->section_title.' · '.(string) $moduleLecture->lecture_title),
                                ];
                            });
                        })
                        ->values();

                    return [
                        'id' => (int) $groupModule->id,
                        'title' => (string) ($groupModule->module_title ?: $groupModule->module_name ?: 'Module'),
                        'manage_url' => route('formateur.groupes.modules.lecons.edit', [
                            'group' => $group->id,
                            'module' => $groupModule->id,
                        ]),
                        'lectures' => $lectures,
                    ];
                })->values();

                return [
                    'id' => (int) $group->id,
                    'name' => (string) $group->name,
                    'whiteboard_url' => route('formateur.groupes.whiteboard.show', ['group' => $group->id]),
                    'modules' => $modules,
                ];
            })
            ->values();

        $toolModuleIds = $toolGroups
            ->pluck('modules')
            ->flatten(1)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $wordClouds = WordCloud::query()
            ->when(
                $toolModuleIds->isNotEmpty(),
                fn ($query) => $query->whereIn('module_id', $toolModuleIds->all()),
                fn ($query) => $query->where('module_id', $module->id)
            )
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (WordCloud $wordCloud) {
                return [
                    'id' => (int) $wordCloud->id,
                    'title' => (string) $wordCloud->title,
                    'question' => (string) $wordCloud->question,
                    'module_id' => (int) ($wordCloud->module_id ?? 0),
                    'group_id' => (int) ($wordCloud->group_id ?? 0),
                    'access_code' => (string) $wordCloud->access_code,
                    'is_active' => (bool) $wordCloud->is_active,
                    'live_url' => route('formateur.nuages.live', ['wordCloud' => $wordCloud->id]),
                    'join_url' => route('wordcloud.join.code', ['code' => $wordCloud->access_code]),
                    'updated_at_human' => $wordCloud->updated_at?->diffForHumans(),
                ];
            })
            ->values();

        return [$toolGroups, $wordClouds];
    }
}
