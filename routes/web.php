<?php

use App\Http\Controllers\BuzzerParticipationController;
use App\Http\Controllers\ComponentFinderParticipationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EmargementJoinController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PollParticipationController;
use App\Http\Controllers\QuestionWallParticipationController;
use App\Http\Controllers\RoueAleatoireParticipationController;
use App\Http\Controllers\ScaleParticipationController;
use App\Http\Controllers\VraiFauxParticipationController;
use App\Http\Controllers\WordCloudParticipationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// -------------------------------------------------------------------------
// Fichier de routes publiques de l'application. Les routes des différents
// rôles (admin, formateur, stagiaire) sont importées plus bas.
// -------------------------------------------------------------------------
// ----------------------------------------------------------
// 🌐 Pages publiques générales
// ----------------------------------------------------------
// Page d'accueil du site
Route::get('/', [\App\Http\Controllers\UserController::class, 'Index'])->name('index');
// Sitemap XML pour les moteurs de recherche
Route::get('/sitemap.xml', [\App\Http\Controllers\Frontend\SitemapController::class, 'index'])->name('sitemap');
// Présentation du projet Onéduc
Route::get('/le-projet-oneduc-fr', [\App\Http\Controllers\UserController::class, 'Projet'])->name('projet');
// Présentation de l'association
Route::get('/association', [\App\Http\Controllers\UserController::class, 'Association'])->name('association');
// Page d'adhésion pour rejoindre l'association
Route::get('/adhesion', [\App\Http\Controllers\UserController::class, 'Adhesion'])->name('adhesion');
// Charte graphique publique
Route::view('/chartegraphique', 'frontend.contenu.charte_graphique')->name('charte-graphique');
// Affiche les modules liés à une catégorie (accessible sans connexion)
Route::get('/categorie/{id}/modules', [\App\Http\Controllers\Backend\CategoryController::class, 'showCategoryModules'])->name('frontend.category.modules');
// --- Pages légales et conformité ---
Route::view('/mentions-legales', 'frontend.contenu.mentions-legales')->name('mentions-legales');
Route::view('/conditions-utilisation', 'frontend.contenu.conditions-utilisation')->name('conditions-utilisation');
Route::view('/confidentialite', 'frontend.contenu.confidentialite')->name('confidentialite');
Route::view('/cookies', 'frontend.contenu.cookies')->name('cookies');
Route::view('/accessibilite', 'frontend.contenu.accessibilite')->name('accessibilite');
Route::view('/accessibilite/schema-pluriannuel', 'frontend.contenu.schema-pluriannuel-accessibilite')
    ->name('accessibilite.schema');
Route::view('/accessibilite/plan-action-2026', 'frontend.contenu.plan-action-accessibilite-2026')
    ->name('accessibilite.plan-2026');

// Route pour le Hub de connexion (Version corrigée)
Route::get('/connexion-choix', function () {
    // Si le fichier est dans resources/views/frontend/contenu/
    return view('frontend.contenu.login-hub');
})->name('login.selection');

// Diffusion d'un média stocké sur le disque Laravel "public" (storage/app/public)
Route::get('/media/storage/{path}', function (string $path) {
    $path = ltrim($path, '/');
    if ($path === '' || str_contains($path, '..') || str_contains($path, "\0")) {
        abort(404);
    }

    $disk = Storage::disk('public');
    if (! $disk->exists($path)) {
        abort(404);
    }

    return response()->file($disk->path($path));
})->where('path', '.*')->name('media.storage');

Route::get('/stagiaire/connexion', [\App\Http\Controllers\UserController::class, 'showCodeLoginForm'])->name('stagiaire.code.form');

// ----------------------------------------------------------
// 🧠 Catégories & sous-catégories
// ----------------------------------------------------------
// Liste l'ensemble des catégories de formation
Route::get('/formations', [\App\Http\Controllers\Backend\CategoryController::class, 'FrontCategories'])->name('categories.all');
// Affiche les sous-catégories d'une catégorie donnée
Route::get('/categorie/{id}/sous-categories', [\App\Http\Controllers\Backend\CategoryController::class, 'showSubCategories'])->name('frontend.subcategory.modules');

// Route::get('/categories/{id}/subcategories', [\App\Http\Controllers\Backend\CategoryController::class, 'showSubCategories'])->name('frontend.subcategories');
// Route::get('/categories/{id}/modules', [\App\Http\Controllers\Backend\CategoryController::class, 'showCategoryModules'])->name('frontend.category.modules');

