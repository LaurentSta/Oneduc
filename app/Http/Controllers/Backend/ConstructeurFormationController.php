<?php

namespace App\Http\Controllers\Backend;

use App\Domains\ModulesFormateur\Actions\CreerChapitre;
use App\Domains\ModulesFormateur\Actions\AjouterRessourceLecon;
use App\Domains\ModulesFormateur\Actions\ImporterSupportLecon;
use App\Domains\ModulesFormateur\Actions\ModifierPedagogieLecon;
use App\Domains\ModulesFormateur\Actions\CreerLecon;
use App\Domains\ModulesFormateur\Actions\CreerModule;
use App\Domains\ModulesFormateur\Actions\DeplacerLecon;
use App\Domains\ModulesFormateur\Actions\DupliquerLecon;
use App\Domains\ModulesFormateur\Actions\GenererAudioLecon;
use App\Domains\ModulesFormateur\Actions\GenererLeconIA;
use App\Domains\ModulesFormateur\Actions\GenererStructureFormationIA;
use App\Domains\ModulesFormateur\Actions\ModifierLecon;
use App\Domains\ModulesFormateur\Actions\ModifierOptionsModule;
use App\Domains\ModulesFormateur\Actions\PromouvoirLeconEnChapitre;
use App\Domains\ModulesFormateur\Actions\ReordonnerChapitres;
use App\Domains\ModulesFormateur\Actions\ReordonnerLecons;
use App\Domains\ModulesFormateur\Actions\TeleverserAudioModule;
use App\Domains\ModulesFormateur\Actions\TeleverserImageModule;
use App\Domains\ModulesFormateur\Actions\TeleverserScormModule;
use App\Domains\ModulesFormateur\Actions\TeleverserVideoModule;
use App\Domains\ModulesFormateur\Support\AccesFormationCatalogue;
use App\Domains\ModulesFormateur\Support\DonneesModule;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Group;
use App\Models\LessonResource;
use App\Models\Module;
use App\Models\ModuleLecture;
use App\Models\ModuleSection;
use App\Models\User;
use App\Services\ConsommationIADashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConstructeurFormationController extends Controller
{
    public function __construct(
        private readonly AccesFormationCatalogue $acces,
        private readonly DonneesModule $donneesModule,
        private readonly CreerModule $creerModule,
        private readonly CreerChapitre $creerChapitre,
        private readonly ReordonnerChapitres $reordonnerChapitres,
        private readonly CreerLecon $creerLecon,
        private readonly GenererLeconIA $genererLeconIA,
        private readonly GenererStructureFormationIA $genererStructureFormationIA,
        private readonly GenererAudioLecon $genererAudioLecon,
        private readonly ModifierLecon $modifierLecon,
        private readonly DupliquerLecon $dupliquerLecon,
        private readonly ReordonnerLecons $reordonnerLecons,
        private readonly DeplacerLecon $deplacerLecon,
        private readonly PromouvoirLeconEnChapitre $promouvoirLeconEnChapitre,
        private readonly TeleverserImageModule $televerserImageModule,
        private readonly TeleverserAudioModule $televerserAudioModule,
        private readonly TeleverserVideoModule $televerserVideoModule,
        private readonly TeleverserScormModule $televerserScormModule,
        private readonly ModifierOptionsModule $modifierOptionsModule,
        private readonly ConsommationIADashboardService $consommationIADashboardService,
    ) {}

    public function index()
    {
        $formationsCatalogue = Module::query()
            ->catalogue()
            ->with(['formateur:id,name', 'auteur:id,name'])
            ->withCount(['sections', 'groups'])
            ->orderByDesc('updated_at')
            ->get();

        $formationsFormateurs = Module::query()
            ->where('is_trainer_authored', true)
            ->with('formateur:id,name')
            ->withCount(['sections', 'groups'])
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.backend.formations-constructeur.index', [
            'modules' => $formationsCatalogue,
            'formationsCatalogue' => $formationsCatalogue,
            'formationsFormateurs' => $formationsFormateurs,
        ]);
    }

    public function create()
    {
        return view('admin.backend.formations-constructeur.create', [
            'formateurs' => $this->formateursReferents(),
            'categories' => Category::query()->orderBy('category_name')->get(),
        ]);
    }

    public function consommationIA()
    {
        return view('admin.backend.formations-constructeur.consommation-ia', [
            'resume' => $this->consommationIADashboardService->resumePourFormateur((int) auth()->id()),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'module_title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'formateur_id' => $this->regleFormateurReferent(),
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $module = $this->creerModule->creerFormationCatalogueVide(
            $validated,
            (int) auth()->id(),
            $validated['formateur_id'] ?? null,
        );
        $this->creerChapitre->execute($module, 'Chapitre 1');

        return redirect()
            ->route('admin.formations.constructeur.edit', $module)
            ->with('success', 'Formation catalogue créée. Ajoutez maintenant ses leçons.');
    }

    public function genererStructureIA(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'nullable|string|max:500',
            'document' => 'nullable|file|mimes:pdf,docx,pptx,txt|max:20480',
            'niveau_public' => ['nullable', 'string', Rule::in(['debutant', 'intermediaire', 'avance', 'mixte'])],
            'contexte_public' => 'nullable|string|max:300',
            'contraintes_public' => 'nullable|string|max:300',
            'formateur_id' => $this->regleFormateurReferent(),
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        if (blank($validated['theme'] ?? null) && ! $request->hasFile('document')) {
            return back()->withInput()->with('error', "Merci de renseigner un thème ou d'importer un document.");
        }

        set_time_limit(310);

        try {
            $module = $this->genererStructureFormationIA->execute(
                $validated['theme'] ?? null,
                $request->file('document'),
                (int) auth()->id(),
                $validated['niveau_public'] ?? null,
                $validated['contexte_public'] ?? null,
                $validated['contraintes_public'] ?? null,
                [
                    'category_id' => $validated['category_id'] ?? null,
                    'formateur_id' => $validated['formateur_id'] ?? null,
                ],
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return back()->withInput()->with('error', "La génération par l'IA a pris trop de temps. Réessayez avec une source plus courte.");
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->configurerCommeBrouillonCatalogue($module, $validated['formateur_id'] ?? null);

        return redirect()
            ->route('admin.formations.constructeur.edit', $module)
            ->with('success', "Formation générée par l'IA. Relisez-la avant publication.");
    }

    public function edit(Module $module)
    {
        $this->acces->assertCatalogue($module);

        $module->load([
            'sections.lectures' => fn ($query) => $query->orderBy('position'),
            'groups',
            'formateur',
        ]);

        $idsAutresVersions = Module::query()
            ->versionsOf($module)
            ->whereKeyNot($module->id)
            ->pluck('id');

        $groupesAutresVersions = Group::query()
            ->whereHas('modules', fn ($query) => $query->whereIn('modules.id', $idsAutresVersions))
            ->orderBy('name')
            ->get();

        $groupesAffectables = Group::query()
            ->whereNotIn('id', $groupesAutresVersions->pluck('id'))
            ->orderBy('name')
            ->get();

        return view('admin.backend.formations-constructeur.edit', [
            'module' => $module,
            'accessibleGroups' => $groupesAffectables,
            'categories' => Category::query()->orderBy('category_name')->get(),
            'formateurs' => $this->formateursReferents(),
            'groupesAutresVersions' => $groupesAutresVersions,
        ]);
    }

    public function update(Request $request, Module $module)
    {
        $this->acces->assertEditable($module);

        $validated = $request->validate([
            'module_title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'formateur_id' => $this->regleFormateurReferent(),
        ]);

        $attributes = [
            'module_title' => $validated['module_title'],
            'module_name' => $validated['module_title'],
            'description' => $validated['description'] ?? null,
        ];

        if ($request->has('formateur_id')) {
            $attributes['formateur_id'] = $validated['formateur_id'] ?? null;
        }

        $module->update($attributes);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Formation mise à jour.');
    }

    public function updateOptions(Request $request, Module $module)
    {
        $this->acces->assertEditable($module);

        $validated = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'formateur_id' => $this->regleFormateurReferent(),
            'label' => 'nullable|string|max:255',
            'duree' => 'nullable|string|max:100',
            'estimated_question_seconds' => 'nullable|integer|min:1|max:600',
            'resources' => 'nullable|string|max:255',
            'prerequi' => 'nullable|string',
            'objectifs' => 'nullable|string',
            'module_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'header_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'module_video_file' => 'nullable|file|mimes:mp4,m4v,mov,avi,webm|max:307200',
        ]);

        $validated['certificat'] = $request->boolean('certificat');
        $validated['status'] = false;

        $this->modifierOptionsModule->execute(
            $module,
            $validated,
            $request->file('module_image'),
            $request->file('header_image'),
            $request->file('module_video_file'),
        );

        $this->creerModule->classerDansCatalogue($module, (int) $validated['category_id']);

        if ($request->has('formateur_id')) {
            $module->update(['formateur_id' => $validated['formateur_id'] ?? null]);
        }

        return back()->with('success', 'Options de la formation mises à jour.');
    }

    public function destroy(Module $module)
    {
        $this->acces->assertEditable($module);
        $module->delete();

        return redirect()
            ->route('admin.formations.constructeur.index')
            ->with('success', 'Brouillon de formation supprimé.');
    }

    public function preview(Request $request, Module $module)
    {
        $module->load(['sections.lectures' => fn ($query) => $query->orderBy('position')]);
        $sectionId = $request->integer('section');
        $lectureId = $request->integer('lecture');
        $section = $module->sections->firstWhere('id', $sectionId) ?? $module->sections->first();
        $lecture = $section?->lectures->firstWhere('id', $lectureId) ?? $section?->lectures->first();

        if (! $section || ! $lecture) {
            return back()->with('error', "Ajoutez au moins une leçon avant d'ouvrir l'aperçu.");
        }

        return view('admin.backend.formations-constructeur.preview', $this->donneesModule->apercu($lecture));
    }

    public function storeSection(Request $request, Module $module)
    {
        $this->acces->assertEditable($module);
        $validated = $request->validate(['section_title' => 'required|string|max:255']);
        $section = $this->creerChapitre->execute($module, $validated['section_title']);

        if ($request->wantsJson()) {
            return response()->json(['section' => $this->donneesModule->section($section)], 201);
        }

        return back()->with('success', 'Chapitre ajouté.');
    }

    public function updateSection(Request $request, ModuleSection $section)
    {
        $this->acces->assertEditable($section->module);
        $validated = $request->validate(['section_title' => 'required|string|max:255']);
        $section->update(['section_title' => $validated['section_title']]);

        if ($request->wantsJson()) {
            return response()->json(['section' => $this->donneesModule->section($section)]);
        }

        return back()->with('success', 'Chapitre mis à jour.');
    }

    public function destroySection(Request $request, ModuleSection $section)
    {
        $this->acces->assertEditable($section->module);

        if ($section->lectures()->exists()) {
            return response()->json([
                'message' => 'Ce chapitre contient des leçons. Déplacez-les ou supprimez-les avant de supprimer le chapitre.',
            ], 422);
        }

        $section->delete();

        return $request->wantsJson()
            ? response()->json(['success' => true])
            : back()->with('success', 'Chapitre supprimé.');
    }

    public function reorderSections(Request $request, Module $module)
    {
        $this->acces->assertEditable($module);
        $validated = $request->validate([
            'section_ids' => 'required|array',
            'section_ids.*' => 'integer',
        ]);

        $this->reordonnerChapitres->execute($module, $validated['section_ids']);

        return response()->json(['success' => true]);
    }

    public function storeLecture(Request $request, ModuleSection $section)
    {
        $this->acces->assertEditable($section->module);
        $validated = $request->validate([
            'lecture_title' => 'required|string|max:255',
            'content_blocks' => 'nullable|string',
        ]);

        $lecture = $this->creerLecon->execute(
            $section,
            $validated['lecture_title'],
            $validated['content_blocks'] ?? null,
        );

        if ($request->wantsJson()) {
            return response()->json(['lecture' => $this->donneesModule->lecture($lecture)], 201);
        }

        return back()->with('success', 'Leçon ajoutée.');
    }

    public function genererLeconIA(Request $request, ModuleSection $section)
    {
        $this->acces->assertEditable($section->module);
        $validated = $request->validate([
            'document' => 'required|file|mimes:pdf,docx,pptx,txt|max:20480',
            'lecture_title' => 'nullable|string|max:255',
        ]);

        try {
            $resultat = $this->genererLeconIA->execute(
                $request->file('document'),
                $section->module,
                (int) auth()->id(),
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return back()->with('error', "La génération par l'IA a pris trop de temps. Réessayez.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->creerLecon->execute(
            $section,
            ($validated['lecture_title'] ?? null) ?: $resultat['title'],
            $resultat['blocks_json'],
        );

        return redirect()
            ->route('admin.formations.constructeur.edit', $section->module)
            ->with('success', "Leçon générée par l'IA. Relisez-la avant publication.");
    }

    public function editLecture(ModuleLecture $lecture)
    {
        $this->acces->assertCatalogue($lecture->module);

        return view('admin.backend.formations-constructeur.lecture-edit', $this->donneesModule->conception($lecture));
    }

    public function modifierPedagogie(Request $request, ModuleLecture $lecture, ModifierPedagogieLecon $action)
    {
        $this->acces->assertEditable($lecture->module);
        $action->execute($lecture, $request->all());

        return back()->with('success', 'Réglages pédagogiques enregistrés.');
    }

    public function importerSupport(Request $request, ModuleLecture $lecture, ImporterSupportLecon $action)
    {
        $this->acces->assertEditable($lecture->module);
        $action->execute($lecture, $request);

        return back()->with('success', 'Support importé. Les présentations sont converties en arrière-plan.');
    }

    public function relancerSlides(ModuleLecture $lecture, ImporterSupportLecon $action)
    {
        $this->acces->assertEditable($lecture->module);
        $action->relancer($lecture);

        return back()->with('success', 'Conversion relancée.');
    }

    public function apercuScorm(Request $request, ModuleLecture $lecture)
    {
        $this->acces->assertCatalogue($lecture->module);

        return view('shared.scorm-apercu', [
            'lecture' => $lecture,
            'scormUrl' => $this->donneesModule->urlScormApercu($lecture, $request->query('bloc')),
        ]);
    }

    public function ajouterRessource(Request $request, ModuleLecture $lecture, AjouterRessourceLecon $action)
    {
        $this->acces->assertEditable($lecture->module);
        $action->execute($lecture, $request);

        return back()->with('success', 'Ressource ajoutée au module.');
    }

    public function modifierVisibiliteRessource(Request $request, ModuleLecture $lecture, LessonResource $resource)
    {
        $this->acces->assertEditable($lecture->module);
        abort_unless((int) $resource->module_id === (int) $lecture->module_id, 404);
        $valide = $request->validate(['is_visible_to_stagiaire' => ['required', 'boolean']]);
        $resource->update($valide);

        return back()->with('success', 'Visibilité de la ressource enregistrée.');
    }

    public function supprimerRessource(ModuleLecture $lecture, LessonResource $resource)
    {
        $this->acces->assertEditable($lecture->module);
        abort_unless((int) $resource->module_id === (int) $lecture->module_id, 404);
        Storage::disk('public')->delete($resource->file_path);
        $resource->delete();

        return back()->with('success', 'Ressource supprimée.');
    }

    public function updateLecture(Request $request, ModuleLecture $lecture)
    {
        $this->acces->assertEditable($lecture->module);
        $validated = $request->validate([
            'lecture_title' => 'required|string|max:255',
            'content_blocks' => 'nullable|string',
        ]);

        $lecture = $this->modifierLecon->execute(
            $lecture,
            $validated['lecture_title'],
            $validated['content_blocks'] ?? null,
            $request->has('content_blocks'),
        );

        if ($request->wantsJson()) {
            return response()->json([
                'lecture' => $this->donneesModule->lecture($lecture),
                'saved_at' => now()->toIso8601String(),
            ]);
        }

        return back()->with('success', 'Leçon mise à jour.');
    }

    public function duplicateLecture(Request $request, ModuleLecture $lecture)
    {
        $this->acces->assertEditable($lecture->module);
        $duplicate = $this->dupliquerLecon->execute($lecture);

        if ($request->wantsJson()) {
            return response()->json(['lecture' => $this->donneesModule->lecture($duplicate)], 201);
        }

        return back()->with('success', 'Leçon dupliquée.');
    }

    public function destroyLecture(Request $request, ModuleLecture $lecture)
    {
        $this->acces->assertEditable($lecture->module);
        $lecture->delete();

        return $request->wantsJson()
            ? response()->json(['success' => true])
            : back()->with('success', 'Leçon supprimée.');
    }

    public function reorderLectures(Request $request, ModuleSection $section)
    {
        $this->acces->assertEditable($section->module);
        $validated = $request->validate([
            'lecture_ids' => 'required|array',
            'lecture_ids.*' => 'integer',
        ]);

        $this->reordonnerLecons->execute($section, $validated['lecture_ids']);

        return response()->json(['success' => true]);
    }

    public function moveLecture(Request $request, ModuleLecture $lecture)
    {
        $this->acces->assertEditable($lecture->module);
        $validated = $request->validate([
            'section_id' => 'required|integer',
            'position' => 'required|integer|min:0',
        ]);

        $lecture = $this->deplacerLecon->execute(
            $lecture,
            (int) $validated['section_id'],
            (int) $validated['position'],
        );

        if ($request->wantsJson()) {
            return response()->json(['lecture' => $this->donneesModule->lecture($lecture)]);
        }

        return back()->with('success', 'Leçon déplacée.');
    }

    public function promoteLectureToSection(Request $request, ModuleLecture $lecture)
    {
        $this->acces->assertEditable($lecture->module);
        $section = $this->promouvoirLeconEnChapitre->execute($lecture);

        if ($request->wantsJson()) {
            return response()->json(['section' => $this->donneesModule->section($section)], 201);
        }

        return back()->with('success', 'Leçon transformée en chapitre.');
    }

    public function generateAudioLecture(ModuleLecture $lecture)
    {
        $this->acces->assertEditable($lecture->module);

        try {
            $media = $this->genererAudioLecon->execute($lecture, (int) auth()->id());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['media_id' => $media->id, 'url' => $media->getUrl()]);
    }

    public function uploadImage(Request $request, Module $module)
    {
        $this->acces->assertEditable($module);
        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120']);
        $media = $this->televerserImageModule->execute($module, $request->file('image'));

        return response()->json(['media_id' => $media->id, 'url' => $media->getUrl('display')]);
    }

    public function uploadVideo(Request $request, Module $module)
    {
        $this->acces->assertEditable($module);
        $request->validate(['video' => 'required|mimes:mp4,webm,ogg|max:102400']);
        $media = $this->televerserVideoModule->execute($module, $request->file('video'));

        return response()->json(['url' => $media->getUrl()]);
    }

    public function uploadAudio(Request $request, Module $module)
    {
        $this->acces->assertEditable($module);
        $request->validate(['audio' => 'required|mimes:mp3,wav,ogg,m4a|max:20480']);
        $media = $this->televerserAudioModule->execute($module, $request->file('audio'));

        return response()->json(['media_id' => $media->id, 'url' => $media->getUrl()]);
    }

    public function uploadScorm(Request $request, Module $module)
    {
        $this->acces->assertEditable($module);
        $validated = $request->validate([
            'scorm' => 'required|file|mimes:zip|max:512000',
            'content_block_key' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/'],
        ]);

        $version = $this->televerserScormModule->execute(
            $module,
            $request->file('scorm'),
            $validated['content_block_key'],
        );

        return response()->json([
            'scorm_package_version_id' => $version->id,
            'content_block_key' => $validated['content_block_key'],
            'preview_url' => $version->asset_url,
        ]);
    }

    public function assignGroups(Request $request, Module $module)
    {
        $this->acces->assertCatalogue($module);

        if ($module->estBrouillonCatalogue()) {
            throw ValidationException::withMessages([
                'group_ids' => 'Publiez cette version avant de l’affecter à un groupe.',
            ]);
        }

        abort_unless($module->estPubliee() && $module->status, 403);

        $validated = $request->validate([
            'group_ids' => 'array',
            'group_ids.*' => 'integer|exists:groups,id',
        ]);

        $groupIds = collect($validated['group_ids'] ?? [])
            ->map(fn ($groupId) => (int) $groupId)
            ->filter()
            ->unique()
            ->values();

        if ($groupIds->isNotEmpty()) {
            $groupesEnConflit = Group::query()
                ->whereIn('id', $groupIds->all())
                ->whereHas('modules', function ($query) use ($module): void {
                    $query
                        ->where('modules.id', '<>', $module->id)
                        ->where('modules.is_trainer_authored', false)
                        ->where('modules.catalogue_key', $module->catalogue_key);
                })
                ->orderBy('name')
                ->pluck('name');

            if ($groupesEnConflit->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'group_ids' => 'Ces groupes utilisent déjà une autre version de cette formation : '.$groupesEnConflit->join(', ').'. Utilisez la bascule de version.',
                ]);
            }
        }

        $module->groups()->sync($groupIds->all());

        return back()->with('success', 'Groupes mis à jour.');
    }

    /**
     * @return array<int, mixed>
     */
    private function regleFormateurReferent(): array
    {
        return [
            'nullable',
            'integer',
            Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'formateur')),
        ];
    }

    private function formateursReferents()
    {
        return User::query()->formateurs()->orderBy('name')->get(['id', 'name']);
    }

    private function configurerCommeBrouillonCatalogue(Module $module, ?int $formateurReferentId): void
    {
        $attributes = [
            'is_trainer_authored' => false,
            'status' => false,
        ];

        if (Schema::hasColumn($module->getTable(), 'created_by')) {
            $attributes['created_by'] = (int) auth()->id();
            $attributes['formateur_id'] = $formateurReferentId;
        } elseif ($formateurReferentId !== null) {
            $attributes['formateur_id'] = $formateurReferentId;
        }

        if (Schema::hasColumn($module->getTable(), 'publication_state')) {
            $attributes['publication_state'] = Module::PUBLICATION_DRAFT;
            $attributes['published_at'] = null;
        }

        $module->forceFill($attributes)->save();
    }
}
