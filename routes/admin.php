<?php
// routes/admin.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\GroupeController;
use App\Http\Controllers\Backend\ModuleController;
use App\Http\Controllers\Backend\ModuleSectionController;
use App\Http\Controllers\Backend\ModuleLectureController;
use App\Http\Controllers\LessonFeedbackController;
use App\Http\Controllers\Backend\EvaluationController;
use App\Http\Controllers\Backend\SkillReferentialController;
use App\Http\Controllers\Backend\SkillDomainController;
use App\Http\Controllers\Backend\SkillController;
use App\Http\Controllers\Backend\QuizQuestionController;
use App\Http\Controllers\Backend\ScormLibraryController;
use App\Http\Controllers\Backend\StagiaireController;
use App\Http\Controllers\Backend\CompetencyController;
use App\Http\Controllers\Backend\BadgeController;
use App\Http\Controllers\Backend\ObservateurController;
use App\Http\Controllers\Backend\PilotageController;
use App\Http\Controllers\Backend\TrainerPathQualityController;
use App\Http\Controllers\Backend\ConsommationIAController;
use App\Http\Controllers\Backend\WordCloudController;
use App\Http\Controllers\Backend\OutilEtatController;
use App\Http\Controllers\UtilisateurController;

Route::middleware(['auth', 'role:admin', 'admin.activity'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/', [AdminController::class, 'AdminDashboard'])->name('dashboard');
        Route::get('/profile', [AdminController::class, 'AdminProfile'])->name('profile');
        // Compte (Préférences)
        Route::get('/parametre', [AdminController::class, 'AdminParametre'])->name('parametre');
        Route::post('/profil/store', [AdminController::class, 'AdminProfilStore'])->name('profil.store');

        // Sécurité
        Route::get('/securite', [AdminController::class, 'AdminSecurite'])->name('securite');
        Route::post('/securite', [AdminController::class, 'AdminSecuriteUpdate'])->name('securite.update');

        // Marche/arrêt des outils numériques
        Route::prefix('outils')->name('outils.')->group(function () {
            Route::get('/', [OutilEtatController::class, 'index'])->name('index');
            Route::post('/{cle}/toggle', [OutilEtatController::class, 'toggle'])->name('toggle');
        });


        Route::get('/formateurs', [AdminController::class, 'AllFormateur'])->name('formateurs');
        Route::delete('/formateurs/{user}', [AdminController::class, 'DestroyFormateur'])->name('formateurs.destroy');
        Route::patch('/formateurs/{user}/adhesion', [AdminController::class, 'UpdateFormateurAdhesion'])->name('formateurs.adhesion.update');
        Route::post('/update-user-status', [AdminController::class, 'UpdateUserStatus'])->name('update.user.status');

        Route::get('/stagiaires', [AdminController::class, 'AllStagiaires'])->name('stagiaires.index');
        Route::delete('/stagiaires/{user}', [AdminController::class, 'DestroyStagiaire'])->name('stagiaires.destroy');
        Route::post('/stagiaires/{user}/reset-progression', [StagiaireController::class, 'resetProgression'])->name('stagiaires.reset');
        Route::delete('/observateurs/{user}', [AdminController::class, 'DestroyObservateur'])->name('observateurs.destroy');

        Route::prefix('utilisateurs')->name('utilisateurs.')->group(function () {
            Route::get('/', [UtilisateurController::class, 'index'])->name('index');
            Route::get('/create', [UtilisateurController::class, 'create'])->name('create');
            Route::post('/', [UtilisateurController::class, 'store'])->name('store');
            Route::get('/{utilisateur}/edit', [UtilisateurController::class, 'edit'])->name('edit');
            Route::put('/{utilisateur}', [UtilisateurController::class, 'update'])->name('update');
            Route::patch('/{utilisateur}/statut', [UtilisateurController::class, 'mettreAJourStatut'])->name('statut.update');
        });

        Route::prefix('observateurs')->name('observateurs.')->group(function () {
            Route::get('/', [ObservateurController::class, 'index'])->name('index');
            Route::get('/create', [ObservateurController::class, 'create'])->name('create');
            Route::post('/', [ObservateurController::class, 'store'])->name('store');
            Route::get('/{observateur}/edit', [ObservateurController::class, 'edit'])->name('edit');
            Route::put('/{observateur}', [ObservateurController::class, 'update'])->name('update');
        });

        // Catégories
        Route::controller(CategoryController::class)->group(function () {
            Route::get('/categories', 'AllCategory')->name('categories.all');
            Route::get('/categories/ajout', 'AddCategory')->name('categories.ajout');
            Route::post('/categories/store', 'StoreCategory')->name('categories.store');
            Route::get('/categories/edit/{id}', 'EditCategory')->name('categories.edit');
            Route::post('/categories/update', 'UpdateCategory')->name('categories.update');
            Route::delete('/categories/{id}', 'DeleteCategory')->name('categories.delete');

            Route::get('/sous-categories', 'AllSubCategory')->name('subcategories.all');
            Route::get('/sous-categories/ajout', 'AddSubCategory')->name('subcategories.add');
            Route::post('/sous-categories/store', 'StoreSubCategory')->name('subcategories.store');
            Route::get('/sous-categories/edit/{id}', 'EditSubCategory')->name('subcategories.edit');
            Route::post('/sous-categories/update', 'UpdateSubCategory')->name('subcategories.update');
            Route::delete('/sous-categories/{id}', 'DeleteSubCategory')->name('subcategories.delete');
        });

        // Groupes
        Route::controller(GroupeController::class)->group(function () {
            Route::get('/groupes', 'AllGroupe')->name('groupes');
            Route::get('/groupes/ajout', 'AddGroupe')->name('groupes.add');
            Route::post('/groupes', 'StoreGroupe')->name('groupes.store');
            Route::get('/groupes/{id}/edit', 'EditGroupe')->name('groupes.edit');
            Route::put('/groupes/{id}', 'UpdateGroupe')->name('groupes.update');
            Route::delete('/groupes/{id}', 'destroy')->name('groupes.delete');
        });

        // Modules - CRUD de base
        Route::controller(ModuleController::class)->group(function () {
            Route::get('/modules', 'Modules')->name('modules');
            Route::get('/modules/ajout', 'AddModule')->name('modules.add');
            Route::post('/modules/store', 'StoreModule')->name('modules.store');
            Route::get('/modules/edit/{id}', 'EditModule')->name('modules.edit');
            Route::put('/modules/update/{id}', 'UpdateModule')->name('modules.update');
            Route::delete('/modules/{id}', 'DeleteModule')->name('modules.delete');
            Route::patch('/modules/{module}/toggle-status', 'toggleStatus')->name('modules.toggle-status');
        });

        // Modules - Gestion des leçons
        Route::controller(ModuleLectureController::class)->group(function () {
            Route::get('/modules/{id}/lectures/add', 'AddModuleLecture')->name('modules.lecture.add');
            Route::post('/modules/lectures/store', 'SaveLecture')->name('modules.lecture.store');
            Route::get('/lectures/edit/{id}', 'EditLecture')->name('lectures.edit');
            Route::post('/lectures/update', 'UpdateModuleLecture')->name('lectures.update');
            Route::delete('/lectures/{id}', 'DeleteLecture')->name('lectures.delete');
            Route::get('/lectures/{id}/move-up', 'MoveLectureUp')->name('lectures.move.up');
            Route::get('/lectures/{id}/move-down', 'MoveLectureDown')->name('lectures.move.down');
        });

        // Modules - Gestion des chapitres
        Route::controller(ModuleSectionController::class)->group(function () {
            Route::post('/modules/sections/store', 'AddModuleSection')->name('modules.section.store');
            Route::post('/sections/delete/{id}', 'DeleteSection')->name('sections.delete');
            Route::get('/sections/edit/{id}', 'EditModuleSection')->name('sections.edit');
            Route::post('/sections/update/{id}', 'UpdateModuleSection')->name('sections.update');
        });

        // Evaluations
        Route::controller(EvaluationController::class)
            ->prefix('evaluations')
            ->name('evaluations.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');

                Route::get('/{evaluation}', 'show')->name('show');
                Route::get('/{evaluation}/edit', 'edit')->name('edit');
                Route::put('/{evaluation}', 'update')->name('update');
                Route::delete('/{evaluation}', 'destroy')->name('destroy');
                
            });

        // Référentiels
        Route::resource('referentiels', SkillReferentialController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->names('referentiels');

        Route::prefix('referentiels/{referentiel}')->group(function () {

            Route::resource('domaines', SkillDomainController::class)
                ->parameters(['domaines' => 'domain'])
                ->except(['show'])
                ->names('referentiels.domains');

            Route::resource('competences', SkillController::class)
                ->parameters(['competences' => 'skill'])
                ->except(['show'])
                ->names('referentiels.skills');
        });

        // Quiz (banque de questions)
        Route::prefix('lectures/{lecture}/quiz')->name('quiz.')->group(function () {
            Route::get('/questions', [QuizQuestionController::class, 'index'])->name('questions.index');
            Route::get('/questions/create', [QuizQuestionController::class, 'create'])->name('questions.create');
            Route::post('/questions', [QuizQuestionController::class, 'store'])->name('questions.store');
            Route::post('/questions/import', [QuizQuestionController::class, 'importCsv'])->name('questions.import');
            Route::get('/questions/import/template', [QuizQuestionController::class, 'downloadCsvTemplate'])->name('questions.import.template');

            Route::get('/questions/{question}/edit', [QuizQuestionController::class, 'edit'])->name('questions.edit');
            Route::put('/questions/{question}', [QuizQuestionController::class, 'update'])->name('questions.update');
            Route::delete('/questions/{question}', [QuizQuestionController::class, 'destroy'])->name('questions.destroy');
        });

        // Retours
        Route::get('/retours', [LessonFeedbackController::class, 'adminIndex'])
            ->name('retours.index');

        Route::delete('/retours/{id}', [LessonFeedbackController::class, 'destroy'])
            ->name('retours.delete');

        // Pilotage (projets/taches + journal + notifications)
        Route::prefix('pilotage')->name('pilotage.')->group(function () {
            Route::get('/', [PilotageController::class, 'index'])->name('index');
            Route::post('/projects', [PilotageController::class, 'storeProject'])->name('projects.store');
            Route::put('/projects/{project}', [PilotageController::class, 'updateProject'])->name('projects.update');
            Route::delete('/projects/{project}', [PilotageController::class, 'destroyProject'])->name('projects.destroy');

            Route::post('/tasks', [PilotageController::class, 'storeTask'])->name('tasks.store');
            Route::get('/tasks/{task}/edit', [PilotageController::class, 'editTask'])->name('tasks.edit');
            Route::put('/tasks/{task}', [PilotageController::class, 'updateTask'])->name('tasks.update');
            Route::delete('/tasks/{task}', [PilotageController::class, 'destroyTask'])->name('tasks.destroy');
            Route::post('/tasks/{task}/move', [PilotageController::class, 'moveTask'])->name('tasks.move');
            Route::post('/tasks/{task}/comments', [PilotageController::class, 'storeComment'])->name('tasks.comments.store');

            Route::get('/journal', [PilotageController::class, 'journal'])->name('journal');
            Route::get('/qualite-parcours-formateur', [TrainerPathQualityController::class, 'index'])
                ->name('qualite-parcours-formateur');
            Route::get('/consommation-ia', [ConsommationIAController::class, 'index'])
                ->name('consommation-ia');

            Route::get('/notifications', [PilotageController::class, 'notifications'])->name('notifications.index');
            Route::post('/notifications/read-all', [PilotageController::class, 'markAllNotificationsRead'])->name('notifications.read-all');
            Route::post('/notifications/{notificationId}/read', [PilotageController::class, 'markNotificationRead'])->name('notifications.read');
            Route::post('/preferences', [PilotageController::class, 'updatePreferences'])->name('preferences.update');
        });


        /*
    |--------------------------------------------------------------------------
    | SCORM – Import (utilisé depuis l’édition de leçon)
    |--------------------------------------------------------------------------
    */
    // routes/admin.php
    Route::prefix('scorm')->name('scorm.')->group(function () {
        Route::post('/import-lecture', [ScormLibraryController::class, 'importForLecture'])->name('import');
    });

    Route::prefix('slides')->name('slides.')->group(function () {
        Route::post('/import-lecture', [ModuleLectureController::class, 'importSlidesForLecture'])->name('import');
        Route::post('/retry-lecture', [ModuleLectureController::class, 'retrySlidesForLecture'])->name('retry');
    });

    // Compétences
    Route::prefix('competences')->name('competencies.')->group(function () {
    Route::get('/', [CompetencyController::class, 'index'])->name('index');
    Route::post('/', [CompetencyController::class, 'store'])->name('store');
    Route::post('/{competency}/toggle', [CompetencyController::class, 'toggle'])->name('toggle');
    Route::delete('/{competency}', [CompetencyController::class, 'destroy'])->name('destroy');
});


    // Badges (liste + création + édition)
    Route::get('/badges', [BadgeController::class, 'index'])->name('badges.index');
    Route::get('/badges/create', [BadgeController::class, 'create'])->name('badges.create');
    Route::post('/badges', [BadgeController::class, 'store'])->name('badges.store');

        Route::get('/badges/{badge}/edit', [BadgeController::class, 'edit'])->name('badges.edit');
        Route::post('/badges/{badge}', [BadgeController::class, 'update'])->name('badges.update');
        Route::delete('/badges/{badge}', [BadgeController::class, 'destroy'])
    ->name('badges.destroy');

    // Jeux - Nuage de mot
    Route::prefix('nuage')->name('nuage.')->group(function () {
        Route::get('/', [WordCloudController::class, 'index'])->name('index');
        Route::get('/create', [WordCloudController::class, 'create'])->name('create');
        Route::post('/', [WordCloudController::class, 'store'])->name('store');

        Route::get('/{wordCloud}', [WordCloudController::class, 'show'])->name('show');
        Route::get('/{wordCloud}/edit', [WordCloudController::class, 'edit'])->name('edit');
        Route::put('/{wordCloud}', [WordCloudController::class, 'update'])->name('update');
        Route::delete('/{wordCloud}', [WordCloudController::class, 'destroy'])->name('destroy');

        Route::get('/{wordCloud}/live', [WordCloudController::class, 'live'])->name('live');
        Route::get('/{wordCloud}/live/data', [WordCloudController::class, 'liveData'])->name('live.data');
    });

    });
