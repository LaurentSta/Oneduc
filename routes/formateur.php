<?php

use App\Http\Controllers\Backend\ModuleController;
use App\Http\Controllers\Formateur\EmargementController;
use App\Http\Controllers\Formateur\FormateurModuleController;
use App\Http\Controllers\Formateur\FormateurProfileController;
use App\Http\Controllers\Formateur\FormateurStagiaireController;
use App\Http\Controllers\Formateur\GroupeController;
use App\Http\Controllers\Formateur\GroupeModuleLessonController;
use App\Http\Controllers\Formateur\GroupeOutilController;
use App\Http\Controllers\Formateur\GroupeWordCloudController;
use App\Http\Controllers\Formateur\GroupQuizSessionController;
use App\Http\Controllers\Formateur\LessonResourceController;
use App\Http\Controllers\Formateur\LiveQuizSessionController;
use App\Http\Controllers\Formateur\MesParcoursController;
use App\Http\Controllers\Formateur\ModuleBuilderController;
use App\Http\Controllers\Formateur\ModuleQuizBankController;
use App\Http\Controllers\Formateur\ObjectiveController;
use App\Http\Controllers\Formateur\OnboardingController;
use App\Http\Controllers\Formateur\OutilsBuzzerController;
use App\Http\Controllers\Formateur\OutilsComposantController;
use App\Http\Controllers\Formateur\OutilsEchelleController;
use App\Http\Controllers\Formateur\OutilsLiveQuizController;
use App\Http\Controllers\Formateur\OutilsNumeriquesController;
use App\Http\Controllers\Formateur\OutilsPagesCollaborativesController;
use App\Http\Controllers\Formateur\OutilsQuizQuestionsController;
use App\Http\Controllers\Formateur\OutilsSondageController;
use App\Http\Controllers\Formateur\OutilsVraiFauxController;
use App\Http\Controllers\Formateur\ParcoursController;
use App\Http\Controllers\Formateur\PollQuestionnaireController;
use App\Http\Controllers\Formateur\ProgressionController;
use App\Http\Controllers\Formateur\ProgressionGroupesController;
use App\Http\Controllers\Formateur\ProgressionModulesController;
use App\Http\Controllers\Formateur\ProgressionStagiaireController;
use App\Http\Controllers\Formateur\ProgressionStagiairesController;
use App\Http\Controllers\Formateur\OutilsPowerPointController;
use App\Http\Controllers\Formateur\QuestionWallController;
use App\Http\Controllers\Formateur\QuizQuestionController as FormateurQuizQuestionController;
use App\Http\Controllers\Formateur\QuestionnaireController;
use App\Http\Controllers\Formateur\RoueAleatoireController;
use App\Http\Controllers\Formateur\WhiteboardController;
use App\Http\Controllers\Formateur\WordCloudController as FormateurWordCloudController;
use App\Http\Controllers\FormateurController;
use App\Http\Controllers\Stagiaire\QuizController;
// use App\Http\Controllers\Stagiaire\QuizAttemptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:formateur', 'association.member'])
    ->prefix('formateur')
    ->name('formateur.')
    ->group(function () {

        // 🖥️ Dashboard & profil formateur
        Route::get('/', [FormateurController::class, 'FormateurDashboard'])->name('dashboard');
        Route::get('/dashboard/activity', [FormateurController::class, 'dashboardActivity'])->name('dashboard.activity');
        Route::get('/parcours/{step?}', [OnboardingController::class, 'show'])->name('onboarding.show');
        Route::prefix('/parcours-formateur')
            ->name('parcours.')
            ->group(function () {
                Route::get('/', [ParcoursController::class, 'index'])->name('index');
                Route::post('/modules/{module}/questionnaire', [ParcoursController::class, 'submitModuleQuestionnaire'])
                    ->middleware('throttle:10,1')
                    ->name('questionnaire.submit');
                Route::get('/modules/{module}', [ParcoursController::class, 'showModule'])->name('modules.show');
                Route::get('/modules/{module}/introduction', [ParcoursController::class, 'showModuleIntroduction'])->name('modules.introduction');
                Route::get('/modules/{module}/chapitres/{chapter}', [ParcoursController::class, 'showChapter'])->name('chapters.show');
                Route::get('/modules/{module}/chapitres/{chapter}/lecons/{lesson}/parties/{part}', [ParcoursController::class, 'showLessonPart'])->name('lessons.part');
                Route::post('/modules/{module}/chapitres/{chapter}/lecons/{lesson}/parties/{part}/validation', [ParcoursController::class, 'completeGuidedLessonPart'])->name('lessons.part.complete');
                Route::get('/modules/{module}/chapitres/{chapter}/lecons/{lesson}', [ParcoursController::class, 'showLesson'])->name('lessons.show');
                Route::get('/modules/{module}/chapitres/{chapter}/lecons/{lesson}/activites/{activity}', [ParcoursController::class, 'showActivity'])->name('activities.show');
                Route::post('/modules/{module}/chapitres/{chapter}/lecons/{lesson}/activites/{activity}', [ParcoursController::class, 'submitActivity'])->name('activities.submit');
            });
        // Profil & sécurité
        Route::get('/profile', [FormateurProfileController::class, 'FormateurProfile'])->name('profile');
        Route::get('/parametre', [FormateurProfileController::class, 'FormateurParametre'])->name('parametre');
        Route::get('/documentation', fn () => view('formateur.documentation'))->name('documentation');
        Route::post('/profil/store', [FormateurProfileController::class, 'FormateurProfilStore'])->name('profil.store');
        Route::get('/securite', [FormateurProfileController::class, 'showFormateurSecurite'])->name('securite.show');
        Route::post('/securite', [FormateurProfileController::class, 'FormateurSecurite'])->name('securite');
        Route::delete('/compte', [FormateurProfileController::class, 'destroyOwnAccount'])->name('account.destroy');

        // 👤 Stagiaires
        Route::get('/stagiaires', [FormateurStagiaireController::class, 'indexStagiaires'])->name('stagiaires.index');
        Route::get('/stagiaires/create', [FormateurStagiaireController::class, 'createStagiaire'])->name('stagiaires.create');
        Route::post('/stagiaires', [FormateurStagiaireController::class, 'storeStagiaire'])->name('stagiaires.store');
        Route::get('/stagiaires/{id}/edit', [FormateurStagiaireController::class, 'editStagiaire'])->name('stagiaires.edit');
        Route::put('/stagiaires/{id}', [FormateurStagiaireController::class, 'updateStagiaire'])->name('stagiaires.update');
        Route::delete('/stagiaires/{id}', [FormateurStagiaireController::class, 'destroyStagiaire'])->name('stagiaires.destroy');
        Route::post('/stagiaires/{id}/message', [FormateurStagiaireController::class, 'sendMessage'])->name('stagiaires.message.send');

        // 🧑‍🤝‍🧑 Groupes
        Route::get('/groupes', [GroupeController::class, 'index'])->name('groupes.index');
        Route::get('/groupes/create', [GroupeController::class, 'create'])->name('groupes.create');
        Route::get('/groupes/co-formateurs/recherche', [GroupeController::class, 'searchCoFormateurs'])->name('groupes.co-formateurs.search');
        Route::post('/groupes', [GroupeController::class, 'store'])->name('groupes.store');
        Route::get('/groupes/{id}/edit', [GroupeController::class, 'edit'])->name('groupes.edit');
        Route::put('/groupes/{id}', [GroupeController::class, 'update'])->name('groupes.update');
        Route::delete('/groupes/{id}', [GroupeController::class, 'destroy'])->name('groupes.destroy');
        if (\App\Support\Outils\EtatsOutils::actif('tableau_blanc')) {
            Route::prefix('/groupes/{group}/tableau-blanc')
                ->name('groupes.whiteboard.')
                ->group(function () {
                    Route::get('/', [WhiteboardController::class, 'show'])->name('show');
                    Route::get('/snapshot', [WhiteboardController::class, 'snapshot'])->name('snapshot');
                    Route::post('/excalidraw-save', [WhiteboardController::class, 'save'])->name('excalidraw.save');
                    Route::post('/items', [WhiteboardController::class, 'upsert'])->name('items.upsert');
                    Route::delete('/items/{item}', [WhiteboardController::class, 'destroy'])->name('items.destroy');
                    Route::post('/clear', [WhiteboardController::class, 'clear'])->name('clear');
                });
        }

        // ✍️ Émargement (feuille de présence, par groupe)
        if (\App\Support\Outils\EtatsOutils::actif('emargement')) {
            Route::get('/emargement', [EmargementController::class, 'index'])->name('emargement.index');
            Route::post('/emargement/groupes/{group}/activer', [EmargementController::class, 'activerGroupe'])->name('emargement.activer');
            Route::post('/emargement/groupes/{group}/desactiver', [EmargementController::class, 'desactiverGroupe'])->name('emargement.desactiver');
            Route::prefix('/groupes/{group}/emargement')
                ->name('groupes.emargement.')
                ->group(function () {
                    Route::post('/', [EmargementController::class, 'store'])->name('store');
                    Route::get('/{seance}', [EmargementController::class, 'show'])->name('show');
                    Route::get('/{seance}/state', [EmargementController::class, 'state'])->name('state');
                    Route::post('/{seance}/ouvrir', [EmargementController::class, 'ouvrir'])->name('ouvrir');
                    Route::post('/{seance}/fermer', [EmargementController::class, 'fermer'])->name('fermer');
                    Route::post('/{seance}/presences/{presence}/corriger', [EmargementController::class, 'corrigerPresence'])->name('presences.corriger');
                    Route::post('/{seance}/presences/ajouter', [EmargementController::class, 'ajouterStagiaire'])->name('presences.ajouter');
                    Route::get('/{seance}/export-pdf', [EmargementController::class, 'exportPdf'])->name('export-pdf');
                });
        }

        // 🌥️ Nuages de mots (parcours, par groupe)
        if (\App\Support\Outils\EtatsOutils::actif('nuage_mots')) {
            Route::prefix('/groupes/{group}/wordcloud')
                ->name('groupes.wordcloud.')
                ->group(function () {
                    Route::get('/{item}/live', [GroupeWordCloudController::class, 'live'])->name('live');
                    Route::get('/{item}/data', [GroupeWordCloudController::class, 'liveData'])->name('data');
                });
        }

        // 🧩 Outils génériques de parcours (buzzer, échelle, vrai/faux, roue, composants)
        Route::prefix('/groupes/{group}/outils')
            ->name('groupes.outils.')
            ->group(function () {
                Route::get('/{item}/lancer', [GroupeOutilController::class, 'launch'])->name('launch');
            });

        // 📈 Progression des stagiaires
        Route::get('/progressions', function () {
            return redirect()->route('formateur.progressions.groupes');
        })->name('progressions.index');

        Route::get('/progressions/groupes', [ProgressionGroupesController::class, 'index'])
            ->name('progressions.groupes');

        Route::get('/progressions/stagiaires', [ProgressionStagiairesController::class, 'index'])
            ->name('progressions.stagiaires');

        Route::get('/progressions/stagiaire/{user}', [ProgressionStagiaireController::class, 'show'])
            ->name('progressions.stagiaire');

        Route::get('/progressions/modules', [ProgressionModulesController::class, 'index'])
            ->name('progressions.modules');

        // ✅ IMPORTANT : route AJAX/SCORM
        Route::post('/progression/complete', [ProgressionController::class, 'markCompleted'])
            ->name('progression.complete');

        // 🛠️ Outils numériques
        Route::get('/outils-numeriques', [OutilsNumeriquesController::class, 'index'])
            ->name('outils.index');

        // Le garde-fou est dans OutilsPowerPointController (pas ici) : routes toujours
        // enregistrées, mais inaccessibles (404) quand l'outil est désactivé. Nécessaire
        // pour rester testable (contrairement à un if ici, évalué une fois au boot).
        Route::prefix('/outils-numeriques/powerpoint')
            ->name('outils.powerpoint.')
            ->group(function () {
                Route::get('/', [OutilsPowerPointController::class, 'index'])->name('index');
                Route::post('/', [OutilsPowerPointController::class, 'store'])->name('store');
                Route::get('/{module}', [OutilsPowerPointController::class, 'show'])->name('show');
                Route::get('/{module}/status', [OutilsPowerPointController::class, 'status'])->name('status');
                Route::post('/{module}/retry', [OutilsPowerPointController::class, 'retry'])->name('retry');
                Route::post('/{module}/publish', [OutilsPowerPointController::class, 'publish'])->name('publish');
            });

        Route::get('/banque-de-questions-quiz', [OutilsQuizQuestionsController::class, 'index'])
            ->name('outils.quiz-questions.index');

        if (\App\Support\Outils\EtatsOutils::actif('quiz_direct')) {
            Route::get('/quiz-en-direct', [OutilsLiveQuizController::class, 'index'])
                ->name('outils.quiz.index');

            Route::prefix('/outils-numeriques/questionnaires')
                ->name('questionnaires.')
                ->group(function () {
                    Route::post('/', [QuestionnaireController::class, 'store'])->name('store');
                    Route::delete('/{questionnaire}', [QuestionnaireController::class, 'destroy'])->name('destroy');
                    Route::post('/{questionnaire}/questions', [QuestionnaireController::class, 'storeQuestion'])->name('questions.store');
                    Route::post('/{questionnaire}/questions/generer-ia', [QuestionnaireController::class, 'generateIA'])->name('questions.generate-ia');
                    Route::post('/{questionnaire}/questions/{question}/toggle', [QuestionnaireController::class, 'toggleQuestion'])->name('questions.toggle');
                    Route::delete('/{questionnaire}/questions/{question}', [QuestionnaireController::class, 'destroyQuestion'])->name('questions.destroy');
                });

            Route::prefix('/outils-numeriques/quiz-en-direct')
                ->name('group-quiz.')
                ->group(function () {
                    Route::post('/launch', [GroupQuizSessionController::class, 'launch'])->name('launch');
                    Route::get('/{session}', [GroupQuizSessionController::class, 'show'])->name('show');
                    Route::post('/{session}/start', [GroupQuizSessionController::class, 'start'])->name('start');
                    Route::post('/{session}/reveal', [GroupQuizSessionController::class, 'reveal'])->name('reveal');
                    Route::post('/{session}/next', [GroupQuizSessionController::class, 'next'])->name('next');
                    Route::post('/{session}/close', [GroupQuizSessionController::class, 'close'])->name('close');
                    Route::get('/{session}/snapshot', [GroupQuizSessionController::class, 'snapshot'])->name('snapshot');
                    Route::delete('/{session}', [GroupQuizSessionController::class, 'destroy'])->name('destroy');
                });
        }

        Route::redirect('/sondages', '/formateur/outils-numeriques/sondages', 301);

        if (\App\Support\Outils\EtatsOutils::actif('sondage')) {
            Route::prefix('/outils-numeriques/sondages')->name('sondages.')->group(function () {
                Route::get('/', [OutilsSondageController::class, 'index'])->name('index');
                Route::post('/launch', [OutilsSondageController::class, 'launch'])->name('launch');
                Route::get('/{pollSession}', [OutilsSondageController::class, 'show'])->name('show');
                Route::post('/{pollSession}/toggle', [OutilsSondageController::class, 'toggle'])->name('toggle');
                Route::get('/{pollSession}/state', [OutilsSondageController::class, 'state'])->name('state');
            });

            Route::prefix('/outils-numeriques/sondage-questionnaires')
                ->name('sondage-questionnaires.')
                ->group(function () {
                    Route::post('/', [PollQuestionnaireController::class, 'store'])->name('store');
                    Route::delete('/{questionnaire}', [PollQuestionnaireController::class, 'destroy'])->name('destroy');
                    Route::post('/{questionnaire}/questions', [PollQuestionnaireController::class, 'storeQuestion'])->name('questions.store');
                    Route::delete('/{questionnaire}/questions/{index}', [PollQuestionnaireController::class, 'destroyQuestion'])->name('questions.destroy');
                });
        }

        if (\App\Support\Outils\EtatsOutils::actif('vraifaux')) {
            Route::prefix('/vrai-faux')->name('vraifaux.')->group(function () {
                Route::get('/', [OutilsVraiFauxController::class, 'index'])->name('index');
                Route::post('/', [OutilsVraiFauxController::class, 'store'])->name('store');
                Route::get('/{trueFalseSession}', [OutilsVraiFauxController::class, 'show'])->name('show');
                Route::post('/{trueFalseSession}/toggle', [OutilsVraiFauxController::class, 'toggle'])->name('toggle');
                Route::get('/{trueFalseSession}/state', [OutilsVraiFauxController::class, 'state'])->name('state');
            });
        }

        if (\App\Support\Outils\EtatsOutils::actif('echelle')) {
            Route::prefix('/echelle')->name('echelle.')->group(function () {
                Route::get('/', [OutilsEchelleController::class, 'index'])->name('index');
                Route::post('/', [OutilsEchelleController::class, 'store'])->name('store');
                Route::get('/{scaleSession}', [OutilsEchelleController::class, 'show'])->name('show');
                Route::post('/{scaleSession}/toggle', [OutilsEchelleController::class, 'toggle'])->name('toggle');
                Route::get('/{scaleSession}/state', [OutilsEchelleController::class, 'state'])->name('state');
            });
        }

        if (\App\Support\Outils\EtatsOutils::actif('composants')) {
            Route::prefix('/trouve-le-composant')->name('composants.')->group(function () {
                Route::get('/', [OutilsComposantController::class, 'index'])->name('index');
                Route::get('/nouvelle', [OutilsComposantController::class, 'create'])->name('create');
                Route::post('/', [OutilsComposantController::class, 'store'])->name('store');
                Route::post('/enregistrees/{componentFinderActivity}/lancer', [OutilsComposantController::class, 'launch'])->name('activities.launch');
                Route::get('/enregistrees/{componentFinderActivity}/modifier', [OutilsComposantController::class, 'edit'])->name('activities.edit');
                Route::put('/enregistrees/{componentFinderActivity}', [OutilsComposantController::class, 'update'])->name('activities.update');
                Route::delete('/enregistrees/{componentFinderActivity}', [OutilsComposantController::class, 'destroyActivity'])->name('activities.destroy');
                Route::get('/{componentFinderSession}', [OutilsComposantController::class, 'show'])->name('show');
                Route::post('/{componentFinderSession}/toggle', [OutilsComposantController::class, 'toggle'])->name('toggle');
                Route::delete('/{componentFinderSession}', [OutilsComposantController::class, 'destroy'])->name('destroy');
            });
        }

        if (\App\Support\Outils\EtatsOutils::actif('buzzer')) {
            Route::prefix('/buzzer')->name('buzzer.')->group(function () {
                Route::get('/', [OutilsBuzzerController::class, 'index'])->name('index');
                Route::post('/', [OutilsBuzzerController::class, 'store'])->name('store');
                Route::get('/{buzzerSession}', [OutilsBuzzerController::class, 'show'])->name('show');
                Route::get('/{buzzerSession}/snapshot', [OutilsBuzzerController::class, 'snapshot'])->name('snapshot');
                Route::post('/{buzzerSession}/start', [OutilsBuzzerController::class, 'start'])->name('start');
                Route::post('/{buzzerSession}/correct', [OutilsBuzzerController::class, 'correct'])->name('correct');
                Route::post('/{buzzerSession}/incorrect', [OutilsBuzzerController::class, 'incorrect'])->name('incorrect');
                Route::post('/{buzzerSession}/skip', [OutilsBuzzerController::class, 'skip'])->name('skip');
                Route::post('/{buzzerSession}/next', [OutilsBuzzerController::class, 'next'])->name('next');
                Route::post('/{buzzerSession}/close', [OutilsBuzzerController::class, 'close'])->name('close');
                Route::delete('/{buzzerSession}', [OutilsBuzzerController::class, 'destroy'])->name('destroy');
            });
        }

        Route::get('/pages-collaboratives', [OutilsPagesCollaborativesController::class, 'index'])
            ->name('pages-collaboratives.index');

        if (\App\Support\Outils\EtatsOutils::actif('roue_aleatoire')) {
            Route::prefix('/roue-aleatoire')->name('roue.')->group(function () {
                Route::get('/', [RoueAleatoireController::class, 'index'])->name('index');
                Route::post('/', [RoueAleatoireController::class, 'store'])->name('store');
                Route::get('/{session}', [RoueAleatoireController::class, 'show'])->name('show');
                Route::post('/{session}/participants', [RoueAleatoireController::class, 'updateParticipants'])->name('participants');
                Route::post('/{session}/spin', [RoueAleatoireController::class, 'spin'])->name('spin');
                Route::post('/{session}/reset', [RoueAleatoireController::class, 'reset'])->name('reset');
                Route::get('/{session}/state', [RoueAleatoireController::class, 'state'])->name('state');
            });
        }

        if (\App\Support\Outils\EtatsOutils::actif('mur_questions')) {
            Route::prefix('/mur-questions')->name('questions.')->group(function () {
                Route::get('/', [QuestionWallController::class, 'index'])->name('index');
                Route::post('/', [QuestionWallController::class, 'store'])->name('store');
                Route::get('/{wall}', [QuestionWallController::class, 'show'])->name('show');
                Route::post('/{wall}/toggle', [QuestionWallController::class, 'toggle'])->name('toggle');
                Route::post('/{wall}/questions/{question}/status', [QuestionWallController::class, 'updateStatus'])->name('status');
                Route::get('/{wall}/state', [QuestionWallController::class, 'state'])->name('state');
            });
        }

        // 📚 Mes parcours créés
        Route::prefix('/mes-parcours')->name('mes-parcours.')->group(function () {
            Route::get('/', [MesParcoursController::class, 'index'])->name('index');
            Route::get('/create', [MesParcoursController::class, 'create'])->name('create');
            Route::post('/', [MesParcoursController::class, 'store'])->name('store');
            Route::get('/{parcours}', [MesParcoursController::class, 'show'])->name('show');
            Route::get('/{parcours}/edit', [MesParcoursController::class, 'edit'])->name('edit');
            Route::put('/{parcours}', [MesParcoursController::class, 'update'])->name('update');
            Route::delete('/{parcours}', [MesParcoursController::class, 'destroy'])->name('destroy');
        });

        // 📂 Formations
        Route::get('/formations', [FormateurModuleController::class, 'mesModules'])->name('formations.index');
        Route::get('/formations/{module}/detail', [FormateurModuleController::class, 'moduleDetail'])->name('formations.detail');
        Route::get('/formations/{module}/preview', [FormateurModuleController::class, 'preview'])->name('formations.preview');
        Route::get('/objectifs/recherche', [ObjectiveController::class, 'index'])->name('objectifs.index');

        Route::get('/formations/{module}/section/{section}', [ModuleController::class, 'section'])
            ->name('formations.section');

        Route::get('/formations/{module}/section/{section}/lesson/{lecture}', [ModuleController::class, 'lire'])
            ->name('formations.lecture');

        Route::post('/formations/{module}/section/{section}/lesson/{lecture}/resources', [LessonResourceController::class, 'store'])
            ->name('formations.lesson.resources.store');

        Route::post('/formations/{module}/section/{section}/lesson/{lecture}/resources/{resource}/visibility', [LessonResourceController::class, 'toggleVisibility'])
            ->name('formations.lesson.resources.visibility');

        Route::delete('/formations/{module}/section/{section}/lesson/{lecture}/resources/{resource}', [LessonResourceController::class, 'destroy'])
            ->name('formations.lesson.resources.destroy');

        // 🧩 Mes modules (module builder formateur)
        Route::prefix('/mes-modules')->name('modules.builder.')->group(function () {
            Route::get('/', [ModuleBuilderController::class, 'index'])->name('index');
            Route::get('/consommation-ia', [ModuleBuilderController::class, 'consommationIA'])->name('consommation-ia');
            Route::get('/creer', [ModuleBuilderController::class, 'create'])->name('create');
            Route::post('/', [ModuleBuilderController::class, 'store'])->name('store');
            Route::post('/generer-ia', [ModuleBuilderController::class, 'generateStructureIA'])->name('generate-structure-ia');
            Route::post('/depuis-catalogue/{catalogModule}', [ModuleBuilderController::class, 'duplicate'])->name('duplicate');
            Route::post('/{module}/images', [ModuleBuilderController::class, 'uploadImage'])->name('images.store');
            Route::post('/{module}/videos', [ModuleBuilderController::class, 'uploadVideo'])->name('videos.store');
            Route::post('/{module}/audios', [ModuleBuilderController::class, 'uploadAudio'])->name('audios.store');
            Route::post('/{module}/scorm', [ModuleBuilderController::class, 'uploadScorm'])->name('scorm.store');
            Route::get('/{module}/edition', [ModuleBuilderController::class, 'edit'])->name('edit');
            Route::get('/{module}/apercu', [ModuleBuilderController::class, 'preview'])->name('preview');
            Route::put('/{module}', [ModuleBuilderController::class, 'update'])->name('update');
            Route::put('/{module}/options', [ModuleBuilderController::class, 'updateOptions'])->name('options.update');
            Route::delete('/{module}', [ModuleBuilderController::class, 'destroy'])->name('destroy');

            Route::post('/{module}/sections', [ModuleBuilderController::class, 'storeSection'])->name('sections.store');
            Route::put('/sections/{section}', [ModuleBuilderController::class, 'updateSection'])->name('sections.update');
            Route::delete('/sections/{section}', [ModuleBuilderController::class, 'destroySection'])->name('sections.destroy');
            Route::post('/{module}/sections/reorder', [ModuleBuilderController::class, 'reorderSections'])->name('sections.reorder');

            Route::post('/sections/{section}/lectures', [ModuleBuilderController::class, 'storeLecture'])->name('lectures.store');
            Route::post('/sections/{section}/lectures/generer-ia', [ModuleBuilderController::class, 'generateLectureIA'])->name('lectures.generate-ia');
            Route::get('/lectures/{lecture}/edition', [ModuleBuilderController::class, 'editLecture'])->name('lectures.edit');
            Route::post('/lectures/{lecture}/generer-audio', [ModuleBuilderController::class, 'generateAudioLecture'])->name('lectures.generate-audio');
            Route::put('/lectures/{lecture}', [ModuleBuilderController::class, 'updateLecture'])->name('lectures.update');
            Route::put('/lectures/{lecture}/pedagogie', [ModuleBuilderController::class, 'modifierPedagogie'])->name('lectures.pedagogie');
            Route::post('/lectures/{lecture}/support', [ModuleBuilderController::class, 'importerSupport'])->name('lectures.support.store');
            Route::post('/lectures/{lecture}/support/relancer', [ModuleBuilderController::class, 'relancerSlides'])->name('lectures.support.relancer');
            Route::get('/lectures/{lecture}/apercu-scorm', [ModuleBuilderController::class, 'apercuScorm'])->name('lectures.apercu-scorm');
            Route::delete('/lectures/{lecture}', [ModuleBuilderController::class, 'destroyLecture'])->name('lectures.destroy');
            Route::post('/lectures/{lecture}/duplicate', [ModuleBuilderController::class, 'duplicateLecture'])->name('lectures.duplicate');
            Route::post('/sections/{section}/lectures/reorder', [ModuleBuilderController::class, 'reorderLectures'])->name('lectures.reorder');
            Route::post('/lectures/{lecture}/move', [ModuleBuilderController::class, 'moveLecture'])->name('lectures.move');
            Route::post('/lectures/{lecture}/promote', [ModuleBuilderController::class, 'promoteLectureToSection'])->name('lectures.promote');

            Route::put('/{module}/groupes', [ModuleBuilderController::class, 'assignGroups'])->name('groups.sync');

            // Banque de questions de quiz d'une leçon (formateur propriétaire uniquement)
            Route::prefix('lectures/{lecture}/quiz/questions')->name('lectures.quiz.questions.')->group(function () {
                Route::get('/', [FormateurQuizQuestionController::class, 'index'])->name('index');
                Route::get('/creer', [FormateurQuizQuestionController::class, 'create'])->name('create');
                Route::post('/', [FormateurQuizQuestionController::class, 'store'])->name('store');
                Route::post('/import', [FormateurQuizQuestionController::class, 'importCsv'])->name('import');
                Route::get('/import/modele', [FormateurQuizQuestionController::class, 'downloadCsvTemplate'])->name('import.template');
                Route::post('/generer-ia', [FormateurQuizQuestionController::class, 'generateIA'])->name('generate-ia');
                Route::get('/{question}/edition', [FormateurQuizQuestionController::class, 'edit'])->name('edit');
                Route::put('/{question}', [FormateurQuizQuestionController::class, 'update'])->name('update');
                Route::delete('/{question}', [FormateurQuizQuestionController::class, 'destroy'])->name('destroy');
            });

            // Vue unifiée de la banque de questions pour toute la formation (arborescence + distribution)
            Route::prefix('{module}/quiz-questions')->name('quiz-questions.')->group(function () {
                Route::get('/', [ModuleQuizBankController::class, 'index'])->name('index');
            });
            Route::post('/quiz-questions/{question}/deplacer', [ModuleQuizBankController::class, 'move'])->name('quiz-questions.move');
        });

        Route::redirect('/word-clouds', '/formateur/outils-numeriques/nuages-de-mots', 301);
        Route::redirect('/nuages-de-mots', '/formateur/outils-numeriques/nuages-de-mots', 301);

        if (\App\Support\Outils\EtatsOutils::actif('nuage_mots')) {
            Route::prefix('/outils-numeriques/nuages-de-mots')
                ->name('nuages.')
                ->group(function () {
                    Route::get('/', [FormateurWordCloudController::class, 'index'])->name('index');
                    Route::post('/', [FormateurWordCloudController::class, 'store'])->name('store');
                    Route::get('/{wordCloud}/live', [FormateurWordCloudController::class, 'live'])->name('live');
                    Route::post('/{wordCloud}/question', [FormateurWordCloudController::class, 'setQuestion'])->name('question');
                    Route::post('/{wordCloud}/close', [FormateurWordCloudController::class, 'close'])->name('close');
                    Route::get('/{wordCloud}/live/data', [FormateurWordCloudController::class, 'liveData'])->name('live.data');
                    Route::delete('/{wordCloud}', [FormateurWordCloudController::class, 'destroy'])->name('destroy');
                });
        }

        // Personnaliser les leçons d'un module pour un groupe
        Route::get('/groupes/{group}/modules/{module}/lecons', [GroupeModuleLessonController::class, 'editModuleLessons'])
            ->name('groupes.modules.lecons.edit');

        Route::post('/groupes/{group}/modules/{module}/lecons/{lecture}/toggle', [GroupeModuleLessonController::class, 'toggleModuleLesson'])
            ->name('groupes.modules.lecons.toggle');

        Route::post('/groupes/{group}/modules/{module}/lecons/{lecture}/move-up', [GroupeModuleLessonController::class, 'moveModuleLessonUp'])
            ->name('groupes.modules.lecons.move.up');

        Route::post('/groupes/{group}/modules/{module}/lecons/{lecture}/move-down', [GroupeModuleLessonController::class, 'moveModuleLessonDown'])
            ->name('groupes.modules.lecons.move.down');

        Route::post('/groupes/{group}/modules/{module}/lecons/reset', [GroupeModuleLessonController::class, 'resetModuleLessons'])
            ->name('groupes.modules.lecons.reset');

        // Dans le groupe middleware(['auth', 'role:formateur']) ...

        // Route pour modifier le nombre de questions du quiz (Ajax ou Post classique)
        Route::post('/formations/lecture/{lecture}/update-quiz-count', [FormateurModuleController::class, 'updateQuizCount'])
            ->name('lecture.update_quiz_count');

        /*
|--------------------------------------------------------------------------
| Quiz DANS la leçon (mêmes écrans que stagiaire)
|--------------------------------------------------------------------------
*/
        Route::get('/formations/{module}/section/{section}/lesson/{lecture}/quiz/start', [QuizController::class, 'start'])
            ->name('quiz.start')
            ->middleware('signed');

        Route::prefix('/formations/{module}/section/{section}/lesson/{lecture}/quiz/{attempt}')
            ->name('lesson.quiz.')
            ->group(function () {
                Route::get('/question', [QuizController::class, 'showQuestion'])->name('question');
                Route::post('/answer', [QuizController::class, 'answer'])->name('answer');
                Route::get('/result', [QuizController::class, 'result'])->name('result');
                Route::post('/restart', [QuizController::class, 'restart'])->name('restart');
            });

        Route::prefix('/formations/{module}/section/{section}/lesson/{lecture}/live-quiz')
            ->name('live-quiz.')
            ->group(function () {
                Route::post('/', [LiveQuizSessionController::class, 'store'])->name('store');
                Route::get('/sessions/{session}', [LiveQuizSessionController::class, 'show'])->name('show');
                Route::post('/sessions/{session}/start', [LiveQuizSessionController::class, 'start'])->name('start');
                Route::post('/sessions/{session}/reveal', [LiveQuizSessionController::class, 'reveal'])->name('reveal');
                Route::post('/sessions/{session}/next', [LiveQuizSessionController::class, 'next'])->name('next');
                Route::post('/sessions/{session}/close', [LiveQuizSessionController::class, 'close'])->name('close');
                Route::get('/sessions/{session}/snapshot', [LiveQuizSessionController::class, 'snapshot'])->name('snapshot');
            });

        Route::post('/live-quiz/launch', [LiveQuizSessionController::class, 'launch'])
            ->name('live-quiz.launch');

    });
