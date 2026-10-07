<?php

namespace App\Http\Controllers;

use App\Domains\Learners\Support\LearnerModuleProgress;
use App\Models\BuzzerAttempt;
use App\Models\BuzzerSession;
use App\Models\ComponentFinderAttempt;
use App\Models\ComponentFinderSession;
use App\Models\FormateurMessage;
use App\Models\Group;
use App\Models\GroupTimer;
use App\Models\GroupWhiteboard;
use App\Models\GroupWhiteboardItem;
use App\Models\LessonFeedback;
use App\Models\LiveQuizSession;
use App\Models\LiveQuizSessionParticipant;
use App\Models\Module;
use App\Models\ModuleLecture;
use App\Models\PollSession;
use App\Models\PollSessionResponse;
use App\Models\Progression;
use App\Models\QuestionWall;
use App\Models\QuestionWallQuestion;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptQuestion;
use App\Models\RandomWheelSession;
use App\Models\ScaleSession;
use App\Models\ScaleSessionResponse;
use App\Models\ScormEvaluationScore;
use App\Models\ScormInteraction;
use App\Models\ScormScore;
use App\Models\Seance;
use App\Models\SeancePresence;
use App\Models\TrueFalseSession;
use App\Models\TrueFalseSessionResponse;
use App\Models\VideoSegmentTracking;
use App\Models\WordCloud;
use App\Models\WordCloudEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StagiaireController extends Controller
{
    public function __construct(
        private readonly LearnerModuleProgress $moduleProgress,
    ) {}

    /** ========= DETAIL MODULE ========= */
    public function StagiaireModuleDetail($id)
    {
        $user = auth()->user();

        // Module actif + formateur + sections + leçons
        $module = Module::with(['formateur', 'sections.lectures.objectives'])
            ->active()
            ->findOrFail($id);

        abort_unless($module->isVisibleTo($user), 403);

        // 1. Compter les réponses SCORM par leçon
        $scormCounts = ScormInteraction::where('user_id', $user->id)
            ->select('lecture_id', DB::raw('count(*) as total'))
            ->groupBy('lecture_id')
            ->pluck('total', 'lecture_id');

        $lectureIds = $module->sections->flatMap->lectures->pluck('id')->values()->all();
        [$quizAttempts, $quizAttemptAgg] = $this->moduleProgress->latestQuizAttemptsData($lectureIds, (int) $user->id);

        // Statut par leçon : priorité aux tentatives réelles pour les quiz natifs.
        $lessonStatuses = [];
        foreach ($module->sections->flatMap->lectures as $lecture) {
            if ((bool) ($lecture->quiz_enabled ?? false)) {
                $attempt = $quizAttempts->get($lecture->id);
                $agg = $attempt ? $quizAttemptAgg->get($attempt->id) : null;
                $status = $this->moduleProgress->quizProgressStatus($attempt, $agg);
            } else {
                $nbScorm = (int) ($scormCounts[$lecture->id] ?? 0);
                $status = $nbScorm > 0 ? 'completed' : 'not_started';
            }

            $lessonStatuses[$lecture->id] = $status;
        }

        // Progression par section
        $sectionProgress = [];
        foreach ($module->sections as $section) {
            $total = $section->lectures->count();
            $completed = collect($section->lectures)
                ->filter(fn ($lec) => ($lessonStatuses[$lec->id] ?? 'not_started') === 'completed')
                ->count();
            $sectionProgress[$section->id] = ['total' => $total, 'completed' => $completed];
        }

        // Progression globale
        $totalLectures = $module->sections->flatMap->lectures->count();
        $completedLectures = collect($lessonStatuses)->filter(fn ($s) => $s === 'completed')->count();
        $progression = $totalLectures > 0 ? (int) round(($completedLectures / $totalLectures) * 100) : 0;

        // Objectifs pédagogiques issus des leçons (agrégés et sans doublon)
        $lessonObjectives = $module->sections
            ->flatMap->lectures
            ->flatMap(function ($lecture) {
                return $lecture->objectives->pluck('title');
            })
            ->map(fn ($title) => trim((string) $title))
            ->filter()
            ->unique()
            ->values();

        $estimatedDurationLabel = $module->getFormattedDurationForUser($user->id);

        return view('stagiaire.stagiaire_module_detail', compact(
            'module',
            'lessonStatuses',
            'sectionProgress',
            'progression',
            'lessonObjectives',
            'estimatedDurationLabel'
        ));
    }

    /** ========= DASHBOARD (Mise à jour : Fusion SCORM + Quiz) ========= */
    public function StagiaireDashboard()
    {
        $user = auth()->user();
        $userId = $user->id;

        // --- 1. STATS VIDÉO ---
        $videoStats = VideoSegmentTracking::where('user_id', $userId)
            ->selectRaw('SUM(total_watch_time) as watch_time, COUNT(*) as segments, SUM(watch_count) - COUNT(*) as replays')
            ->first();

        $videoTime = (int) ($videoStats->watch_time ?? 0);
        $totalVideoSegments = (int) ($videoStats->segments ?? 0);
        $totalVideoReplays = (int) ($videoStats->replays ?? 0);

        // --- 2. TEMPS D'APPRENTISSAGE (ENGAGEMENT) ---
        // Temps SCORM
        $scormTime = (int) ScormScore::where('user_id', $userId)->sum('session_time');
        // Temps Quiz Natifs
        $quizTime = (int) DB::table('quiz_attempts')->where('user_id', $userId)->sum('total_time_seconds');

        // Temps total passé à "travailler" (Vidéos + Exercices)
        $engagementTotal = $scormTime + $quizTime + $videoTime;

        // Temps de connexion global (Session)
        $totalSiteTime = (int) ($user->total_site_time ?? 0);

        // --- 3. QUESTIONS & RÉUSSITE (FUSION) ---
        // A. SCORM
        $scormInteractions = ScormInteraction::where('user_id', $userId)
            ->whereIn('result', ['correct', 'wrong'])
            ->get();
        $scormTotal = $scormInteractions->count();
        $scormCorrect = $scormInteractions->where('result', 'correct')->count();

        // B. Quiz Natifs
        $quizQuestions = DB::table('quiz_attempt_questions')
            ->join('quiz_attempts', 'quiz_attempt_questions.attempt_id', '=', 'quiz_attempts.id')
            ->where('quiz_attempts.user_id', $userId)
            ->whereNotNull('quiz_attempt_questions.answered_at')
            ->select('quiz_attempt_questions.is_correct', 'quiz_attempt_questions.time_seconds')
            ->get();
        $quizTotal = $quizQuestions->count();
        $quizCorrect = $quizQuestions->where('is_correct', 1)->count();

        // Totaux fusionnés
        $answeredCount = $scormTotal + $quizTotal;
        $totalCorrect = $scormCorrect + $quizCorrect;

        // Taux de réussite global
        $tauxBonnesReponses = $answeredCount > 0 ? (int) round(($totalCorrect / $answeredCount) * 100) : 0;

        // --- 4. LATENCE MOYENNE (RÉFLEXION) ---
        $totalLatency = 0;
        // Latence SCORM
        foreach ($scormInteractions as $interaction) {
            if ($interaction->latency) {
                try {
                    [$h, $m, $s] = array_pad(explode(':', $interaction->latency), 3, 0);
                    $totalLatency += ((int) $h * 3600 + (int) $m * 60 + (int) $s);
                } catch (\Exception $e) {
                }
            }
        }
        // Latence Quiz (déjà en secondes)
        $totalLatency += $quizQuestions->sum('time_seconds');

        $averageLatencyTime = $answeredCount > 0 ? (int) round($totalLatency / $answeredCount) : 0;

        // --- 5. GROUPES & MODULES ---
        $groupes = $user->groupesStagiaire()
            ->active()
            ->with([
                'modules' => function ($q) {
                    $q->withPivot('position')->orderBy('group_module.position', 'asc');
                },
                'modules.sections.lectures:id,section_id,module_id,quiz_questions_per_attempt',
                'instructor',
            ])
            ->get();

        $modules = $groupes->flatMap->modules->unique('id')->values();
        $formateur = $groupes->first()?->instructor;

        // Calcul de la progression par module
        $this->moduleProgress->attachProgressAttributes($modules, $userId);

        // Progression Globale (Moyenne des modules en cours)
        $progressionGlobale = $modules->isNotEmpty() ? (int) round($modules->avg('progression_percent')) : 0;

        // --- 6. EXTRAS (Commentaires, Evals...) ---
        $commentairesTotal = LessonFeedback::withTrashed()->where('user_id', $userId)->count();

        // On garde la logique Eval SCORM existante pour l'instant
        $scormEvalScores = ScormEvaluationScore::with('evaluation')->where('user_id', $userId)->get();
        $totalEvaluationsDone = $scormEvalScores->count();
        $bestEvaluationScore = $scormEvalScores->max('best_score');

        return view('stagiaire.index', compact(
            'progressionGlobale',
            'groupes',
            'modules',
            'formateur',
            'engagementTotal',    // Nouveau : Temps d'apprentissage réel
            'totalSiteTime',      // Ancien : Temps de connexion
            'answeredCount',
            'tauxBonnesReponses',
            'averageLatencyTime', // Nouveau : Latence
            'videoTime',
            'commentairesTotal',
            'totalVideoSegments',
            'totalEvaluationsDone',
            'bestEvaluationScore'
        ));
    }

    /** ========= LISTE MODULES ========= */
    public function StagiaireModules()
    {
        $user = Auth::user();

        // 1. On récupère le groupe avec ses modules, en demandant explicitement la colonne de pivot 'position'
        // et en triant par celle-ci.
        $group = Group::with([
            'modules' => function ($query) {
                $query->active()
                    ->with(['sections.lectures:id,section_id,module_id,duration,question_count,quiz_enabled,quiz_questions_per_attempt'])
                    ->withPivot('position')
                    ->orderBy('group_module.position', 'asc');
            },
            'formateurParcours' => function ($query) {
                $query->with(['items' => fn ($q) => $q->orderBy('position')]);
            },
        ])
            ->active()
            ->whereHas('students', fn ($q) => $q->where('email', $user->email))
            ->first();

        $modules = $group ? $group->modules : collect();
        $this->moduleProgress->attachProgressAttributes($modules, $user->id);

        $parcours = $group?->formateurParcours;
        $parcoursItems = $parcours?->items ?? collect();
        $modulesById = $modules->keyBy('id');

        // Outils numériques actifs du groupe (nuages de mots)
        $activeWordClouds = $group
            ? $group->wordClouds()->where('is_active', true)->orderByDesc('opened_at')->get()
            : collect();

        return view('stagiaire.stagiaire_modules', compact('modules', 'parcours', 'parcoursItems', 'modulesById', 'activeWordClouds'));
    }

    public function StagiaireResultats()
    {
        $user = auth()->user();
        $userId = $user->id;

        // --- 1. CALCULS GLOBAUX (Temps & Engagement) ---
        // Temps SCORM + Quiz + Vidéo
        $scormTime = (int) ScormScore::where('user_id', $userId)->sum('session_time');
        $quizTime = (int) DB::table('quiz_attempts')->where('user_id', $userId)->sum('total_time_seconds');

        // Stats Vidéo
        $videoStatsObj = VideoSegmentTracking::where('user_id', $userId)
            ->selectRaw('SUM(total_watch_time) as watch_time')
            ->first();
        $videoTime = (int) ($videoStatsObj->watch_time ?? 0);

        $engagementTotal = $scormTime + $quizTime + $videoTime;

        // --- 2. RÉCUPÉRATION ET ANALYSE DES QUESTIONS (Logique "Droit à l'erreur") ---

        // On récupère TOUTES les réponses, triées par date (du plus vieux au plus récent)
        $rawAnswers = QuizAttemptQuestion::with(['question', 'attempt.lecture.module'])
            ->whereHas('attempt', fn ($q) => $q->where('user_id', $userId))
            ->whereNotNull('answered_at')
            ->orderBy('answered_at', 'asc') // Important : ASC pour identifier le 1er essai
            ->get();

        // On groupe par Question ID pour l'analyse
        $consolidatedQuestions = $rawAnswers->groupBy('question_id')->map(function ($answers) {

            $firstTry = $answers->first(); // Le tout premier essai
            $lastTry = $answers->last();  // Le dernier en date

            // RÈGLE D'OR : Est considéré validé si AU MOINS UNE réponse est correcte dans l'historique
            $isValidated = $answers->contains('is_correct', 1);

            return (object) [
                'question_id' => $firstTry->question_id,
                'question_text' => $firstTry->question->question_text ?? 'Question introuvable',
                'module_title' => $firstTry->attempt->lecture->module->module_title ?? 'Module inconnu',
                'attempts_count' => $answers->count(),      // Compteur de tentatives
                'first_result' => (bool) $firstTry->is_correct, // Mémoire de la 1ère fois
                'final_status' => $isValidated,           // Statut "Sanctuarisé"
                'last_date' => $lastTry->answered_at,
            ];
        })->sortByDesc('last_date'); // On affiche les questions travaillées récemment en haut

        // --- 3. STATISTIQUES DÉRIVÉES ---

        // Latence moyenne (Temps de réflexion)
        $totalLatency = 0;
        $totalQuestions = $rawAnswers->count();
        // Pour les quiz natifs
        $totalLatency += $rawAnswers->sum('time_seconds');

        $averageLatencyTime = $totalQuestions > 0 ? (int) round($totalLatency / $totalQuestions) : 0;

        // Taux de réussite "Intelligent" (Basé sur les questions validées vs total questions tentées)
        $uniqueQuestionsCount = $consolidatedQuestions->count();
        $validatedQuestionsCount = $consolidatedQuestions->where('final_status', true)->count();

        $tauxBonnesReponses = $uniqueQuestionsCount > 0
            ? (int) round(($validatedQuestionsCount / $uniqueQuestionsCount) * 100)
            : 0;

        // Réessais (Questions tentées plus d'une fois)
        $reessayeCount = $consolidatedQuestions->where('attempts_count', '>', 1)->count();

        // --- 4. RÉSULTATS DÉTAILLÉS PAR MODULE (CONSOLIDÉS PAR LEÇON) ---
        // Objectif: 1 seule ligne par leçon, même si SCORM + Quiz coexistent.
        $scormResults = ScormScore::query()
            ->where('user_id', $userId)
            ->get()
            ->keyBy('lecture_id');

        $nativeAttempts = QuizAttempt::query()
            ->where('user_id', $userId)
            ->orderByDesc('finished_at')
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get();

        $latestQuizAttempts = $nativeAttempts
            ->groupBy('lecture_id')
            ->map(function ($rows) {
                return $rows->sortByDesc(function ($a) {
                    return $a->finished_at?->timestamp
                        ?? $a->started_at?->timestamp
                        ?? $a->created_at?->timestamp
                        ?? 0;
                })->first();
            });

        $attemptAgg = collect();
        $attemptIds = $nativeAttempts->pluck('id')->all();
        if (! empty($attemptIds)) {
            $attemptAgg = QuizAttemptQuestion::query()
                ->select([
                    'attempt_id',
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN answered_at IS NOT NULL THEN 1 ELSE 0 END) as answered'),
                    DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct'),
                ])
                ->whereIn('attempt_id', $attemptIds)
                ->groupBy('attempt_id')
                ->get()
                ->keyBy('attempt_id');
        }

        $scormAgg = ScormInteraction::query()
            ->where('user_id', $userId)
            ->whereIn('result', ['correct', 'wrong'])
            ->select([
                'lecture_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN result = 'correct' THEN 1 ELSE 0 END) as correct"),
            ])
            ->groupBy('lecture_id')
            ->get()
            ->keyBy('lecture_id');

        $videoByLecture = VideoSegmentTracking::query()
            ->where('user_id', $userId)
            ->select('lecture_id', DB::raw('SUM(total_watch_time) as watch_time'))
            ->groupBy('lecture_id')
            ->pluck('watch_time', 'lecture_id');

        $videoBoundsByLecture = VideoSegmentTracking::query()
            ->where('user_id', $userId)
            ->select([
                'lecture_id',
                DB::raw('MIN(created_at) as first_seen_at'),
                DB::raw('MAX(updated_at) as last_seen_at'),
            ])
            ->groupBy('lecture_id')
            ->get()
            ->keyBy('lecture_id');

        $progressionsByLecture = Progression::query()
            ->where('user_id', $userId)
            ->select('lecture_id', DB::raw('MAX(completed_at) as completed_at'))
            ->groupBy('lecture_id')
            ->pluck('completed_at', 'lecture_id');

        $lectureIds = collect()
            ->merge($scormResults->keys())
            ->merge($latestQuizAttempts->keys())
            ->merge($videoByLecture->keys())
            ->merge($videoBoundsByLecture->keys())
            ->merge($progressionsByLecture->keys())
            ->unique()
            ->values();

        $lecturesById = ModuleLecture::query()
            ->with('module:id,module_title')
            ->whereIn('id', $lectureIds)
            ->get()
            ->keyBy('id');

        $resultats = $lectureIds->map(function ($lectureId) use (
            $lecturesById,
            $scormResults,
            $latestQuizAttempts,
            $attemptAgg,
            $scormAgg,
            $videoByLecture,
            $videoBoundsByLecture,
            $progressionsByLecture
        ) {
            $lecture = $lecturesById->get($lectureId);
            if (! $lecture) {
                return null;
            }

            $sc = $scormResults->get($lectureId);
            $latestQuiz = $latestQuizAttempts->get($lectureId);
            $displayQuiz = $latestQuiz;
            $quizStats = $displayQuiz ? $attemptAgg->get($displayQuiz->id) : null;
            $scormStats = $scormAgg->get($lectureId);
            $videoBounds = $videoBoundsByLecture->get($lectureId);

            $scormTotal = (int) ($scormStats->total ?? 0);
            $scormCorrect = (int) ($scormStats->correct ?? 0);
            $scormAnswered = $scormTotal;

            $scormScore = null;
            if (! is_null($sc?->last_score)) {
                $scormScore = (int) $sc->last_score;
            } elseif (! is_null($sc?->best_score)) {
                $scormScore = (int) $sc->best_score;
            } elseif ($scormTotal > 0) {
                $scormScore = (int) round(($scormCorrect / max(1, $scormTotal)) * 100);
            }

            $quizTotal = (int) ($quizStats->total ?? ($displayQuiz->total_questions ?? 0));
            $quizAnswered = (int) ($quizStats->answered ?? 0);
            $quizCorrect = (int) ($quizStats->correct ?? 0);

            if ($quizAnswered === 0 && ! is_null($displayQuiz?->finished_at) && $quizTotal > 0) {
                $quizAnswered = $quizTotal;
            }

            $quizScore = null;
            if (! is_null($displayQuiz?->percent)) {
                $quizScore = (int) $displayQuiz->percent;
            } elseif (! is_null($displayQuiz?->score)) {
                $quizScore = (int) $displayQuiz->score;
            } elseif ($quizTotal > 0) {
                $quizScore = (int) round(($quizCorrect / max(1, $quizTotal)) * 100);
            }

            $scormTime = (int) ($sc?->session_time ?? 0);
            $quizTime = (int) ($displayQuiz?->total_time_seconds ?? 0);
            $videoTime = (int) ($videoByLecture[$lectureId] ?? 0);
            $totalTime = $scormTime + $quizTime + $videoTime;

            $lessonStatus = strtolower((string) ($sc?->lesson_status ?? ''));
            $scormCompleted = in_array($lessonStatus, ['completed', 'passed'], true) || (bool) ($sc?->is_completed ?? false);
            $hasScormActivity = $scormTotal > 0
                || $scormTime > 0
                || ! is_null($sc?->last_score)
                || ! is_null($sc?->last_attempt_at)
                || in_array($lessonStatus, ['incomplete', 'browsed', 'in_progress', 'failed'], true);

            $hasQuizActivity = ! is_null($displayQuiz)
                && (
                    $quizAnswered > 0
                    || $quizTime > 0
                    || ! is_null($displayQuiz->started_at)
                    || ! is_null($displayQuiz->finished_at)
                );

            $hasVideoActivity = $videoTime > 0 || ! is_null($videoBounds?->first_seen_at);
            $hasProgression = ! empty($progressionsByLecture[$lectureId]);

            $useQuizMetrics = $hasQuizActivity || (! is_null($displayQuiz) && (bool) ($lecture->quiz_enabled ?? false));

            $scorePercent = $useQuizMetrics ? $quizScore : $scormScore;
            $totalQ = $useQuizMetrics ? $quizTotal : $scormTotal;
            $answered = $useQuizMetrics ? $quizAnswered : $scormAnswered;
            $correct = $useQuizMetrics ? $quizCorrect : $scormCorrect;

            if ($totalQ <= 0 && $useQuizMetrics) {
                $totalQ = (int) ($lecture->quiz_questions_per_attempt ?? 0);
            }
            if ($totalQ <= 0 && ! $useQuizMetrics) {
                $totalQ = (int) ($lecture->question_count ?? 0);
            }

            $correct = max(0, $correct);
            $answered = max(0, max($answered, $correct));
            if ($totalQ > 0) {
                $answered = min($answered, $totalQ);
                $correct = min($correct, $answered);
            }
            $wrong = max(0, $answered - $correct);

            if ($useQuizMetrics && ! is_null($displayQuiz?->finished_at)) {
                $statusKey = ($scorePercent ?? 0) >= 50 ? 'completed' : 'failed';
            } elseif ($scormCompleted || $hasProgression) {
                $statusKey = 'completed';
            } elseif ($hasQuizActivity || $hasScormActivity || $hasVideoActivity) {
                $statusKey = 'in_progress';
            } else {
                $statusKey = 'not_started';
            }

            if (is_null($scorePercent)) {
                if ($statusKey === 'completed') {
                    $scorePercent = $totalQ > 0
                        ? (int) round(($correct / max(1, $totalQ)) * 100)
                        : 100;
                } elseif ($totalQ > 0) {
                    $scorePercent = (int) round(($correct / max(1, $totalQ)) * 100);
                } else {
                    $scorePercent = 0;
                }
            }

            $scorePercent = (int) max(0, min(100, $scorePercent));

            $statusLabel = match ($statusKey) {
                'completed' => 'Validé',
                'failed' => 'Échec',
                'in_progress' => 'En cours',
                default => 'Non commencé',
            };

            $statusClass = match ($statusKey) {
                'completed' => 'text-green-600 bg-green-50 border-green-200',
                'failed' => 'text-red-600 bg-red-50 border-red-200',
                'in_progress' => 'text-orangeone bg-orange-50 border-orange-200',
                default => 'text-gray-500 bg-gray-50 border-gray-200',
            };

            $barClass = match ($statusKey) {
                'completed' => 'bg-green-500',
                'failed' => 'bg-red-400',
                'in_progress' => 'bg-orangeone',
                default => 'bg-gray-300',
            };

            $sourceLabel = 'Interactif';
            if ($hasQuizActivity && ($hasScormActivity || $hasVideoActivity)) {
                $sourceLabel = 'Interactif + Quiz';
            } elseif ($hasQuizActivity || (bool) ($lecture->quiz_enabled ?? false)) {
                $sourceLabel = 'Quiz';
            }

            $progressCompletedAt = ! empty($progressionsByLecture[$lectureId])
                ? \Illuminate\Support\Carbon::parse($progressionsByLecture[$lectureId])
                : null;

            $startedAt = collect([
                $displayQuiz?->started_at,
                $displayQuiz?->created_at,
                $sc?->created_at,
                ! is_null($videoBounds?->first_seen_at)
                    ? \Illuminate\Support\Carbon::parse($videoBounds->first_seen_at)
                    : null,
            ])->filter()->sortBy(function ($dt) {
                return $dt instanceof \DateTimeInterface ? $dt->getTimestamp() : strtotime((string) $dt);
            })->first();

            $endedAt = collect([
                $progressCompletedAt,
                $displayQuiz?->finished_at,
                $latestQuiz?->finished_at,
                $latestQuiz?->updated_at,
                $sc?->last_attempt_at,
                $sc?->updated_at,
                ! is_null($videoBounds?->last_seen_at)
                    ? \Illuminate\Support\Carbon::parse($videoBounds->last_seen_at)
                    : null,
            ])->filter()->sortByDesc(function ($dt) {
                return $dt instanceof \DateTimeInterface ? $dt->getTimestamp() : strtotime((string) $dt);
            })->first();

            return (object) [
                'lecture_id' => (int) $lecture->id,
                'lecture_title' => $lecture->lecture_title ?? 'Leçon inconnue',
                'module_title' => $lecture->module->module_title ?? 'Module inconnu',
                'source_label' => $sourceLabel,
                'score_percent' => $scorePercent,
                'total_questions' => $totalQ,
                'answered_questions' => $answered,
                'correct_answers' => $correct,
                'wrong_answers' => $wrong,
                'time' => gmdate('H:i:s', max(0, (int) $totalTime)),
                'status_key' => $statusKey,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'bar_class' => $barClass,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'completed_at' => $progressionsByLecture[$lectureId] ?? null,
            ];
        })
            ->filter()
            ->sortBy([
                ['module_title', 'asc'],
                ['lecture_title', 'asc'],
            ])
            ->values();

        $totalSiteTime = (int) ($user->total_site_time ?? 0);
        $videoStats = $videoStatsObj;

        return view('stagiaire.stagiaire_resultats', compact(
            'resultats',
            'reessayeCount',
            'totalSiteTime',
            'engagementTotal',
            'averageLatencyTime',
            'tauxBonnesReponses',
            'videoStats',
            'consolidatedQuestions'
        ));
    }

    /** ========= OUTILS NUMÉRIQUES ========= */
    public function StagiaireOutils()
    {
        $user = auth()->user();
        $userId = $user->id;

        $group = Group::query()
            ->with(['formateurParcours.items' => fn ($query) => $query->orderBy('position')])
            ->active()
            ->whereHas('students', fn ($q) => $q->where('email', $user->email))
            ->first();

        if (! $group) {
            return view('stagiaire.stagiaire_outils', [
                'tools' => collect(),
                'group' => null,
                'formateur' => null,
            ]);
        }

        $groupId = $group->id;
        $formateur = $group->instructor;
        $tools = collect();

        // Nuage de mots
        $wordClouds = WordCloud::where('group_id', $groupId)->get();
        $parcoursWordCloudItems = $group->formateurParcours?->items
            ? $group->formateurParcours->items->where('type', 'wordcloud')->values()
            : collect();

        if ($wordClouds->count() > 0 || $parcoursWordCloudItems->count() > 0) {
            $wcIds = $wordClouds->pluck('id');
            $parcoursWordCloudItemIds = $parcoursWordCloudItems->pluck('id');
            $activeWordCloud = $wordClouds
                ->where('is_active', true)
                ->sortByDesc(fn ($wordCloud) => $wordCloud->opened_at ?? $wordCloud->created_at)
                ->first();
            $firstParcoursWordCloud = $parcoursWordCloudItems
                ->sortBy('position')
                ->first();
            $lastWordCloudActivity = collect([
                $wordClouds->max('opened_at'),
                $wordClouds->max('created_at'),
                $parcoursWordCloudItems->max('updated_at'),
                $parcoursWordCloudItems->max('created_at'),
            ])
                ->filter()
                ->sortByDesc(fn ($date) => $date instanceof \DateTimeInterface ? $date->getTimestamp() : strtotime((string) $date))
                ->first();

            $tools->push((object) [
                'key' => 'wordcloud',
                'label' => 'Nuage de mots',
                'sessions' => $wordClouds->count() + $parcoursWordCloudItems->count(),
                'participated' => WordCloudEntry::whereIn('word_cloud_id', $wcIds)->where('user_id', $userId)->distinct('word_cloud_id')->count('word_cloud_id')
                    + WordCloudEntry::whereIn('formateur_parcours_item_id', $parcoursWordCloudItemIds)->where('user_id', $userId)->distinct('formateur_parcours_item_id')->count('formateur_parcours_item_id'),
                'trackable' => true,
                'last_used' => $lastWordCloudActivity,
                'active_url' => $activeWordCloud
                    ? route('wordcloud.join.code', ['code' => $activeWordCloud->access_code])
                    : ($firstParcoursWordCloud ? route('stagiaire.wordcloud.parcours.show', $firstParcoursWordCloud) : null),
            ]);
        }

        // Sondage
        $polls = PollSession::where('group_id', $groupId)->get();
        if ($polls->count() > 0) {
            $pollIds = $polls->pluck('id');
            $tools->push((object) [
                'key' => 'poll',
                'label' => 'Sondage',
                'sessions' => $polls->count(),
                'participated' => PollSessionResponse::whereIn('poll_session_id', $pollIds)->where('user_id', $userId)->distinct('poll_session_id')->count('poll_session_id'),
                'trackable' => true,
                'last_used' => $polls->max('opened_at') ?? $polls->max('created_at'),
            ]);
        }

        // Quiz en direct
        $liveQuizzes = LiveQuizSession::where('group_id', $groupId)->get();
        if ($liveQuizzes->count() > 0) {
            $lqIds = $liveQuizzes->pluck('id');
            $tools->push((object) [
                'key' => 'live_quiz',
                'label' => 'Quiz en direct',
                'sessions' => $liveQuizzes->count(),
                'participated' => LiveQuizSessionParticipant::whereIn('live_quiz_session_id', $lqIds)->where('user_id', $userId)->distinct('live_quiz_session_id')->count('live_quiz_session_id'),
                'trackable' => true,
                'last_used' => $liveQuizzes->max('ended_at') ?? $liveQuizzes->max('started_at') ?? $liveQuizzes->max('created_at'),
            ]);
        }

        // Mur de questions
        $questionWalls = QuestionWall::where('group_id', $groupId)->get();
        if ($questionWalls->count() > 0) {
            $qwIds = $questionWalls->pluck('id');
            $tools->push((object) [
                'key' => 'question_wall',
                'label' => 'Mur de questions',
                'sessions' => $questionWalls->count(),
                'participated' => QuestionWallQuestion::whereIn('question_wall_id', $qwIds)->where('user_id', $userId)->distinct('question_wall_id')->count('question_wall_id'),
                'trackable' => true,
                'last_used' => $questionWalls->max('updated_at') ?? $questionWalls->max('created_at'),
            ]);
        }

        // Tableau blanc (1 par groupe)
        $whiteboard = GroupWhiteboard::where('group_id', $groupId)->first();
        if ($whiteboard) {
            $tools->push((object) [
                'key' => 'whiteboard',
                'label' => 'Tableau blanc',
                'sessions' => 1,
                'participated' => GroupWhiteboardItem::where('group_whiteboard_id', $whiteboard->id)->where('created_by', $userId)->exists() ? 1 : 0,
                'trackable' => true,
                'last_used' => $whiteboard->updated_at ?? $whiteboard->created_at,
            ]);
        }

        // Minuteur (1 par groupe, pas de participation individuelle)
        if (\App\Support\Outils\EtatsOutils::actif('minuteur')) {
            $timer = GroupTimer::where('group_id', $groupId)->first();
            if ($timer) {
                $tools->push((object) [
                    'key' => 'timer',
                    'label' => 'Minuteur',
                    'sessions' => 1,
                    'participated' => null,
                    'trackable' => false,
                    'last_used' => $timer->updated_at ?? $timer->created_at,
                ]);
            }
        }

        // Roue aléatoire (pas de participation individuelle)
        $randomWheels = RandomWheelSession::where('group_id', $groupId)->get();
        if ($randomWheels->count() > 0) {
            $tools->push((object) [
                'key' => 'random_wheel',
                'label' => 'Roue aléatoire',
                'sessions' => $randomWheels->count(),
                'participated' => null,
                'trackable' => false,
                'last_used' => $randomWheels->max('spun_at') ?? $randomWheels->max('created_at'),
            ]);
        }

        // Vrai ou Faux
        if (\App\Support\Outils\EtatsOutils::actif('vraifaux')) {
            $trueFalseSessions = TrueFalseSession::where('group_id', $groupId)->get();
            if ($trueFalseSessions->count() > 0) {
                $trueFalseIds = $trueFalseSessions->pluck('id');
                $activeSession = $trueFalseSessions
                    ->where('is_active', true)
                    ->sortByDesc(fn ($session) => $session->opened_at ?? $session->created_at)
                    ->first();

                $tools->push((object) [
                    'key' => 'true_false',
                    'label' => 'Vrai ou Faux',
                    'sessions' => $trueFalseSessions->count(),
                    'participated' => TrueFalseSessionResponse::whereIn('true_false_session_id', $trueFalseIds)->where('user_id', $userId)->distinct('true_false_session_id')->count('true_false_session_id'),
                    'trackable' => true,
                    'last_used' => $trueFalseSessions->max('opened_at') ?? $trueFalseSessions->max('created_at'),
                    'active_url' => $activeSession ? route('vraifaux.join.code', $activeSession->access_code) : null,
                ]);
            }
        }

        // Buzzer Quiz
        if (\App\Support\Outils\EtatsOutils::actif('buzzer')) {
            $buzzerSessions = BuzzerSession::where('group_id', $groupId)->get();
            if ($buzzerSessions->count() > 0) {
                $buzzerIds = $buzzerSessions->pluck('id');
                $activeSession = $buzzerSessions
                    ->where('status', '!=', BuzzerSession::STATUS_CLOSED)
                    ->sortByDesc(fn ($session) => $session->opened_at ?? $session->created_at)
                    ->first();

                $tools->push((object) [
                    'key' => 'buzzer_quiz',
                    'label' => 'Buzzer Quiz',
                    'sessions' => $buzzerSessions->count(),
                    'participated' => BuzzerAttempt::whereIn('buzzer_session_id', $buzzerIds)->where('user_id', $userId)->distinct('buzzer_session_id')->count('buzzer_session_id'),
                    'trackable' => true,
                    'last_used' => $buzzerSessions->max('opened_at') ?? $buzzerSessions->max('created_at'),
                    'active_url' => $activeSession ? route('buzzer.join.code', $activeSession->access_code) : null,
                ]);
            }
        }

        // Zone de clic
        if (\App\Support\Outils\EtatsOutils::actif('composants')) {
            $componentFinderSessions = ComponentFinderSession::where('group_id', $groupId)->get();
            if ($componentFinderSessions->count() > 0) {
                $componentFinderIds = $componentFinderSessions->pluck('id');
                $activeSession = $componentFinderSessions
                    ->where('is_active', true)
                    ->sortByDesc(fn ($session) => $session->opened_at ?? $session->created_at)
                    ->first();

                $tools->push((object) [
                    'key' => 'component_finder',
                    'label' => 'Zone de clic',
                    'sessions' => $componentFinderSessions->count(),
                    'participated' => ComponentFinderAttempt::whereIn('component_finder_session_id', $componentFinderIds)->where('user_id', $userId)->distinct('component_finder_session_id')->count('component_finder_session_id'),
                    'trackable' => true,
                    'last_used' => $componentFinderSessions->max('opened_at') ?? $componentFinderSessions->max('created_at'),
                    'active_url' => $activeSession ? route('composants.join.code', $activeSession->access_code) : null,
                ]);
            }
        }

        // Échelle de positionnement
        if (\App\Support\Outils\EtatsOutils::actif('echelle')) {
            $scales = ScaleSession::where('group_id', $groupId)->get();
            if ($scales->count() > 0) {
                $scaleIds = $scales->pluck('id');
                $tools->push((object) [
                    'key' => 'scale',
                    'label' => 'Échelle de positionnement',
                    'sessions' => $scales->count(),
                    'participated' => ScaleSessionResponse::whereIn('scale_session_id', $scaleIds)->where('user_id', $userId)->distinct('scale_session_id')->count('scale_session_id'),
                    'trackable' => true,
                    'last_used' => $scales->max('opened_at') ?? $scales->max('created_at'),
                ]);
            }
        }

        // Émargement
        $seances = Seance::where('group_id', $groupId)->get();
        $openSeance = $seances->firstWhere('statut', 'ouverte');
        if ($seances->count() > 0) {
            $seanceIds = $seances->pluck('id');
            $tools->push((object) [
                'key' => 'emargement',
                'label' => 'Émargement',
                'sessions' => $seances->count(),
                'participated' => SeancePresence::whereIn('seance_id', $seanceIds)->where('user_id', $userId)->where('statut', 'present')->count(),
                'trackable' => true,
                'last_used' => $seances->max('date'),
            ]);
        }

        return view('stagiaire.stagiaire_outils', compact('tools', 'group', 'formateur', 'openSeance'));
    }

    /** ========= MESSAGES ========= */
    public function StagiaireMessages()
    {
        $stagiaireId = auth()->id();

        $messages = FormateurMessage::with('formateur')
            ->where('stagiaire_id', $stagiaireId)
            ->latest()
            ->get();

        return view('stagiaire.messages.index', compact('messages'));
    }
}
