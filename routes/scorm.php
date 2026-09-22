<?php

use App\Http\Controllers\ContentBlockScormController;
use App\Http\Controllers\EvaluationSCORMController;
use App\Http\Controllers\Frontend\LectureController;
use App\Http\Controllers\SCORMController;
use Illuminate\Support\Facades\Route;

// Les iframes de même origine transmettent le jeton CSRF de leur page parente.
Route::post('/scorm/save-progress', [SCORMController::class, 'saveProgress'])
    ->middleware('auth');

Route::post('/scorm/progress', [SCORMController::class, 'saveProgress'])
    ->middleware('auth');

Route::post('/scorm/save-block-progress', [ContentBlockScormController::class, 'saveProgress'])
    ->middleware('auth');

Route::get('/lecture/{id}/scorm', [LectureController::class, 'showScorm'])
    ->middleware('auth')
    ->name('lecture.scorm');
Route::get('/lecture/{id}/scorm-block/{key}', [LectureController::class, 'showScormBlock'])
    ->middleware('auth')
    ->name('lecture.scorm-block');
Route::get('/lecture/{id}/slides', [LectureController::class, 'showSlides'])
    ->middleware('auth')
    ->name('lecture.slides');

Route::post('/scorm/evaluation-progress', [EvaluationSCORMController::class, 'saveEvaluationProgress'])
    ->middleware('auth');
