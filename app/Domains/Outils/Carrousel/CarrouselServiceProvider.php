<?php

namespace App\Domains\Outils\Carrousel;

use App\Domains\Outils\Carrousel\Support\DepotCarrousel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as IlluminateView;

class CarrouselServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config.php', 'outils.carrousel');
    }

    public function boot(DepotCarrousel $depot): void
    {
        if (! \App\Support\Outils\EtatsOutils::actif('carrousel')) {
            return;
        }

        $this->loadViewsFrom(__DIR__.'/resources/views', 'outil-carrousel');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware('web')->group(function (): void {
            $this->loadRoutesFrom(__DIR__.'/routes.php');
        });

        View::composer('formateur.outils.index', function (IlluminateView $view) use ($depot): void {
            if (! $depot->estInstalle() || ! auth()->check()) {
                return;
            }

            $outils = collect($view->getData()['outilsAutonomes'] ?? []);
            $outils->push([
                'vue' => 'outil-carrousel::formateur.tuile',
                'donnees' => [
                    'sessionsCarrouselRecentes' => $depot->sessionsPourFormateur((int) auth()->id(), 5),
                ],
            ]);

            $view->with('outilsAutonomes', $outils);
        });

        View::composer('stagiaire.stagiaire_outils', function (IlluminateView $view) use ($depot): void {
            if (! $depot->estInstalle() || ! auth()->check()) {
                return;
            }

            $group = $view->getData()['group'] ?? null;
            if (! $group) {
                return;
            }

            $resume = $depot->resumePourStagiaire((int) $group->id, (int) auth()->id());
            if (! $resume) {
                return;
            }

            if ($resume->active_code) {
                $resume->active_url = route('carrousel.join.code', $resume->active_code);
                $resume->action_label = 'Consulter';
            }

            $tools = collect($view->getData()['tools'] ?? []);
            $tools->push($resume);
            $view->with('tools', $tools);
        });
    }
}