// ----------------------------------------------------------
// 📚 Modules de formation
// ----------------------------------------------------------
// Liste des modules de formation disponibles
Route::get('/MFormations', [\App\Http\Controllers\Frontend\MFormationsController::class, 'index'])->name('frontend.modules.index');
// Détail d'un module dans sa hiérarchie pédagogique (URL canonique)
Route::get('/categorie/{category}/modules/{module}', [\App\Http\Controllers\Frontend\MFormationsController::class, 'show'])
    ->name('frontend.modules.show');
// Ancienne URL conservée pour compatibilité + redirection SEO vers l'URL canonique
Route::get('/modules/{module}', [\App\Http\Controllers\Frontend\MFormationsController::class, 'showLegacy'])
    ->name('frontend.modules.show.legacy');
// ----------------------------------------------------------
// 📚 Evaluation
// ----------------------------------------------------------
// Les évaluations sont protégées par l'authentification
Route::middleware(['auth'])->group(function () {
    Route::get('/evaluations/{id}', [\App\Http\Controllers\Backend\EvaluationController::class, 'show'])->name('evaluation.show');
});
Route::middleware(['auth'])->group(function () {
    Route::get('/evaluations/{evaluation}/fin',
        [\App\Http\Controllers\Backend\EvaluationController::class, 'fin']
    )->name('stagiaire.evaluations.fin');
});
// ----------------------------------------------------------
// 🎥 Lecture d’une leçon
// ----------------------------------------------------------
// Affiche une leçon précise
Route::get('/lecture/{id}', [\App\Http\Controllers\Frontend\LectureController::class, 'show'])
    ->middleware('auth')
    ->name('lecture.show');

// ----------------------------------------------------------
// ☁️ Jeu - Nuage de mot (participation)
// Authentification + appartenance groupe requises, comme les
// 5 autres outils live (Sondage, Mur de questions, Quiz live,
// Tableau blanc, Minuteur).
// ----------------------------------------------------------
if (\App\Support\Outils\EtatsOutils::actif('nuage_mots')) {
    Route::middleware('auth')->group(function () {
        Route::get('/oneduc/mot', [WordCloudParticipationController::class, 'home'])->name('wordcloud.join');
        Route::post('/oneduc/mot', [WordCloudParticipationController::class, 'resolveCode'])->name('wordcloud.resolve');
        Route::get('/oneduc/mot/{code}', [WordCloudParticipationController::class, 'joinByCode'])->name('wordcloud.join.code');
        Route::post('/oneduc/mot/{code}', [WordCloudParticipationController::class, 'submit'])
            ->middleware('throttle:30,1')
            ->name('wordcloud.submit');
        Route::get('/oneduc/mot/{code}/state', [WordCloudParticipationController::class, 'state'])
            ->name('wordcloud.state');
        Route::get('/oneduc/mot/{code}/data', [WordCloudParticipationController::class, 'liveData'])
            ->name('wordcloud.live.data');
    });
}

// ----------------------------------------------------------
// 🎡 Roue aléatoire (participation)
// Authentification + appartenance groupe requises, même
// justification que le Nuage de mots ci-dessus.
// ----------------------------------------------------------
if (\App\Support\Outils\EtatsOutils::actif('roue_aleatoire')) {
    Route::middleware('auth')->group(function () {
        Route::get('/oneduc/roue/{code}', [RoueAleatoireParticipationController::class, 'show'])->name('roue.join');
        Route::get('/oneduc/roue/{code}/state', [RoueAleatoireParticipationController::class, 'state'])->name('roue.state');
    });
}

// ----------------------------------------------------------
// ❓ Mur de questions (participation)
// ----------------------------------------------------------
if (\App\Support\Outils\EtatsOutils::actif('mur_questions')) {
    Route::get('/oneduc/questions/{code}', [QuestionWallParticipationController::class, 'joinByCode'])
        ->name('questions.join.code');
    Route::post('/oneduc/questions/{code}/questions', [QuestionWallParticipationController::class, 'submitQuestion'])
        ->middleware(['auth', 'throttle:30,1'])
        ->name('questions.submit');
    Route::post('/oneduc/questions/{code}/questions/{question}/vote', [QuestionWallParticipationController::class, 'toggleVote'])
        ->middleware(['auth', 'throttle:60,1'])
        ->name('questions.vote.toggle');
}

