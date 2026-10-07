<?php

use App\Http\Controllers\Backend\ConstructeurFormationController;
use App\Http\Controllers\Backend\QuizQuestionController;
use App\Http\Controllers\Backend\VersionFormationCatalogueController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'admin.activity'])
    ->prefix('admin/formations/constructeur')
    ->name('admin.formations.constructeur.')
    ->group(function () {
        Route::get('/', [ConstructeurFormationController::class, 'index'])->name('index');
        Route::get('/consommation-ia', [ConstructeurFormationController::class, 'consommationIA'])->name('consommation-ia');
        Route::get('/creer', [ConstructeurFormationController::class, 'create'])->name('create');
        Route::post('/', [ConstructeurFormationController::class, 'store'])->name('store');
        Route::post('/generer-ia', [ConstructeurFormationController::class, 'genererStructureIA'])->name('generate-structure-ia');
        Route::post('/depuis-formateur/{module}/dupliquer', [VersionFormationCatalogueController::class, 'duplicateTrainerCreation'])->name('duplicate');

        Route::post('/{module}/images', [ConstructeurFormationController::class, 'uploadImage'])->name('images.store');
        Route::post('/{module}/videos', [ConstructeurFormationController::class, 'uploadVideo'])->name('videos.store');
        Route::post('/{module}/audios', [ConstructeurFormationController::class, 'uploadAudio'])->name('audios.store');
        Route::post('/{module}/scorm', [ConstructeurFormationController::class, 'uploadScorm'])->name('scorm.store');
        Route::get('/{module}/apercu', [ConstructeurFormationController::class, 'preview'])->name('preview');
        Route::get('/{module}/edition', [ConstructeurFormationController::class, 'edit'])->name('edit');
        Route::post('/{module}/versions', [VersionFormationCatalogueController::class, 'store'])->name('versions.store');
        Route::post('/{module}/publier', [VersionFormationCatalogueController::class, 'publish'])->name('publish');
        Route::post('/{module}/archiver', [VersionFormationCatalogueController::class, 'archive'])->name('archive');
        Route::put('/{module}/basculer-groupes', [VersionFormationCatalogueController::class, 'switchGroups'])->name('versions.groups.switch');
        Route::put('/{module}', [ConstructeurFormationController::class, 'update'])->name('update');
        Route::put('/{module}/options', [ConstructeurFormationController::class, 'updateOptions'])->name('options.update');
        Route::delete('/{module}', [ConstructeurFormationController::class, 'destroy'])->name('destroy');

        Route::post('/{module}/sections', [ConstructeurFormationController::class, 'storeSection'])->name('sections.store');
        Route::put('/sections/{section}', [ConstructeurFormationController::class, 'updateSection'])->name('sections.update');
        Route::delete('/sections/{section}', [ConstructeurFormationController::class, 'destroySection'])->name('sections.destroy');
        Route::post('/{module}/sections/reorder', [ConstructeurFormationController::class, 'reorderSections'])->name('sections.reorder');

        Route::post('/sections/{section}/lectures', [ConstructeurFormationController::class, 'storeLecture'])->name('lectures.store');
        Route::post('/sections/{section}/lectures/generer-ia', [ConstructeurFormationController::class, 'genererLeconIA'])->name('lectures.generate-ia');
        Route::get('/lectures/{lecture}/edition', [ConstructeurFormationController::class, 'editLecture'])->name('lectures.edit');
        Route::post('/lectures/{lecture}/generer-audio', [ConstructeurFormationController::class, 'generateAudioLecture'])->name('lectures.generate-audio');
        Route::put('/lectures/{lecture}', [ConstructeurFormationController::class, 'updateLecture'])->name('lectures.update');
        Route::put('/lectures/{lecture}/pedagogie', [ConstructeurFormationController::class, 'modifierPedagogie'])->name('lectures.pedagogie');
        Route::post('/lectures/{lecture}/support', [ConstructeurFormationController::class, 'importerSupport'])->name('lectures.support.store');
        Route::post('/lectures/{lecture}/support/relancer', [ConstructeurFormationController::class, 'relancerSlides'])->name('lectures.support.relancer');
        Route::get('/lectures/{lecture}/apercu-scorm', [ConstructeurFormationController::class, 'apercuScorm'])->name('lectures.apercu-scorm');
        Route::post('/lectures/{lecture}/ressources', [ConstructeurFormationController::class, 'ajouterRessource'])->name('lectures.ressources.store');
        Route::put('/lectures/{lecture}/ressources/{resource}/visibilite', [ConstructeurFormationController::class, 'modifierVisibiliteRessource'])->name('lectures.ressources.visibilite');
        Route::delete('/lectures/{lecture}/ressources/{resource}', [ConstructeurFormationController::class, 'supprimerRessource'])->name('lectures.ressources.destroy');
        Route::delete('/lectures/{lecture}', [ConstructeurFormationController::class, 'destroyLecture'])->name('lectures.destroy');
        Route::post('/lectures/{lecture}/duplicate', [ConstructeurFormationController::class, 'duplicateLecture'])->name('lectures.duplicate');
        Route::post('/sections/{section}/lectures/reorder', [ConstructeurFormationController::class, 'reorderLectures'])->name('lectures.reorder');
        Route::post('/lectures/{lecture}/move', [ConstructeurFormationController::class, 'moveLecture'])->name('lectures.move');
        Route::post('/lectures/{lecture}/promote', [ConstructeurFormationController::class, 'promoteLectureToSection'])->name('lectures.promote');

        Route::put('/{module}/groupes', [ConstructeurFormationController::class, 'assignGroups'])->name('groups.sync');

        Route::prefix('/lectures/{lecture}/quiz/questions')->name('lectures.quiz.questions.')->group(function () {
            Route::get('/', [QuizQuestionController::class, 'index'])->name('index');
            Route::get('/creer', [QuizQuestionController::class, 'create'])->name('create');
            Route::post('/', [QuizQuestionController::class, 'store'])->name('store');
            Route::post('/import', [QuizQuestionController::class, 'importCsv'])->name('import');
            Route::get('/import/modele', [QuizQuestionController::class, 'downloadCsvTemplate'])->name('import.template');
            Route::post('/generer-ia', [QuizQuestionController::class, 'generateIA'])->name('generate-ia');
            Route::get('/{question}/edition', [QuizQuestionController::class, 'edit'])->name('edit');
            Route::put('/{question}', [QuizQuestionController::class, 'update'])->name('update');
            Route::delete('/{question}', [QuizQuestionController::class, 'destroy'])->name('destroy');
        });

        Route::get('/{module}/quiz-questions', [QuizQuestionController::class, 'moduleIndex'])->name('quiz-questions.index');
        Route::post('/quiz-questions/{question}/deplacer', [QuizQuestionController::class, 'move'])->name('quiz-questions.move');
    });
