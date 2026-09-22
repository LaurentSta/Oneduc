<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EntetesSecurite
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Autorise les lecteurs SCORM internes sans bloquer scripts et médias existants.
        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
        }

        $dureeHsts = (int) config('securite.hsts_max_age', 0);
        if ($request->isSecure() && app()->environment('production') && $dureeHsts > 0) {
            $response->headers->set('Strict-Transport-Security', 'max-age='.$dureeHsts);
        }

        return $response;
    }
}