// ----------------------------------------------------------
// 📊 Sondage (participation)
// ----------------------------------------------------------
if (\App\Support\Outils\EtatsOutils::actif('sondage')) {
    Route::middleware(['auth'])->group(function () {
        Route::get('/oneduc/sondage', [PollParticipationController::class, 'home'])->name('sondages.join');
        Route::post('/oneduc/sondage', [PollParticipationController::class, 'resolveCode'])->name('sondages.resolve');
        Route::get('/oneduc/sondage/{code}', [PollParticipationController::class, 'joinByCode'])->name('sondages.join.code');
        Route::post('/oneduc/sondage/{code}/reponses', [PollParticipationController::class, 'submit'])
            ->middleware('throttle:60,1')
            ->name('sondages.submit');
        Route::get('/oneduc/sondage/{code}/data', [PollParticipationController::class, 'data'])->name('sondages.data');
    });
}

// ----------------------------------------------------------
// ✍️ Émargement (raccourci d'accès QR / code court)
// ----------------------------------------------------------
if (\App\Support\Outils\EtatsOutils::actif('emargement')) {
    Route::middleware(['auth'])->group(function () {
        Route::get('/oneduc/emargement', [EmargementJoinController::class, 'home'])->name('emargement.join');
        Route::post('/oneduc/emargement', [EmargementJoinController::class, 'resolveCode'])
            ->middleware('throttle:emargement-code')
            ->name('emargement.resolve');
        Route::get('/oneduc/emargement/{code}', [EmargementJoinController::class, 'joinByCode'])
            ->middleware('throttle:emargement-code')
            ->name('emargement.join.code');
    });
}

// ----------------------------------------------------------
// ✅ Vrai/Faux (participation)
// ----------------------------------------------------------
if (\App\Support\Outils\EtatsOutils::actif('vraifaux')) {
    Route::middleware(['auth'])->group(function () {
        Route::get('/oneduc/vrai-faux', [VraiFauxParticipationController::class, 'home'])->name('vraifaux.join');
        Route::post('/oneduc/vrai-faux', [VraiFauxParticipationController::class, 'resolveCode'])->name('vraifaux.resolve');
        Route::get('/oneduc/vrai-faux/{code}', [VraiFauxParticipationController::class, 'joinByCode'])->name('vraifaux.join.code');
        Route::post('/oneduc/vrai-faux/{code}/reponses', [VraiFauxParticipationController::class, 'submit'])
            ->middleware('throttle:60,1')
            ->name('vraifaux.submit');
        Route::get('/oneduc/vrai-faux/{code}/data', [VraiFauxParticipationController::class, 'data'])->name('vraifaux.data');
    });
}

// ----------------------------------------------------------
// 📏 Échelle de positionnement (participation)
// ----------------------------------------------------------
if (\App\Support\Outils\EtatsOutils::actif('echelle')) {
    Route::middleware(['auth'])->group(function () {
        Route::get('/oneduc/echelle', [ScaleParticipationController::class, 'home'])->name('echelle.join');
        Route::post('/oneduc/echelle', [ScaleParticipationController::class, 'resolveCode'])->name('echelle.resolve');
        Route::get('/oneduc/echelle/{code}', [ScaleParticipationController::class, 'joinByCode'])->name('echelle.join.code');
        Route::post('/oneduc/echelle/{code}/reponses', [ScaleParticipationController::class, 'submit'])
            ->middleware('throttle:60,1')
            ->name('echelle.submit');
        Route::get('/oneduc/echelle/{code}/data', [ScaleParticipationController::class, 'data'])->name('echelle.data');
    });
}

// ----------------------------------------------------------
// 🔍 Zone de clic (participation)
// ----------------------------------------------------------
if (\App\Support\Outils\EtatsOutils::actif('composants')) {
    Route::middleware(['auth'])->group(function () {
        Route::get('/oneduc/composant', [ComponentFinderParticipationController::class, 'home'])->name('composants.join');
        Route::post('/oneduc/composant', [ComponentFinderParticipationController::class, 'resolveCode'])->name('composants.resolve');
        Route::get('/oneduc/composant/{code}', [ComponentFinderParticipationController::class, 'joinByCode'])->name('composants.join.code');
        Route::post('/oneduc/composant/{code}/reponses', [ComponentFinderParticipationController::class, 'submit'])
            ->middleware('throttle:60,1')
            ->name('composants.submit');
    });
}

