<?php

namespace App\Http\Controllers\Backend;

use App\Domains\ModulesFormateur\Support\AccesFormationCatalogue;
use App\Http\Controllers\Controller;
use App\Jobs\ConvertLectureSlides;
use App\Models\LectureObjective;
use App\Models\Module;
use App\Models\ModuleLecture;
use App\Models\ModuleSection;
use App\Models\QuizQuestion;
use App\Models\ScormPackageVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleLectureController extends Controller
{
    public function __construct(private readonly AccesFormationCatalogue $accesCatalogue) {}

    public function AddModuleLecture($id)
    {
        $module = Module::findOrFail($id);
        $this->accesCatalogue->assertCatalogue($module);

        return redirect()->route('admin.formations.constructeur.edit', $module);
    }

    public function MoveLectureUp($id)
    {
        $lecture = ModuleLecture::findOrFail($id);
        $this->assertLectureEditable($lecture);

        $prev = ModuleLecture::where('section_id', $lecture->section_id)
            ->where('position', '<', $lecture->position)
            ->orderByDesc('position')
            ->first();

        if ($prev) {
            [$lecture->position, $prev->position] = [$prev->position, $lecture->position];
            $lecture->save();
            $prev->save();
        }

        return back();
    }

    public function MoveLectureDown($id)
    {
        $lecture = ModuleLecture::findOrFail($id);
        $this->assertLectureEditable($lecture);

        $next = ModuleLecture::where('section_id', $lecture->section_id)
            ->where('position', '>', $lecture->position)
            ->orderBy('position')
            ->first();

        if ($next) {
            [$lecture->position, $next->position] = [$next->position, $lecture->position];
            $lecture->save();
            $next->save();
        }

        return back();
    }

    public function SaveLecture(Request $request)
    {
        $request->validate([
            'module_id'     => 'required|exists:modules,id',
            'section_id'    => 'required|exists:module_sections,id',
            'lecture_title' => 'required|string|max:255',
        ]);

        $module = Module::findOrFail((int) $request->input('module_id'));
        $this->accesCatalogue->assertEditable($module);
        $section = ModuleSection::query()
            ->whereKey((int) $request->input('section_id'))
            ->where('module_id', $module->id)
            ->firstOrFail();

        $lastPosition = ModuleLecture::where('section_id', $section->id)->max('position') ?? 0;

        ModuleLecture::create([
            'module_id'                  => $request->module_id,
            'section_id'                 => $section->id,
            'lecture_title'              => $request->lecture_title,
            'position'                   => $lastPosition + 1,
            'content_type'               => 'scorm',
            'slides_status'              => 'none',
            'slides_path'                => null,
            'slides_source_path'         => null,
            'slides_error'               => null,
            'slides_converted_at'        => null,
            'slide_count'                => 0,
            'quiz_questions_per_attempt' => 0,
            'scorm_path'                 => null,
        ]);

        return response()->json(['success' => 'Leçon enregistrée avec succès.']);
    }

    public function EditLecture($id)
    {
        $lecture = ModuleLecture::findOrFail($id);
        $this->accesCatalogue->assertCatalogue($lecture->module);

        return redirect()->route('admin.formations.constructeur.lectures.edit', $lecture);
    }

    public function importSlidesForLecture(Request $request)
    {
        $validated = $request->validate([
            'lecture_id'  => ['required', 'exists:module_lectures,id'],
            'slides_file' => ['required', 'file', 'mimes:ppt,pptx,pdf', 'max:51200'],
        ]);

        $lecture = ModuleLecture::findOrFail((int) $validated['lecture_id']);
        $this->assertLectureEditable($lecture);
        $file = $request->file('slides_file');

        if (!empty($lecture->slides_source_path)) {
            Storage::disk('local')->delete($lecture->slides_source_path);
        }

        $storedPath = $file->storeAs(
            'slides/sources/lecture_' . $lecture->id,
            'lecture_' . $lecture->id . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension(),
            'local'
        );

        $lecture->update([
            'content_type'       => 'slides',
            'slides_status'      => 'pending',
            'slides_error'       => null,
            'slides_source_path' => $storedPath,
        ]);

        ConvertLectureSlides::dispatch($lecture->id, $storedPath)->afterResponse();

        return redirect()
            ->back()
            ->with('success', 'Import Slides lancé. Conversion en cours...');
    }

    public function retrySlidesForLecture(Request $request)
    {
        $validated = $request->validate([
            'lecture_id' => ['required', 'exists:module_lectures,id'],
        ]);

        $lecture = ModuleLecture::findOrFail((int) $validated['lecture_id']);
        $this->assertLectureEditable($lecture);
        $sourcePath = (string) ($lecture->slides_source_path ?? '');

        if ($sourcePath === '' || !Storage::disk('local')->exists($sourcePath)) {
            return redirect()
                ->back()
                ->with('error', 'Aucun fichier source disponible. Reimporte un PPT/PDF avant de relancer.');
        }

        $lecture->update([
            'content_type'  => 'slides',
            'slides_status' => 'pending',
            'slides_error'  => null,
        ]);

        ConvertLectureSlides::dispatch($lecture->id, $sourcePath)->afterResponse();

        return redirect()
            ->back()
            ->with('success', 'Relance de conversion envoyée. Traitement en cours...');
    }

    public function UpdateModuleLecture(Request $request)
    {
        $validated = $request->validate([
            'id'                         => 'required|exists:module_lectures,id',
            'lecture_title'              => 'required|string|max:255',
            'duration'                   => 'nullable|integer|min:0',
            'content_type'               => 'required|in:scorm,slides',
            'scorm_path'                 => 'nullable|string|max:255',
            'scorm_package_id'           => 'nullable|exists:scorm_packages,id',
            'use_active_scorm_version'   => 'nullable|in:0,1',
            'scorm_package_version_id'   => 'nullable|exists:scorm_package_versions,id',
            'slide_count'                => 'nullable|integer|min:0',
            'quiz_enabled'               => 'nullable|in:0,1',
            'quiz_questions_per_attempt' => 'exclude_unless:quiz_enabled,1|required|integer|min:1',
            'objectives'                 => ['nullable', 'array'],
            'objectives.*.id'            => ['nullable', 'integer', 'exists:lecture_objectives,id'],
            'objectives.*.title'         => ['nullable', 'string', 'max:255'],
            'objectives.*.description'   => ['nullable', 'string'],
            'objectives.*.position'      => ['nullable', 'integer', 'min:1'],
            'objectives.*._delete'       => ['nullable', 'boolean'],
        ]);

        $lecture = ModuleLecture::findOrFail($validated['id']);
        $this->assertLectureEditable($lecture);

        $useActive   = ($request->input('use_active_scorm_version', '1') === '1');
        $quizEnabled = ($request->input('quiz_enabled', '0') === '1');

        if (!$useActive && !$request->filled('scorm_package_version_id')) {
            return back()
                ->withErrors(['scorm_package_version_id' => 'Sélectionne une version SCORM (ou active "version active").'])
                ->withInput();
        }

        if ($request->filled('scorm_package_id') && $request->filled('scorm_package_version_id')) {
            $ver = ScormPackageVersion::find($request->input('scorm_package_version_id'));

            if (!$ver || (int) $ver->scorm_package_id !== (int) $request->input('scorm_package_id')) {
                return back()
                    ->withErrors(['scorm_package_version_id' => 'La version sélectionnée ne correspond pas au SCORM choisi.'])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($lecture, $validated, $request, $useActive, $quizEnabled) {

            $finalScormPath = $validated['scorm_path'] ?? $lecture->scorm_path;

            $lecture->update([
                'lecture_title'              => $validated['lecture_title'],
                'duration'                   => $request->input('duration'),
                'content_type'               => $validated['content_type'],
                'scorm_path'                 => $finalScormPath,
                'scorm_package_id'           => $request->input('scorm_package_id'),
                'use_active_scorm_version'   => $useActive,
                'scorm_package_version_id'   => $useActive ? null : $request->input('scorm_package_version_id'),
                'slide_count'                => $request->filled('slide_count')
                    ? (int) $request->input('slide_count')
                    : (int) ($lecture->slide_count ?? 0),
                'quiz_enabled'               => $quizEnabled,
                'quiz_questions_per_attempt' => $quizEnabled ? (int) $request->input('quiz_questions_per_attempt') : 0,
            ]);

            $this->syncLectureObjectives($lecture, $request->input('objectives', []));
        });

        $action = $request->input('save_action', 'back');

        if ($action === 'stay') {
            return redirect()->back()->with('success', 'La lecture a été mise à jour avec succès.');
        }

        return redirect()
            ->route('admin.modules.lecture.add', ['id' => $lecture->module_id])
            ->with('success', 'La lecture a été mise à jour avec succès.');
    }

    public function DeleteLecture($id)
    {
        $lecture = ModuleLecture::find($id);

        if ($lecture) {
            $this->assertLectureEditable($lecture);

            DB::transaction(function () use ($lecture): void {
                $this->deleteLectureAndDependencies($lecture);
            });
        }

        $this->cleanupOrphanQuizQuestions();

        return redirect()->back()->with([
            'message'    => 'Lecture supprimée',
            'alert-type' => 'success',
        ]);
    }

    private function deleteLectureAndDependencies(ModuleLecture $lecture): void
    {
        if (!empty($lecture->slides_path)) {
            Storage::disk('public')->deleteDirectory($lecture->slides_path);
        }
        if (!empty($lecture->slides_source_path)) {
            Storage::disk('local')->delete($lecture->slides_source_path);
        }

        $this->deleteQuestionsForLecture((int) $lecture->id);
        LectureObjective::query()->where('lecture_id', $lecture->id)->delete();
        $lecture->delete();
    }

    private function assertLectureEditable(ModuleLecture $lecture): void
    {
        $this->accesCatalogue->assertEditable($lecture->module()->firstOrFail());
    }

    private function deleteQuestionsForLecture(int $lectureId): void
    {
        QuizQuestion::query()
            ->where('lecture_id', $lectureId)
            ->orderBy('id')
            ->chunkById(200, function ($questions): void {
                foreach ($questions as $question) {
                    if (!empty($question->image_path)) {
                        Storage::disk('public')->delete($question->image_path);
                    }
                    if (!empty($question->audio_path)) {
                        Storage::disk('public')->delete($question->audio_path);
                    }
                    $question->delete();
                }
            });
    }

    private function cleanupOrphanQuizQuestions(): int
    {
        $deleted = 0;

        QuizQuestion::query()
            ->whereDoesntHave('lecture')
            ->orderBy('id')
            ->chunkById(200, function ($questions) use (&$deleted): void {
                foreach ($questions as $question) {
                    if (!empty($question->image_path)) {
                        Storage::disk('public')->delete($question->image_path);
                    }
                    if (!empty($question->audio_path)) {
                        Storage::disk('public')->delete($question->audio_path);
                    }
                    $question->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }

    private function syncLectureObjectives(ModuleLecture $lecture, array $rows = []): void
    {
        $rows = collect($rows)
            ->map(function ($r) {
                return [
                    'id'          => isset($r['id']) ? (int) $r['id'] : null,
                    'title'       => isset($r['title']) ? trim((string) $r['title']) : '',
                    'description' => isset($r['description']) ? trim((string) $r['description']) : null,
                    'position'    => isset($r['position']) && (int) $r['position'] > 0 ? (int) $r['position'] : null,
                    '_delete'     => !empty($r['_delete']),
                ];
            })
            ->filter(fn ($r) => $r['_delete'] || $r['title'] !== '')
            ->values();

        $existingIds = $lecture->objectives()->pluck('id')->all();

        $autoPos = 1;

        foreach ($rows as $row) {
            if ($row['_delete'] === true) {
                if ($row['id'] && in_array($row['id'], $existingIds, true)) {
                    $lecture->objectives()->whereKey($row['id'])->delete();
                }
                continue;
            }

            $pos = $row['position'] ?? $autoPos;

            if ($row['id'] && in_array($row['id'], $existingIds, true)) {
                $lecture->objectives()->whereKey($row['id'])->update([
                    'title'       => $row['title'],
                    'description' => $row['description'],
                    'position'    => $pos,
                ]);
            } else {
                $lecture->objectives()->create([
                    'title'       => $row['title'],
                    'description' => $row['description'],
                    'position'    => $pos,
                ]);
            }

            $autoPos++;
        }

        $ordered = $lecture->objectives()->orderBy('position')->orderBy('id')->get();
        $i = 1;
        foreach ($ordered as $obj) {
            if ((int) $obj->position !== $i) {
                $obj->update(['position' => $i]);
            }
            $i++;
        }
    }
}
