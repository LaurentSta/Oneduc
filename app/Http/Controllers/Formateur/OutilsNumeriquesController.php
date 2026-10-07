<?php

namespace App\Http\Controllers\Formateur;

use App\Http\Controllers\Controller;
use App\Models\BuzzerSession;
use App\Models\ComponentFinderSession;
use App\Models\Group;
use App\Models\Module;
use App\Models\PollSession;
use App\Models\QuestionWall;
use App\Models\ScaleSession;
use App\Models\Seance;
use App\Models\TrueFalseSession;
use App\Models\WordCloud;
use Illuminate\View\View;

class OutilsNumeriquesController extends Controller
{
    public function index(): View
    {
        $formateurId = (int) auth()->id();

        $recentWordclouds = WordCloud::query()
            ->whereHas('group', fn ($q) => $q->where('instructor_id', $formateurId))
            ->with(['group', 'module'])
            ->latest()
            ->limit(5)
            ->get();

        $groups = Group::query()
            ->accessibleByTrainer($formateurId)
            ->withCount('students')
            ->orderBy('name')
            ->get();

        $recentQuestionWalls = QuestionWall::query()
            ->where('formateur_id', $formateurId)
            ->with('group')
            ->withCount('questions')
            ->latest()
            ->limit(5)
            ->get();

        $recentPolls = PollSession::query()
            ->where('formateur_id', $formateurId)
            ->with('group:id,name')
            ->withCount('responses')
            ->latest()
            ->limit(5)
            ->get();

        $recentScales = \App\Support\Outils\EtatsOutils::actif('echelle')
            ? ScaleSession::query()
                ->whereHas('group', fn ($q) => $q->accessibleByTrainer($formateurId))
                ->with('group:id,name')
                ->withCount('responses')
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        $recentTrueFalseSessions = \App\Support\Outils\EtatsOutils::actif('vraifaux')
            ? TrueFalseSession::query()
                ->whereHas('group', fn ($q) => $q->accessibleByTrainer($formateurId))
                ->with('group:id,name')
                ->withCount('responses')
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        $recentBuzzerSessions = \App\Support\Outils\EtatsOutils::actif('buzzer')
            ? BuzzerSession::query()
                ->whereHas('group', fn ($q) => $q->accessibleByTrainer($formateurId))
                ->with('group:id,name')
                ->withCount('participants')
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        $recentComponentFinderSessions = \App\Support\Outils\EtatsOutils::actif('composants')
            ? ComponentFinderSession::query()
                ->whereHas('group', fn ($q) => $q->accessibleByTrainer($formateurId))
                ->with('group:id,name')
                ->withCount('attempts')
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        $recentModules = Module::query()
            ->authoredByTrainer($formateurId)
            ->withCount(['sections', 'groups'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $openSeancesCount = Seance::whereIn('group_id', $groups->pluck('id'))->where('statut', 'ouverte')->count();

        $recentPowerPointModules = Module::query()
            ->authoredByTrainer($formateurId)
            ->whereHas('lectures', fn ($query) => $query->where('content_type', 'slides'))
            ->with([
                'lectures' => fn ($query) => $query
                    ->where('content_type', 'slides')
                    ->latest('id'),
            ])
            ->latest()
            ->limit(5)
            ->get();

        return view('formateur.outils.index', compact('recentWordclouds', 'groups', 'recentQuestionWalls', 'recentPolls', 'recentScales', 'recentTrueFalseSessions', 'recentBuzzerSessions', 'recentComponentFinderSessions', 'recentModules', 'openSeancesCount', 'recentPowerPointModules'));
    }
}
