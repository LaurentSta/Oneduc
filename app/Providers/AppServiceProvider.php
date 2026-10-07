<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(DiagnosingHealth::class, function () {
            try {
                DB::select('SELECT 1');
                Cache::get('oneduc:controle-sante');
            } catch (\Throwable) {
                // Ne pas exposer les détails de connexion dans /up ni dans ses logs.
                throw new \RuntimeException('Un service nécessaire est indisponible.');
            }
        });

        // Forcer https très tôt
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        RateLimiter::for('contact', function (Request $request) {
            return [
                // 3 envois / minute par IP
                Limit::perMinute(3)->by($request->ip())
                    ->response(function () {
                        // Message lisible + statut 429
                        return back()
                            ->withErrors(['global' => 'Trop de tentatives. Réessayez dans une minute.'])
                            ->withInput()
                            ->setStatusCode(429);
                    }),
                // 20 envois / heure par IP
                Limit::perHour(20)->by($request->ip()),
            ];
        });
        RateLimiter::for('connexion-code', function (Request $request) {
            return [
                // 10 tentatives / minute par IP
                Limit::perMinute(10)->by($request->ip())
                    ->response(function () {
                        return back()
                            ->withErrors(['code_acces' => 'Trop de tentatives. Réessayez dans une minute.'])
                            ->withInput()
                            ->setStatusCode(429);
                    }),
            ];
        });
        RateLimiter::for('emargement-code', function (Request $request) {
            return [
                // 20 tentatives / minute par IP
                Limit::perMinute(20)->by($request->ip()),
            ];
        });
        // Partage de données vues
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $profileData = User::find(Auth::id());
                $view->with('profileData', $profileData);
            }
        });
    }
}
