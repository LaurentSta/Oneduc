<?php

namespace App\Http\Controllers\Stagiaire;

use App\Http\Controllers\Controller;
use App\Models\FormateurParcoursItem;
use App\Models\Group;
use App\Models\TrueFalseSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ParcoursOutilController extends Controller
{
    public function show(FormateurParcoursItem $item): View|RedirectResponse
    {
        abort_unless($item->type === 'outil', 404);

        $user = auth()->user();

        $group = Group::query()
            ->where('formateur_parcours_id', $item->formateur_parcours_id)
            ->whereHas('students', fn ($q) => $q->where('email', $user->email))
            ->first();

        abort_unless($group !== null, 403);

        return match ($item->outil) {
            'vrai-faux' => $this->showVraiFaux($group, $item),
            default => abort(404),
        };
    }

    private function showVraiFaux(Group $group, FormateurParcoursItem $item): View|RedirectResponse
    {
        abort_unless(\App\Support\Outils\EtatsOutils::actif('vraifaux'), 404);

        $session = TrueFalseSession::query()
            ->where('group_id', $group->id)
            ->where('formateur_parcours_item_id', $item->id)
            ->first();

        if ($session) {
            return redirect()->route('vraifaux.join.code', $session->access_code);
        }

        return view('stagiaire.parcours_outil_wait', compact('item'));
    }
}
