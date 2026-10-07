<?php

// routes/stagiaire.php

use App\Http\Controllers\Backend\ModuleController;
use App\Http\Controllers\Stagiaire\EmargementController;
use App\Http\Controllers\Stagiaire\FirstLoginController;
use App\Http\Controllers\Stagiaire\GroupQuizSessionController;
use App\Http\Controllers\Stagiaire\LiveQuizSessionController;
use App\Http\Controllers\Stagiaire\ParcoursOutilController;
use App\Http\Controllers\Stagiaire\ParcoursWordCloudController;
use App\Http\Controllers\Stagiaire\QuestionWallController;
use App\Http\Controllers\Stagiaire\QuizController; // ✅ Import du nouveau contrôleur
use App\Http\Controllers\Stagiaire\WhiteboardController;
use App\Http\Controllers\Stagiaire\WordCloudController;
use App\Http\Controllers\StagiaireController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:stagiaire', 'track.time'])
    ->prefix('stagiaire')
    ->name('stagiaire.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | 🔓 ZONE OUVERTE : Première connexion (Changement de MDP obligatoire)
        |--------------------------------------------------------------------------
        | Ces routes doivent être accessibles même si password_changed_at est NULL.
        */
        Route::get('/premiere-connexion', [FirstLoginController::class, 'show'])
            ->name('password.init');

        Route::post('/premiere-connexion', [FirstLoginController::class, 'store'])
            ->name('password.init.store');

        /*
        |--------------------------------------------------------------------------
        | 🔒 ZONE PROTÉGÉE : Tout le reste du site
        |--------------------------------------------------------------------------
        | Le middleware 'force.password.change' empêche l'accès si le MDP n'est pas changé.
        */
        Route::middleware(['force.password.change'])->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Dashboard & profil
            |--------------------------------------------------------------------------
            */
            Route::get('/', [StagiaireController::class, 'StagiaireDashboard'])
                ->name('dashboard');

            Route::get('/profile', [UserController::class, 'UserProfile'])
                ->name('profile');

            Route::get('/parametre', [UserController::class, 'UserParametre'])
                ->name('parametre');
            Route::get('/documentation', fn () => view('stagiaire.documentation'))
                ->name('documentation');

            if (\App\Support\Outils\EtatsOutils::actif('tableau_blanc')) {
                Route::prefix('/tableau-blanc')
                    ->name('whiteboard.')
                    ->group(function () {
                        Route::get('/', [WhiteboardController::class, 'index'])->name('index');
                        Route::get('/notification-status', [WhiteboardController::class, 'notificationStatus'])->name('notification-status');
                        Route::get('/groupes/{group}', [WhiteboardController::class, 'show'])->name('show');
                        Route::get('/groupes/{group}/snapshot', [WhiteboardController::class, 'snapshot'])->name('snapshot');
                        Route::post('/groupes/{group}/excalidraw-save', [WhiteboardController::class, 'save'])->name('excalidraw.save');
                        Route::post('/groupes/{group}/items', [WhiteboardController::class, 'upsert'])->name('items.upsert');
                        Route::delete('/groupes/{group}/items/{item}', [WhiteboardController::class, 'destroy'])->name('items.destroy');
                    });
            }

            if (\App\Support\Outils\EtatsOutils::actif('emargement')) {
                Route::prefix('/emargement')
                    ->name('emargement.')
                    ->group(function () {
                        Route::get('/notification-status', [EmargementController::class, 'notificationStatus'])->name('notification-status');
                        Route::get('/groupes/{group}', [EmargementController::class, 'show'])->name('show');
                        Route::post('/groupes/{group}/signer', [EmargementController::class, 'signer'])->name('signer');
                    });
            }

            if (\App\Support\Outils\EtatsOutils::actif('mur_questions')) {
                Route::prefix('/mur-questions')
                    ->name('question-wall.')
                    ->group(function () {
                        Route::get('/notification-status', [QuestionWallController::class, 'notificationStatus'])->name('notification-status');
                    });
            }

            Route::post('/profil/store', [UserController::class, 'UserProfilStore'])
                ->name('profil.store');

            Route::get('/securite', [UserController::class, 'showUserSecurite'])
                ->name('securite.show');

            Route::post('/securite', [UserController::class, 'UserSecurite'])
                ->name('securite');

            Route::delete('/compte', [UserController::class, 'destroyOwnAccount'])
                ->name('account.destroy');

            /*
            |--------------------------------------------------------------------------
            | Modules & progression
            |--------------------------------------------------------------------------
            */
            Route::get('/modules', [StagiaireController::class, 'StagiaireModules'])
                ->name('modules');

            Route::get('/modules/{module}', [StagiaireController::class, 'StagiaireModuleDetail'])
                ->name('module.detail');

            Route::get('/modules/{module}/progression-json', [UserController::class, 'getProgressionJson'])
                ->name('module.progression.json');

            Route::get('/resultats', [StagiaireController::class, 'StagiaireResultats'])
                ->name('resultats');

            Route::get('/outils', [StagiaireController::class, 'StagiaireOutils'])
                ->name('outils');

            Route::get('/messages', [StagiaireController::class, 'StagiaireMessages'])
                ->name('messages.index');

            Route::get('/formations/{module}/fin', [ModuleController::class, 'finModule'])
                ->name('module.fin');

            Route::get('/modules/{module}/fin', [ModuleController::class, 'finModule'])
                ->name('module.fin.legacy');

            /*
            |--------------------------------------------------------------------------
            | Sections & leçons
            |--------------------------------------------------------------------------
            */
            Route::get('/modules/{module}/sections/{section}', [ModuleController::class, 'section'])
                ->name('module.section');

            Route::get('/modules/{module}/sections/{section}/lessons/{lecture}', [ModuleController::class, 'lire'])
                ->name('module.lecture');

            /*
            |--------------------------------------------------------------------------
            | Quiz DANS la leçon (URL hiérarchique)
            |--------------------------------------------------------------------------
            */
            // Quiz (start signé)
            Route::get('/modules/{module}/sections/{section}/lessons/{lecture}/quiz/start', [QuizController::class, 'start'])
                ->name('quiz.start')
                ->middleware('signed');

            // Quiz imbriqué dans la leçon
            Route::prefix('/modules/{module}/sections/{section}/lessons/{lecture}/quiz/{attempt}')
                ->name('lesson.quiz.')
                ->group(function () {
                    Route::get('/question', [QuizController::class, 'showQuestion'])->name('question');
                    Route::post('/answer', [QuizController::class, 'answer'])->name('answer');
                    Route::get('/result', [QuizController::class, 'result'])->name('result');

                    // NOUVEAU : recommencer
                    Route::post('/restart', [QuizController::class, 'restart'])->name('restart');
                });

            Route::prefix('/live-quiz')
                ->name('live-quiz.')
                ->group(function () {
                    Route::get('/', [LiveQuizSessionController::class, 'index'])->name('index');
                    Route::post('/lookup', [LiveQuizSessionController::class, 'lookup'])->name('lookup');
                    Route::get('/notification-status', [LiveQuizSessionController::class, 'notificationStatus'])->name('notification-status');
                    Route::get('/join/{code}', [LiveQuizSessionController::class, 'joinByCode'])->name('join-code');
                    Route::post('/sessions/{session}/join', [LiveQuizSessionController::class, 'join'])->name('join');
                    Route::get('/sessions/{session}', [LiveQuizSessionController::class, 'show'])->name('show');
                    Route::post('/sessions/{session}/answer', [LiveQuizSessionController::class, 'answer'])->name('answer');
                    Route::get('/sessions/{session}/snapshot', [LiveQuizSessionController::class, 'snapshot'])->name('snapshot');
                });

            if (\App\Support\Outils\EtatsOutils::actif('quiz_direct')) {
                Route::prefix('/quiz-en-direct')
                    ->name('group-quiz.')
                    ->group(function () {
                        Route::get('/notification-status', [GroupQuizSessionController::class, 'notificationStatus'])->name('notification-status');
                        Route::get('/join/{code}', [GroupQuizSessionController::class, 'joinByCode'])->name('join-code');
                        Route::get('/{session}', [GroupQuizSessionController::class, 'show'])->name('show');
                        Route::post('/{session}/answer', [GroupQuizSessionController::class, 'answer'])->name('answer');
                        Route::get('/{session}/snapshot', [GroupQuizSessionController::class, 'snapshot'])->name('snapshot');
                    });
            }

            // Nuage de mots (parcours)
            if (\App\Support\Outils\EtatsOutils::actif('nuage_mots')) {
                Route::prefix('/wordcloud')
                    ->name('wordcloud.')
                    ->group(function () {
                        Route::get('/notification-status', [WordCloudController::class, 'notificationStatus'])->name('notification-status');
                        Route::get('/{item}', [ParcoursWordCloudController::class, 'show'])->name('parcours.show');
                        Route::post('/{item}/submit', [ParcoursWordCloudController::class, 'submit'])->name('parcours.submit');
                        Route::get('/{item}/data', [ParcoursWordCloudController::class, 'liveData'])->name('parcours.data');
                    });
            }

            // Outils génériques de parcours (buzzer, échelle, vrai/faux, roue, composants)
            Route::prefix('/outil')
                ->name('outil.')
                ->group(function () {
                    Route::get('/parcours/{item}', [ParcoursOutilController::class, 'show'])->name('parcours.show');
                });

        }); // Fin du middleware force.password.change

    });
