<?php

use App\Jobs\ConvertLectureSlides;
use App\Models\Category;
use App\Models\Competency;
use App\Models\ComponentFinderActivity;
use App\Models\ContentBlockScormScore;
use App\Models\LessonResource;
use App\Models\Module;
use App\Models\OutilEtat;
use App\Models\Progression;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\Scorm\ScormImporter;
use App\Support\LearningAssetPath;
use App\Support\Outils\EtatsOutils;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function contexteConceptionUnifiee(string $role): array
{
    $utilisateur = User::factory()->create(['role' => $role]);
    $categorie = Category::create(['category_name' => 'Inclusion numérique', 'category_slug' => 'inclusion-'.Str::random(8)]);
    $sousCategorie = SubCategory::create(['category_id' => $categorie->id, 'subcategory_name' => 'Démarches', 'subcategory_slug' => 'demarches-'.Str::random(8)]);
    $module = Module::create([
        'category_id' => $categorie->id, 'subcategory_id' => $sousCategorie->id,
        'module_title' => 'Communiquer par courriel', 'module_name' => 'Communiquer par courriel',
        'module_name_slug' => 'courriel-'.Str::random(8), 'created_by' => $utilisateur->id,
        'formateur_id' => $role === 'formateur' ? $utilisateur->id : null,
        'is_trainer_authored' => $role === 'formateur',
        'publication_state' => 'draft', 'status' => $role === 'formateur',
    ]);
    $chapitre = $module->sections()->create(['section_title' => 'Premiers échanges', 'position' => 0]);
    $lecon = $chapitre->lectures()->create([
        'module_id' => $module->id, 'lecture_title' => 'Envoyer une pièce jointe', 'position' => 0,
        'content_type' => 'blocks', 'content_blocks' => [['type' => 'text', 'html' => '<p>Un document à envoyer.</p>']],
        'quiz_enabled' => false, 'quiz_questions_per_attempt' => 0,
    ]);

    return [$utilisateur, $module, $chapitre, $lecon, $role === 'admin' ? 'admin.formations.constructeur' : 'formateur.modules.builder'];
}

function donneesPedagogieConception(array $remplacements = []): array
{
    return array_replace([
        'duration' => 12, 'quiz_enabled' => false, 'quiz_questions_per_attempt' => 0,
        'objectives_present' => 1, 'objectives' => [],
    ], $remplacements);
}

it('réunit le plan le contenu les objectifs la validation et les ressources pour chaque concepteur', function ($role) {
    [$utilisateur, $module, $chapitre, $lecon, $routes] = contexteConceptionUnifiee($role);
    $this->actingAs($utilisateur)->get(route($routes.'.lectures.edit', $lecon))
        ->assertOk()->assertSee('Concevoir une leçon')->assertSee('Plan de la formation')
        ->assertSee('Intention pédagogique')->assertSee('Validation de la leçon')
        ->assertSee('Ressources de la formation')->assertSee('data-block-editor', false)
        ->assertSee(route($routes.'.preview', ['module' => $module, 'section' => $chapitre->id, 'lecture' => $lecon->id]), false);
})->with(['admin', 'formateur']);

it('conserve les identifiants des objectifs et synchronise leurs compétences sans modifier le contenu', function ($role) {
    [$utilisateur, $module, $chapitre, $lecon, $routes] = contexteConceptionUnifiee($role);
    $objectif = $lecon->objectives()->create(['title' => 'Ancien objectif', 'position' => 1]);
    $retire = $lecon->objectives()->create(['title' => 'Objectif retiré', 'position' => 2]);
    $competence = Competency::create(['code' => 'COURRIEL-'.Str::random(5), 'label' => 'Envoyer un document', 'is_active' => true]);

    $this->actingAs($utilisateur)->put(route($routes.'.lectures.pedagogie', $lecon), donneesPedagogieConception([
        'objectives' => [
            ['id' => $objectif->id, 'title' => 'Joindre le bon fichier', 'description' => 'Vérifier le nom du document.', 'competency_ids' => [$competence->id]],
            ['title' => 'Vérifier le destinataire'],
        ],
    ]))->assertRedirect()->assertSessionHasNoErrors();
    expect($lecon->fresh()->duration)->toBe(12)
        ->and($lecon->fresh()->content_blocks)->toBe($lecon->content_blocks)
        ->and($objectif->fresh()->title)->toBe('Joindre le bon fichier')
        ->and($objectif->fresh()->competencies->pluck('id')->all())->toBe([$competence->id])
        ->and($lecon->objectives()->count())->toBe(2);
    $this->assertDatabaseMissing('lecture_objectives', ['id' => $retire->id]);
})->with(['admin', 'formateur']);

