<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\OutilEtat;
use App\Support\Outils\EtatsOutils;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutilEtatController extends Controller
{
    public function index(): View
    {
        $etats = EtatsOutils::tout();

        $outils = collect(EtatsOutils::LIBELLES)
            ->map(fn (string $libelle, string $cle) => [
                'cle' => $cle,
                'libelle' => $libelle,
                'actif' => $etats[$cle] ?? true,
            ])
            ->sortBy('libelle')
            ->values();

        return view('admin.backend.outils.index', ['outils' => $outils]);
    }

    public function toggle(Request $request, string $cle): RedirectResponse
    {
        abort_unless(array_key_exists($cle, EtatsOutils::LIBELLES), 404);

        $etat = OutilEtat::firstOrNew(['cle' => $cle]);
        $etatActuel = $etat->exists ? $etat->actif : EtatsOutils::DEFAUTS[$cle];
        $etat->fill([
            'actif' => ! $etatActuel,
            'modifie_par' => $request->user()->id,
        ])->save();

        EtatsOutils::viderCache();

        return back()->with(
            'success',
            EtatsOutils::LIBELLES[$cle].' est maintenant '.($etat->actif ? 'activé' : 'désactivé').'.',
        );
    }
}