// ----------------------------------------------------------
// 🔔 Buzzer Quiz (participation)
// ----------------------------------------------------------
if (\App\Support\Outils\EtatsOutils::actif('buzzer')) {
    Route::middleware(['auth'])->group(function () {
        Route::get('/oneduc/buzzer', [BuzzerParticipationController::class, 'home'])->name('buzzer.join');
        Route::post('/oneduc/buzzer', [BuzzerParticipationController::class, 'resolveCode'])->name('buzzer.resolve');
        Route::get('/oneduc/buzzer/{code}', [BuzzerParticipationController::class, 'joinByCode'])->name('buzzer.join.code');
        Route::post('/oneduc/buzzer/{code}/buzz', [BuzzerParticipationController::class, 'buzz'])
            ->middleware('throttle:30,1')
            ->name('buzzer.buzz');
        Route::get('/oneduc/buzzer/{code}/snapshot', [BuzzerParticipationController::class, 'snapshot'])
            ->middleware('throttle:120,1')
            ->name('buzzer.snapshot');
    });
}

// ----------------------------------------------------------
// 🔐 Authentification
// ----------------------------------------------------------
// Formulaire de connexion
Route::get('/connexion', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('connexion');
// Traitement de la connexion
Route::post('/connexion', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store'])->name('login.process');
// Déconnexion de l'utilisateur
Route::post('/logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
// Formulaire d'inscription classique
// UserController::Register() n'existe plus : redirection vers l'inscription formateur, seul parcours d'inscription fonctionnel
Route::redirect('/inscription', '/inscription-formateur', 301)->name('inscription');

// Envoi de feedback sur une leçon (uniquement pour les utilisateurs connectés)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        return match ($user?->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'formateur' => redirect()->route('formateur.dashboard'),
            'observateur' => redirect()->route('observateur.dashboard'),
            'stagiaire' => redirect()->route('stagiaire.dashboard'),
            default => redirect()->route('index'),
        };
    })->name('dashboard');

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])
        ->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::post('/lecture/{id}/valider', [\App\Http\Controllers\Frontend\LectureController::class, 'valider'])->name('lecture.valider');
    Route::post('/feedback', [\App\Http\Controllers\LessonFeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/feedback/{lesson}', [\App\Http\Controllers\LessonFeedbackController::class, 'index'])->name('feedback.index');
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

// ----------------------------------------------------------
// 🟢 Admin
// ----------------------------------------------------------
// Routes spécifiques au rôle administrateur
require __DIR__.'/admin.php';
require __DIR__.'/admin-constructeur-formations.php';
require __DIR__.'/admin-modeles-parcours.php';

// ----------------------------------------------------------
// 🟠 Formateur
// ----------------------------------------------------------
// Routes dédiées aux formateurs
require __DIR__.'/formateur.php';

// ----------------------------------------------------------
// 🔵 Observateur
// ----------------------------------------------------------
require __DIR__.'/observateur.php';

// ----------------------------------------------------------
// 🟣 Stagiaire
// ----------------------------------------------------------
// Routes accessibles aux stagiaires
require __DIR__.'/stagiaire.php';

// routes/web.php
// ... après les autres imports de routes
require __DIR__.'/scorm.php';
// ----------------------------------------------------------
// Api
// ----------------------------------------------------------
// Endpoints API divers
require __DIR__.'/api.php';

// Inscription formateur
// ----------------------------------------------------------
// Formulaire d'inscription dédié aux formateurs
Route::get('/inscription-formateur', function () {
    return view('frontend.formateur.reg_formateur');
})->name('formateur.inscription.form');
// Traitement de l'inscription formateur
Route::post('/inscription-formateur', [\App\Http\Controllers\Formateur\FormateurProfileController::class, 'register'])->name('formateur.inscription');

// Connexion via code d’accès (stagiaire)
// Connexion d'un stagiaire via un code d'accès fourni par le formateur
Route::get('/stagiaire/connexion-code', [\App\Http\Controllers\UserController::class, 'showCodeLoginForm'])->name('stagiaire.code.form.legacy');
Route::post('/stagiaire/connexion-code', [\App\Http\Controllers\UserController::class, 'loginByCode'])->middleware('throttle:connexion-code')->name('stagiaire.code.login');

// Formulaire de contact
Route::middleware(['throttle:contact'])->group(function () {
    Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');
});
// Affichage du formulaire de contact
Route::get('/contact', [ContactController::class, 'index'])->name('contact'); // GET pour la page

// Auth Laravel (Jetstream/Breeze)
// Routes d'authentification fournies par Laravel Breeze/Jetstream
require __DIR__.'/auth.php';
