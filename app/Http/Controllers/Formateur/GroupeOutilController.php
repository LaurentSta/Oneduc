<?php

namespace App\Http\Controllers\Formateur;

use App\Http\Controllers\Controller;
use App\Models\FormateurParcoursItem;
use App\Models\Group;
use App\Models\TrueFalseSession;
use App\Services\CodeGeneratorService;
use Illuminate\Http\RedirectResponse;

class GroupeOutilController extends Controller
{
    public function launch(Group $group, FormateurParcoursItem $item): RedirectResponse
    {
        abort_unless($item->type === 'outil', 404);
        abort_unless($group->formateur_parcours_id === $item->formateur_parcours_id, 404);
        abort_unless(
            Group::query()->accessibleByTrainer((int) auth()->id())->whereKey($group->id)->exists(),
            403
        );

        return match ($item->outil) {
            'vrai-faux' => $this->launchVraiFaux($group, $item),
            default => abort(404),
        };
    }

    private function launchVraiFaux(Group $group, FormateurParcoursItem $item): RedirectResponse
    {
        abort_unless(\App\Support\Outils\EtatsOutils::actif('vraifaux'), 404);

        $session = TrueFalseSession::query()
            ->where('group_id', $group->id)
            ->where('formateur_parcours_item_id', $item->id)
            ->first();

        if (! $session) {
            $configuration = $item->configuration ?? [];

            $questions = collect($configuration['affirmations'] ?? [])
                ->map(fn (array $affirmation): array => [
                    'statement' => trim((string) ($affirmation['texte'] ?? '')),
                    'answer' => (bool) ($affirmation['reponse'] ?? false),
                    'explanation' => null,
                ])
                ->filter(fn (array $question): bool => $question['statement'] !== '')
                ->values();

            abort_if($questions->isEmpty(), 422, "Cette étape n'a pas encore d'affirmations configurées.");

            $session = TrueFalseSession::query()->create([
                'formateur_id' => (int) auth()->id(),
                'group_id' => $group->id,
                'formateur_parcours_item_id' => $item->id,
                'title' => trim((string) ($configuration['titre'] ?? '')) ?: 'Vrai ou Faux',
                'questions' => $questions->all(),
                'access_code' => CodeGeneratorService::generateUniqueCode(TrueFalseSession::class),
                'is_active' => true,
                'opened_at' => now(),
                'closed_at' => null,
            ]);
        }

        return redirect()->route('formateur.vraifaux.show', $session);
    }
}
