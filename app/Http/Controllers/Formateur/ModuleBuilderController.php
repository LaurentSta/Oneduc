<?php

namespace App\Http\Controllers\Formateur;

use App\Domains\ModulesFormateur\Actions\AssignerGroupesModule;
use App\Domains\ModulesFormateur\Actions\ImporterSupportLecon;
use App\Domains\ModulesFormateur\Actions\ModifierPedagogieLecon;
use App\Domains\ModulesFormateur\Actions\CreerChapitre;
use App\Domains\ModulesFormateur\Actions\CreerLecon;
use App\Domains\ModulesFormateur\Actions\CreerModule;
use App\Domains\ModulesFormateur\Actions\DeplacerLecon;
use App\Domains\ModulesFormateur\Actions\DupliquerLecon;
use App\Domains\ModulesFormateur\Actions\DupliquerModuleCatalogue;
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
use App\Domains\ModulesFormateur\Support\AccesModule;
use App\Domains\ModulesFormateur\Support\DonneesModule;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Group;
use App\Models\Module;
use App\Models\ModuleLecture;
use App\Models\ModuleSection;
use App\Services\ConsommationIADashboardService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModuleBuilderController extends Controller
{
    public function __construct(
        private readonly AccesModule $access,
        private readonly DonneesModule $payloads,
        private readonly CreerModule $creerModule,
        private readonly DupliquerModuleCatalogue $dupliquerModuleCatalogue,
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
        private readonly AssignerGroupesModule $assignerGroupesModule,
        private readonly ModifierOptionsModule $modifierOptionsModule,
        private readonly ConsommationIADashboardService $consommationIADashboardService,
    ) {}

    public function index()
    {
        $formateurId = auth()->id();

        $modules = Module::query()
            ->authoredByTrainer($formateurId)
            ->withCount(['sections', 'groups'])
            ->orderByDesc('updated_at')
            ->get();

        return view('formateur.modules-builder.index', [
            'modules' => $modules,
        ]);
    }

    public function consommationIA()
    {
        return view('formateur.modules-builder.consommation-ia', [
            'resume' => $this->consommationIADashboardService->resumePourFormateur(auth()->id()),
        ]);
    }

    public function create()
    {
        return view('formateur.modules-builder.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'module_title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ]);

        $module = $this->creerModule->execute($validated, (int) auth()->id());

        return redirect()
            ->route('formateur.modules.builder.edit', $module)
            ->with('success', 'Formation créée. Ajoutez maintenant des chapitres et des leçons.');
    }

    public function generateStructureIA(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'nullable|string|max:500',
            'document' => 'nullable|file|mimes:pdf,docx,pptx,txt|max:20480',
            'niveau_public' => ['nullable', 'string', Rule::in(['debutant', 'intermediaire', 'avance', 'mixte'])],
            'contexte_public' => 'nullable|string|max:300',
            'contraintes_public' => 'nullable|string|max:300',
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
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return back()->withInput()->with('error', "La génération par l'IA a pris trop de temps. Réessayez, éventuellement avec un thème plus court ou un document plus léger.");
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('formateur.modules.builder.edit', $module)
            ->with('success', "Formation générée par l'IA. Relisez et ajustez le contenu avant de la proposer aux stagiaires.");
    }

    public function duplicate(Module $catalogModule)
    {
        abort_if($catalogModule->is_trainer_authored, 404);
        abort_unless($catalogModule->isVisibleTo(auth()->user()), 404);

        $newModule = $this->dupliquerModuleCatalogue->execute($catalogModule, (int) auth()->id());

        return redirect()
            ->route('formateur.modules.builder.edit', $newModule)
            ->with('success', 'Formation dupliquée. Vous pouvez maintenant la personnaliser librement.');
    }

    public function edit(Module $module)
    {
        $this->access->assertOwner($module);

        $module->load(['sections.lectures' => fn ($query) => $query->orderBy('position'), 'groups']);

        $accessibleGroups = Group::query()
            ->accessibleByTrainer(auth()->id())
            ->orderBy('name')
            ->get();

        $categories = Category::orderBy('category_name')->get();

        return view('formateur.modules-builder.edit', [
            'module' => $module,
            'accessibleGroups' => $accessibleGroups,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Module $module)
    {
        $this->access->assertOwner($module);

        $validated = $request->validate([
            'module_title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ]);

        $module->update([
            'module_title' => $validated['module_title'],
            'module_name' => $validated['module_title'],
            'description' => $validated['description'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Formation mise à jour.');
    }

    public function updateOptions(Request $request, Module $module)
    {
        $this->access->assertOwner($module);

        $validated = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
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
        $validated['status'] = $request->boolean('status');

        $this->modifierOptionsModule->execute(
            $module,
            $validated,
            $request->file('module_image'),
            $request->file('header_image'),
            $request->file('module_video_file'),
        );

        return back()->with('success', 'Options de la formation mises à jour.');
    }

    public function destroy(Module $module)
    {
        $this->access->assertOwner($module);

        $module->delete();

        return redirect()->route('formateur.modules.builder.index')->with('success', 'Formation supprimée.');
    }

    public function storeSection(Request $request, Module $module)
    {
        $this->access->assertOwner($module);

        $validated = $request->validate([
            'section_title' => 'required|string|max:255',
        ]);

        $section = $this->creerChapitre->execute($module, $validated['section_title']);

        if ($request->wantsJson()) {
            return response()->json([
                'section' => $this->payloads->section($section),
            ], 201);
        }

        return back()->with('success', 'Chapitre ajouté.');
    }

    public function updateSection(Request $request, ModuleSection $section)
    {
        $this->access->assertOwner($section->module);

        $validated = $request->validate([
            'section_title' => 'required|string|max:255',
        ]);

        $section->update(['section_title' => $validated['section_title']]);

        if ($request->wantsJson()) {
            return response()->json([
                'section' => $this->payloads->section($section),
            ]);
        }

        return back()->with('success', 'Chapitre mis à jour.');
    }

    public function destroySection(Request $request, ModuleSection $section)
    {
        $this->access->assertOwner($section->module);

        if ($section->lectures()->exists()) {
            return response()->json([
                'message' => 'Ce chapitre contient des leçons. Déplacez-les ou supprimez-les avant de supprimer le chapitre.',
            ], 422);
        }

        $section->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Chapitre supprimé.');
    }

    public function reorderSections(Request $request, Module $module)
    {
        $this->access->assertOwner($module);

        $validated = $request->validate([
            'section_ids' => 'required|array',
            'section_ids.*' => 'integer',
        ]);

        $this->reordonnerChapitres->execute($module, $validated['section_ids']);

        return response()->json(['success' => true]);
    }

    public function storeLecture(Request $request, ModuleSection $section)
    {
        $this->access->assertOwner($section->module);

        $validated = $request->validate([
            'lecture_title' => 'required|string|max:255',
            'content_blocks' => 'nullable|string',
        ]);

        $lecture = $this->creerLecon->execute(
            $section,
            $validated['lecture_title'],
            $validated['content_blocks'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'lecture' => $this->payloads->lecture($lecture),
            ], 201);
        }

        return back()->with('success', 'Leçon ajoutée.');
    }

    public function generateLectureIA(Request $request, ModuleSection $section)
    {
        $this->access->assertOwner($section->module);

        $validated = $request->validate([
            'document' => 'required|file|mimes:pdf,docx,pptx,txt|max:20480',
            'lecture_title' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->genererLeconIA->execute($request->file('document'), $section->module, (int) auth()->id());
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return back()->with('error', "La génération par l'IA a pris trop de temps. Réessayez.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->creerLecon->execute(
            $section,
            ($validated['lecture_title'] ?? null) ?: $result['title'],
            $result['blocks_json']
        );

        return redirect()
            ->route('formateur.modules.builder.edit', $section->module)
            ->with('success', "Leçon générée par l'IA. Relisez et ajustez le contenu avant de la proposer aux stagiaires.");
    }

    public function updateLecture(Request $request, ModuleLecture $lecture)
    {
        $this->access->assertOwner($lecture->module);

        $validated = $request->validate([
            'lecture_title' => 'required|string|max:255',
            'content_blocks' => 'nullable|string',
        ]);

        $lecture = $this->modifierLecon->execute(
            $lecture,
            $validated['lecture_title'],
            $validated['content_blocks'] ?? null,
            $request->has('content_blocks')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'lecture' => $this->payloads->lecture($lecture),
                'saved_at' => now()->toIso8601String(),
            ]);
        }

        return back()->with('success', 'Leçon mise à jour.');
    }

    public function duplicateLecture(Request $request, ModuleLecture $lecture)
    {
        $this->access->assertOwner($lecture->module);

        $duplicate = $this->dupliquerLecon->execute($lecture);

        if ($request->wantsJson()) {
            return response()->json([
                'lecture' => $this->payloads->lecture($duplicate),
            ], 201);
        }

        return back()->with('success', 'Leçon dupliquée.');
    }

    public function destroyLecture(Request $request, ModuleLecture $lecture)
    {
        $this->access->assertOwner($lecture->module);

        $lecture->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Leçon supprimée.');
    }

    public function reorderLectures(Request $request, ModuleSection $section)
    {
        $this->access->assertOwner($section->module);

        $validated = $request->validate([
            'lecture_ids' => 'required|array',
            'lecture_ids.*' => 'integer',
        ]);

        $this->reordonnerLecons->execute($section, $validated['lecture_ids']);

        return response()->json(['success' => true]);
    }

    public function editLecture(ModuleLecture $lecture)
    {
        $this->access->assertOwner($lecture->module);

        return view('formateur.modules-builder.lecture-edit', $this->payloads->conception($lecture));
    }

    public function modifierPedagogie(Request $request, ModuleLecture $lecture, ModifierPedagogieLecon $action)
    {
        $this->access->assertOwner($lecture->module);
        $action->execute($lecture, $request->all());

        return back()->with('success', 'Réglages pédagogiques enregistrés.');
    }

    public function importerSupport(Request $request, ModuleLecture $lecture, ImporterSupportLecon $action)
    {
        $this->access->assertOwner($lecture->module);
        $action->execute($lecture, $request);

        return back()->with('success', 'Support importé. Les présentations sont converties en arrière-plan.');
    }

    public function relancerSlides(ModuleLecture $lecture, ImporterSupportLecon $action)
    {
        $this->access->assertOwner($lecture->module);
        $action->relancer($lecture);

        return back()->with('success', 'Conversion relancée.');
    }

    public function preview(Request $request, Module $module)
    {
        $this->access->assertOwner($module);
        $module->load(['sections.lectures' => fn ($query) => $query->orderBy('position')->orderBy('id')]);
        $section = $module->sections->firstWhere('id', $request->integer('section')) ?? $module->sections->first();
        $lecture = $section?->lectures->firstWhere('id', $request->integer('lecture')) ?? $section?->lectures->first();
        if (! $lecture) {
            return back()->with('error', 'Ajoutez une leçon pour ouvrir l’aperçu.');
        }

        return view('shared.formations-constructeur.apercu', $this->payloads->apercu($lecture));
    }

    public function apercuScorm(Request $request, ModuleLecture $lecture)
    {
        $this->access->assertOwner($lecture->module);

        return view('shared.scorm-apercu', [
            'lecture' => $lecture,
            'scormUrl' => $this->payloads->urlScormApercu($lecture, $request->query('bloc')),
        ]);
    }

    public function generateAudioLecture(ModuleLecture $lecture)
    {
        $this->access->assertOwner($lecture->module);

        try {
            $media = $this->genererAudioLecon->execute($lecture, (int) auth()->id());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'media_id' => $media->id,
            'url' => $media->getUrl(),
        ]);
    }

    public function moveLecture(Request $request, ModuleLecture $lecture)
    {
        $this->access->assertOwner($lecture->module);

        $validated = $request->validate([
            'section_id' => 'required|integer',
            'position' => 'required|integer|min:0',
        ]);

        $lecture = $this->deplacerLecon->execute(
            $lecture,
            (int) $validated['section_id'],
            (int) $validated['position']
        );

        if ($request->wantsJson()) {
            return response()->json([
                'lecture' => $this->payloads->lecture($lecture),
            ]);
        }

        return back()->with('success', 'Leçon déplacée.');
    }

    public function promoteLectureToSection(Request $request, ModuleLecture $lecture)
    {
        $this->access->assertOwner($lecture->module);

        $section = $this->promouvoirLeconEnChapitre->execute($lecture);

        if ($request->wantsJson()) {
            return response()->json([
                'section' => $this->payloads->section($section),
            ], 201);
        }

        return back()->with('success', 'Leçon transformée en chapitre.');
    }

    public function uploadImage(Request $request, Module $module)
    {
        $this->access->assertOwner($module);

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ]);

        $media = $this->televerserImageModule->execute($module, $request->file('image'));

        return response()->json([
            'media_id' => $media->id,
            'url' => $media->getUrl('display'),
        ]);
    }

    public function uploadVideo(Request $request, Module $module)
    {
        $this->access->assertOwner($module);

        $request->validate([
            'video' => 'required|mimes:mp4,webm,ogg|max:102400',
        ]);

        $media = $this->televerserVideoModule->execute($module, $request->file('video'));

        return response()->json([
            'url' => $media->getUrl(),
        ]);
    }

    public function uploadAudio(Request $request, Module $module)
    {
        $this->access->assertOwner($module);

        $request->validate([
            'audio' => 'required|mimes:mp3,wav,ogg,m4a|max:20480',
        ]);

        $media = $this->televerserAudioModule->execute($module, $request->file('audio'));

        return response()->json([
            'media_id' => $media->id,
            'url' => $media->getUrl(),
        ]);
    }

    public function uploadScorm(Request $request, Module $module)
    {
        $this->access->assertOwner($module);

        $validated = $request->validate([
            'scorm' => 'required|file|mimes:zip|max:512000',
            'content_block_key' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/'],
        ]);

        $version = $this->televerserScormModule->execute(
            $module,
            $request->file('scorm'),
            $validated['content_block_key']
        );

        return response()->json([
            'scorm_package_version_id' => $version->id,
            'content_block_key' => $validated['content_block_key'],
            'preview_url' => $version->asset_url,
        ]);
    }

    public function assignGroups(Request $request, Module $module)
    {
        $this->access->assertOwner($module);

        $validated = $request->validate([
            'group_ids' => 'array',
            'group_ids.*' => 'integer',
        ]);

        $this->assignerGroupesModule->execute($module, $validated['group_ids'] ?? [], (int) auth()->id());

        return back()->with('success', 'Groupes mis à jour.');
    }
}