it('refuse de déplacer ou modifier un objectif provenant d’une autre leçon', function ($role) {
    [$utilisateur, $module, $chapitre, $lecon, $routes] = contexteConceptionUnifiee($role);
    $autreLecon = $chapitre->lectures()->create(['module_id' => $module->id, 'lecture_title' => 'Autre leçon', 'content_type' => 'blocks', 'position' => 1]);
    $objectif = $autreLecon->objectives()->create(['title' => 'À conserver', 'position' => 1]);
    $this->actingAs($utilisateur)->putJson(route($routes.'.lectures.pedagogie', $lecon), donneesPedagogieConception([
        'objectives' => [['id' => $objectif->id, 'title' => 'Modification interdite']],
    ]))->assertUnprocessable()->assertJsonValidationErrors('objectives.0.id');
    expect($objectif->fresh()->title)->toBe('À conserver')->and($lecon->fresh()->duration)->toBeNull();
})->with(['admin', 'formateur']);

it('vérifie les questions actives avant de permettre un quiz de validation', function ($role) {
    [$utilisateur, $module, $chapitre, $lecon, $routes] = contexteConceptionUnifiee($role);
    $url = route($routes.'.lectures.pedagogie', $lecon);
    $donnees = donneesPedagogieConception(['quiz_enabled' => true, 'quiz_questions_per_attempt' => 1]);
    $this->actingAs($utilisateur)->putJson($url, $donnees)->assertUnprocessable()->assertJsonValidationErrors('quiz_questions_per_attempt');
    $question = QuizQuestion::create(['lecture_id' => $lecon->id, 'question_text' => 'Quel fichier ?', 'type' => 'single', 'is_active' => false, 'points' => 1]);
    $this->putJson($url, $donnees)->assertUnprocessable();
    $question->update(['is_active' => true]);
    $this->put($url, $donnees)->assertRedirect()->assertSessionHasNoErrors();
    expect($lecon->fresh()->quiz_enabled)->toBeTrue()->and($lecon->fresh()->quiz_questions_per_attempt)->toBe(1);
    $this->put($url, donneesPedagogieConception())->assertRedirect()->assertSessionHasNoErrors();
    expect($lecon->fresh()->quiz_enabled)->toBeFalse()->and($lecon->fresh()->content_blocks)->toBe($lecon->content_blocks);
})->with(['admin', 'formateur']);

it('protège les créations des autres formateurs et les versions officielles publiées', function () {
    [$formateur, $module, $chapitre, $lecon, $routes] = contexteConceptionUnifiee('formateur');
    $autre = User::factory()->create(['role' => 'formateur']);
    $this->actingAs($autre)->putJson(route($routes.'.lectures.pedagogie', $lecon), donneesPedagogieConception())->assertForbidden();
    $this->get(route($routes.'.preview', $module))->assertForbidden();
    $this->get(route($routes.'.lectures.apercu-scorm', $lecon))->assertForbidden();
    $this->post(route($routes.'.lectures.support.store', $lecon))->assertForbidden();

    [$admin, $officiel, $section, $leconOfficielle, $routesAdmin] = contexteConceptionUnifiee('admin');
    $officiel->update(['publication_state' => 'published', 'status' => true]);
    $this->actingAs($admin)->putJson(route($routesAdmin.'.lectures.pedagogie', $leconOfficielle), donneesPedagogieConception())->assertForbidden();
    $this->post(route($routesAdmin.'.lectures.support.store', $leconOfficielle))->assertForbidden();
    $this->post(route($routesAdmin.'.lectures.ressources.store', $leconOfficielle))->assertForbidden();
    $this->get(route($routesAdmin.'.lectures.edit', $leconOfficielle))->assertOk()->assertSee('lecture seule');
})->group('autorisation');

