<?php

namespace App\Http\Controllers\Formateur;

use App\Domains\ModulesFormateur\Support\OutilsLecon;
use App\Http\Controllers\Controller;
use App\Models\ComponentFinderActivity;
use App\Models\ComponentFinderSession;
use App\Models\Group;
use App\Services\CodeGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OutilsComposantController extends Controller
{
    /**
     * La bibliothèque : les zones de clic du formateur, puis les jeux lancés.
     */
    public function index(): View
    {
        $formateurId = (int) auth()->id();

        $sessions = ComponentFinderSession::query()
            ->whereHas('group', fn ($query) => $query->accessibleByTrainer($formateurId))
            ->with('group:id,name')
            ->withCount('attempts')
            ->latest()
            ->limit(20)
            ->get();

        $activities = ComponentFinderActivity::query()
            ->where('formateur_id', $formateurId)
            ->latest()
            ->get();

        return view('formateur.outils.composants_index', [
            'groups' => $this->groups(),
            'sessions' => $sessions,
            'activities' => $activities,
            'usages' => OutilsLecon::usages('composants'),
        ]);
    }

    public function create(): View
    {
        return $this->editor(null);
    }

    public function edit(ComponentFinderActivity $componentFinderActivity): View
    {
        $this->assertOwner($componentFinderActivity);

        return $this->editor($componentFinderActivity);
    }

    /**
     * Le même assistant sert à créer une zone de clic et à en modifier une.
     */
    private function editor(?ComponentFinderActivity $editing): View
    {
        return view('formateur.outils.composants_editor', [
            'editing' => $editing,
            'groups' => $this->groups(),
        ]);
    }

    private function groups(): Collection
    {
        return Group::query()
            ->accessibleByTrainer((int) auth()->id())
            ->withCount('students')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function store(Request $request): RedirectResponse
    {
        $formateurId = (int) auth()->id();

        $data = $request->validate([
            'group_id' => ['nullable', 'exists:groups,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'zones' => ['required', 'json'],
        ]);

        $group = filled($data['group_id'] ?? null)
            ? Group::query()->accessibleByTrainer($formateurId)->findOrFail((int) $data['group_id'])
            : null;

        $zones = $this->sanitizeZones($data['zones']);

        $activity = ComponentFinderActivity::query()->create([
            'formateur_id' => $formateurId,
            'title' => trim((string) ($data['title'] ?? '')) ?: 'Zone de clic',
            'image_path' => $request->file('image')->store('component_finder', 'public'),
            'zones' => $zones->all(),
        ]);

        if ($group === null) {
            return redirect()
                ->route('formateur.composants.index')
                ->with('success', 'La zone de clic est enregistrée. Vous pouvez la lancer pour un groupe ou l\'insérer dans une leçon.');
        }

        return redirect()->route('formateur.composants.show', $this->createSession($activity, $group));
    }

    public function update(Request $request, ComponentFinderActivity $componentFinderActivity): RedirectResponse
    {
        $this->assertOwner($componentFinderActivity);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'zones' => ['required', 'json'],
        ]);

        $zones = $this->sanitizeZones($data['zones']);
        $previousImage = $componentFinderActivity->image_path;

        $componentFinderActivity->update([
            'title' => trim((string) ($data['title'] ?? '')) ?: 'Zone de clic',
            'image_path' => $request->hasFile('image')
                ? $request->file('image')->store('component_finder', 'public')
                : $previousImage,
            'zones' => $zones->all(),
        ]);

        if ($componentFinderActivity->image_path !== $previousImage) {
            $this->deleteImageIfUnused($previousImage);
        }

        return redirect()
            ->route('formateur.composants.index')
            ->with('success', 'La zone de clic est mise à jour, y compris dans les leçons qui l\'utilisent. Les jeux déjà lancés gardent leur version.');
    }

    public function launch(Request $request, ComponentFinderActivity $componentFinderActivity): RedirectResponse
    {
        $this->assertOwner($componentFinderActivity);

        $data = $request->validate([
            'group_id' => ['required', 'exists:groups,id'],
        ]);

        $group = Group::query()
            ->accessibleByTrainer((int) auth()->id())
            ->findOrFail((int) $data['group_id']);

        return redirect()->route('formateur.composants.show', $this->createSession($componentFinderActivity, $group));
    }

    public function destroyActivity(ComponentFinderActivity $componentFinderActivity): RedirectResponse
    {
        $this->assertOwner($componentFinderActivity);

        // Une leçon pointe vers la zone de clic : la supprimer y laisserait un trou.
        $lecons = count(OutilsLecon::usages('composants')[$componentFinderActivity->id] ?? []);
        if ($lecons > 0) {
            return redirect()
                ->route('formateur.composants.index')
                ->withErrors(['activity' => 'Cette zone de clic est utilisée dans '.$lecons.' leçon'.($lecons > 1 ? 's' : '').' : retirez-la d\'abord de '.($lecons > 1 ? 'ces leçons' : 'cette leçon').'.']);
        }

        $componentFinderActivity->delete();
        $this->deleteImageIfUnused($componentFinderActivity->image_path);

        return redirect()
            ->route('formateur.composants.index')
            ->with('success', 'La zone de clic a été supprimée.');
    }

    public function show(ComponentFinderSession $componentFinderSession): View
    {
        $this->assertAccess($componentFinderSession);
        $componentFinderSession->load(['group', 'attempts.user']);

        $leaderboard = $componentFinderSession->attempts
            ->sortBy([
                ['score', 'desc'],
                ['duration_seconds', 'asc'],
            ])
            ->values();

        $zoneStats = collect($componentFinderSession->zones)
            ->map(function (array $zone) use ($componentFinderSession): array {
                $attemptsWithZone = $componentFinderSession->attempts->filter(
                    fn ($attempt) => collect($attempt->details ?? [])->firstWhere('label', $zone['label']) !== null
                );

                $correctCount = $attemptsWithZone->filter(
                    fn ($attempt) => (bool) (collect($attempt->details ?? [])->firstWhere('label', $zone['label'])['correct'] ?? false)
                )->count();

                $total = $attemptsWithZone->count();

                return [
                    'label' => $zone['label'],
                    'description' => (string) ($zone['description'] ?? ''),
                    'correct' => $correctCount,
                    'total' => $total,
                    'percent' => $total > 0 ? (int) round(($correctCount / $total) * 100) : 0,
                ];
            })
            ->values();

        return view('formateur.outils.composants_show', [
            'session' => $componentFinderSession,
            'joinUrl' => route('composants.join.code', $componentFinderSession->access_code),
            'leaderboard' => $leaderboard,
            'zoneStats' => $zoneStats,
        ]);
    }

    public function toggle(ComponentFinderSession $componentFinderSession): RedirectResponse
    {
        $this->assertAccess($componentFinderSession);

        $newStatus = ! $componentFinderSession->is_active;

        $componentFinderSession->update([
            'is_active' => $newStatus,
            'opened_at' => $newStatus ? now() : $componentFinderSession->opened_at,
            'closed_at' => $newStatus ? null : now(),
        ]);

        return back()->with(
            'success',
            $newStatus ? 'Le jeu est maintenant ouvert.' : 'Le jeu est maintenant fermé.'
        );
    }

    public function destroy(ComponentFinderSession $componentFinderSession): RedirectResponse
    {
        $this->assertAccess($componentFinderSession);

        $componentFinderSession->delete();
        $this->deleteImageIfUnused($componentFinderSession->image_path);

        return redirect()
            ->route('formateur.composants.index')
            ->with('success', 'Le jeu a été supprimé.');
    }

    private function assertAccess(ComponentFinderSession $componentFinderSession): void
    {
        abort_unless(
            $componentFinderSession->group()
                ->accessibleByTrainer((int) auth()->id())
                ->exists(),
            403
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function sanitizeZones(string $json): Collection
    {
        $allowedShapes = ['square', 'circle', 'oval', 'triangle', 'rectangle'];

        $zones = collect(json_decode($json, true) ?: [])
            ->map(function ($zone) use ($allowedShapes): array {
                $shape = (string) ($zone['shape'] ?? 'rectangle');
                $x = max(0, min(100, (float) ($zone['x'] ?? 0)));
                $y = max(0, min(100, (float) ($zone['y'] ?? 0)));
                $w = max(0, min(100 - $x, (float) ($zone['w'] ?? 0)));
                $h = max(0, min(100 - $y, (float) ($zone['h'] ?? 0)));

                return [
                    'label' => trim((string) ($zone['label'] ?? '')),
                    'description' => mb_substr(trim((string) ($zone['description'] ?? '')), 0, 500),
                    'shape' => in_array($shape, $allowedShapes, true) ? $shape : 'rectangle',
                    'x' => $x,
                    'y' => $y,
                    'w' => $w,
                    'h' => $h,
                ];
            })
            ->filter(fn (array $zone): bool => $zone['label'] !== '' && $zone['w'] > 0 && $zone['h'] > 0)
            ->values();

        abort_if($zones->count() < 2, 422, 'Ajoutez au moins deux composants à trouver sur l\'image.');
        abort_if($zones->count() > 15, 422, 'Vous ne pouvez pas définir plus de 15 composants.');

        return $zones;
    }

    private function assertOwner(ComponentFinderActivity $componentFinderActivity): void
    {
        abort_unless((int) $componentFinderActivity->formateur_id === (int) auth()->id(), 403);
    }

    /**
     * Le jeu reçoit une copie du titre et des zones : modifier ou supprimer la
     * zone enregistrée ne change pas les résultats d'un jeu déjà lancé.
     */
    private function createSession(ComponentFinderActivity $activity, Group $group): ComponentFinderSession
    {
        return ComponentFinderSession::query()->create([
            'formateur_id' => (int) auth()->id(),
            'group_id' => $group->id,
            'title' => $activity->title,
            'image_path' => $activity->image_path,
            'zones' => $activity->zones,
            'access_code' => CodeGeneratorService::generateUniqueCode(ComponentFinderSession::class),
            'is_active' => true,
            'opened_at' => now(),
            'closed_at' => null,
        ]);
    }

    /**
     * Une zone enregistrée et les jeux lancés à partir d'elle partagent le même fichier.
     */
    private function deleteImageIfUnused(string $imagePath): void
    {
        $used = ComponentFinderActivity::query()->where('image_path', $imagePath)->exists()
            || ComponentFinderSession::query()->where('image_path', $imagePath)->exists();

        if (! $used) {
            Storage::disk('public')->delete($imagePath);
        }
    }
}