it('réoriente les anciens liens admin vers le constructeur commun', function () {
    [$admin, $module, $chapitre, $lecon] = contexteConceptionUnifiee('admin');
    $this->actingAs($admin)->get(route('admin.modules.lecture.add', $module))->assertRedirect(route('admin.formations.constructeur.edit', $module));
    $this->get(route('admin.lectures.edit', $lecon))->assertRedirect(route('admin.formations.constructeur.lectures.edit', $lecon));
});

it('prévisualise les activités d’un brouillon sans créer de progression ni de tentative', function ($role) {
    [$utilisateur, $module, $chapitre, $lecon, $routes] = contexteConceptionUnifiee($role);
    $lecon->update(['content_blocks' => [...$lecon->content_blocks, ['type' => 'divider', 'mode' => 'reveal'], ['type' => 'text', 'html' => '<p>Vérifiez votre document.</p>']]]);
    $question = QuizQuestion::create(['lecture_id' => $lecon->id, 'question_text' => 'Quel bouton permet de joindre un fichier ?', 'type' => 'single', 'is_active' => true, 'points' => 1]);
    $question->options()->createMany([['option_text' => 'Joindre', 'is_correct' => true, 'position' => 1], ['option_text' => 'Supprimer', 'is_correct' => false, 'position' => 2]]);
    $this->actingAs($utilisateur)->get(route($routes.'.preview', ['module' => $module, 'lecture' => $lecon->id, 'section' => $chapitre->id]))
        ->assertOk()->assertSee('Aperçu apprenant')->assertSee('Quel bouton permet')->assertSee('Continuer');
    expect(Progression::where('user_id', $utilisateur->id)->count())->toBe(0)
        ->and(QuizAttempt::where('user_id', $utilisateur->id)->count())->toBe(0)
        ->and(ContentBlockScormScore::where('user_id', $utilisateur->id)->count())->toBe(0);
})->with(['admin', 'formateur']);

it('encapsule l’aperçu SCORM dans un contexte sans enregistrement y compris sur un brouillon', function ($role) {
    [$utilisateur, $module, $chapitre, $lecon, $routes] = contexteConceptionUnifiee($role);
    $module->update(['status' => false]);
    $lecon->update(['content_type' => 'scorm', 'scorm_path' => 'modules/exemple/index.html']);
    $this->actingAs($utilisateur)->get(route($routes.'.lectures.apercu-scorm', $lecon))
        ->assertOk()->assertSee('preview: true', false)->assertSee('embedded: true', false);
    $this->get(route($routes.'.preview', $module))->assertOk()->assertSee(route($routes.'.lectures.apercu-scorm', $lecon), false);
})->with(['admin', 'formateur']);

it('importe une présentation dans une leçon vide sans écraser des blocs existants', function ($role) {
    Queue::fake();
    Storage::fake('local');
    [$utilisateur, $module, $chapitre, $lecon, $routes] = contexteConceptionUnifiee($role);
    $url = route($routes.'.lectures.support.store', $lecon);
    $fichier = UploadedFile::fake()->create('presentation.pdf', 20, 'application/pdf');
    $this->actingAs($utilisateur)->postJson($url, ['support_type' => 'slides', 'support_file' => $fichier])->assertUnprocessable();
    expect($lecon->fresh()->content_type)->toBe('blocks');
    $lecon->update(['content_blocks' => []]);
    $this->post($url, ['support_type' => 'slides', 'support_file' => $fichier])->assertRedirect()->assertSessionHasNoErrors();
    expect($lecon->fresh()->content_type)->toBe('slides')->and($lecon->fresh()->slides_status)->toBe('pending');
    Storage::disk('local')->assertExists($lecon->fresh()->slides_source_path);
    Queue::assertPushed(ConvertLectureSlides::class);
    $this->postJson($url, ['support_type' => 'slides', 'support_file' => $fichier])->assertUnprocessable();
})->with(['admin', 'formateur']);

it('importe le SCORM dans un nouvel emplacement pour conserver le paquet source', function ($role) {
    [$utilisateur, $module, $chapitre, $lecon, $routes] = contexteConceptionUnifiee($role);
    $lecon->update(['content_type' => 'scorm', 'scorm_path' => 'modules/source/index.html']);
    $this->mock(ScormImporter::class, function ($mock) use ($lecon) {
        $mock->shouldReceive('importToFolder')->once()->withArgs(function ($fichier, $dossier) use ($lecon) {
            return $fichier instanceof UploadedFile && str_starts_with($dossier, LearningAssetPath::lessonImportFolder($lecon->id).'/import_');
        })->andReturn((object) ['relative_index_path' => 'modules/nouveau/index.html', 'package_id' => null, 'version_id' => null]);
    });
    $this->actingAs($utilisateur)->post(route($routes.'.lectures.support.store', $lecon), [
        'support_type' => 'scorm', 'support_file' => UploadedFile::fake()->create('contenu.zip', 20, 'application/zip'),
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect($lecon->fresh()->scorm_path)->toBe('modules/nouveau/index.html')->and($lecon->fresh()->use_active_scorm_version)->toBeFalse();
})->with(['admin', 'formateur']);

it('gère les ressources du brouillon admin en empêchant toute modification d’une autre formation', function () {
    Storage::fake('public');
    [$admin, $module, $chapitre, $lecon, $routes] = contexteConceptionUnifiee('admin');
    $this->actingAs($admin)->post(route($routes.'.lectures.ressources.store', $lecon), [
        'title' => 'Fiche mémo', 'resource_file' => UploadedFile::fake()->create('memo.pdf', 10, 'application/pdf'), 'is_visible_to_stagiaire' => 1,
    ])->assertRedirect()->assertSessionHasNoErrors();
    $ressource = $module->moduleResources()->firstOrFail();
    Storage::disk('public')->assertExists($ressource->file_path);
    $this->put(route($routes.'.lectures.ressources.visibilite', ['lecture' => $lecon, 'resource' => $ressource]), ['is_visible_to_stagiaire' => 0])->assertRedirect();
    expect($ressource->fresh()->is_visible_to_stagiaire)->toBeFalse();
    [, $autreModule, , $autreLecon] = contexteConceptionUnifiee('admin');
    $this->delete(route($routes.'.lectures.ressources.destroy', ['lecture' => $autreLecon, 'resource' => $ressource]))->assertNotFound();
    expect(LessonResource::whereKey($ressource->id)->exists())->toBeTrue();
    $this->delete(route($routes.'.lectures.ressources.destroy', ['lecture' => $lecon, 'resource' => $ressource]))->assertRedirect();
    Storage::disk('public')->assertMissing($ressource->file_path);
});

it('enregistre une activité outil dans la leçon en nettoyant son contenu', function ($role) {
    [$utilisateur, , , $lecon, $routes] = contexteConceptionUnifiee($role);

    $this->actingAs($utilisateur)->putJson(route($routes.'.lectures.update', $lecon), [
        'lecture_title' => $lecon->lecture_title,
        'content_blocks' => json_encode([
            ['type' => 'outil', 'outil' => 'cartes-retourner', 'obligatoire' => 'true', 'group_id' => 7, 'configuration' => [
                'titre' => 'Vocabulaire', 'access_code' => 'ABC123',
                'cartes' => [
                    ['recto' => 'La balise <b>', 'verso' => 'Met le texte en gras', 'image' => 'x'],
                    ['recto' => '  ', 'verso' => ''],
                    'pas une carte',
                ],
            ]],
            ['type' => 'outil', 'outil' => 'buzzer', 'configuration' => ['titre' => 'Outil non intégrable']],
            ['type' => 'outil', 'outil' => 'vrai-faux', 'configuration' => ['affirmations' => []]],
        ]),
    ])->assertOk();

    expect($lecon->fresh()->content_blocks)->toEqual([[
        'type' => 'outil', 'outil' => 'cartes-retourner',
        'configuration' => ['titre' => 'Vocabulaire', 'consigne' => '', 'cartes' => [['recto' => 'La balise <b>', 'verso' => 'Met le texte en gras']]],
        'obligatoire' => true,
    ]]);
})->with(['admin', 'formateur']);

it('affiche l’activité outil au stagiaire, la compte comme étape obligatoire et la masque si l’outil est désactivé', function () {
    [$utilisateur, $module, $chapitre, $lecon, $routes] = contexteConceptionUnifiee('formateur');
    $lecon->update(['content_blocks' => [[
        'type' => 'outil', 'outil' => 'vrai-faux', 'obligatoire' => true,
        'configuration' => ['titre' => 'Mots de passe', 'consigne' => '', 'affirmations' => [
            ['texte' => 'Un mot de passe court suffit.', 'reponse' => false, 'explication' => 'Visez douze caractères.'],
            ['texte' => '', 'reponse' => true, 'explication' => 'Affirmation encore incomplète.'],
        ]],
    ]]]);
    $apercu = route($routes.'.preview', ['module' => $module, 'section' => $chapitre->id, 'lecture' => $lecon->id]);

    $this->actingAs($utilisateur)->get($apercu)->assertOk()
        ->assertSee('Un mot de passe court suffit.')->assertSee('Visez douze caractères.')
        ->assertDontSee('Affirmation encore incomplète.')
        ->assertSee('outilsRestants: 1', false);

    OutilEtat::updateOrCreate(['cle' => 'vraifaux'], ['actif' => false]);
    EtatsOutils::viderCache();

    // Désactivé par l'admin : ni affiché, ni bloquant pour la suite de la leçon.
    $this->get($apercu)->assertOk()
        ->assertDontSee('Un mot de passe court suffit.')
        ->assertSee('outilsRestants: 0', false);
});

it('relie la leçon à une zone de clic de la bibliothèque : modifiée dans l’outil, elle l’est dans la leçon', function () {
    [$utilisateur, $module, $chapitre, $lecon, $routes] = contexteConceptionUnifiee('formateur');
    $zones = [
        ['label' => 'Processeur', 'description' => 'Le cerveau de la machine.', 'shape' => 'circle', 'x' => 10, 'y' => 20, 'w' => 30, 'h' => 15],
        ['label' => 'Mémoire', 'description' => '', 'shape' => 'square', 'x' => 50, 'y' => 20, 'w' => 20, 'h' => 10],
    ];
    $zoneDeClic = ComponentFinderActivity::create(['formateur_id' => $utilisateur->id, 'title' => 'Carte mère', 'image_path' => 'component_finder/abc123.png', 'zones' => $zones]);
    $celleDUnCollegue = ComponentFinderActivity::create(['formateur_id' => User::factory()->create(['role' => 'formateur'])->id, 'title' => 'Autre', 'image_path' => 'component_finder/def456.png', 'zones' => $zones]);

    $this->actingAs($utilisateur)->putJson(route($routes.'.lectures.update', $lecon), [
        'lecture_title' => $lecon->lecture_title,
        'content_blocks' => json_encode([
            ['type' => 'outil', 'outil' => 'composants', 'activite_id' => $zoneDeClic->id, 'obligatoire' => true, 'configuration' => ['zones' => $zones], 'activite' => ['titre' => 'x']],
            ['type' => 'outil', 'outil' => 'composants', 'activite_id' => $celleDUnCollegue->id],
            ['type' => 'outil', 'outil' => 'composants', 'activite_id' => null],
        ]),
    ])->assertOk();

    // Le bloc ne garde qu'un lien, et seulement vers une zone de clic du concepteur.
    expect($lecon->fresh()->content_blocks)->toEqual([
        ['type' => 'outil', 'outil' => 'composants', 'activite_id' => $zoneDeClic->id, 'obligatoire' => true],
    ]);

    $apercu = route($routes.'.preview', ['module' => $module, 'section' => $chapitre->id, 'lecture' => $lecon->id]);
    $this->get($apercu)->assertOk()
        ->assertSee('Processeur')->assertSee('Agrandir')
        ->assertSee('aria-label="Aide"', false)->assertSee('@click="open = true"', false)
        ->assertDontSee('Carte mère')
        ->assertSee('/media/storage/component_finder/abc123.png', false)
        ->assertSee('outilsRestants: 1', false);

    $zones[0]['label'] = 'Microprocesseur';
    $this->put(route('formateur.composants.activities.update', $zoneDeClic), ['title' => 'Carte mère', 'zones' => json_encode($zones)])->assertRedirect();
    $this->get($apercu)->assertOk()->assertSee('Microprocesseur')->assertDontSee('Processeur');

    // Tant qu'une leçon l'utilise, la zone de clic ne peut pas être supprimée.
    $this->get(route('formateur.composants.index'))->assertOk()->assertSee('Utilisée dans 1 leçon')->assertSee($lecon->lecture_title);
    $this->delete(route('formateur.composants.activities.destroy', $zoneDeClic))->assertSessionHasErrors('activity');
    expect($zoneDeClic->fresh())->not->toBeNull();
});
