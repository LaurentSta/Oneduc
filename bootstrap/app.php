<?php

use App\Http\Middleware\EnsureAssociationMembership;
use App\Http\Middleware\EntetesSecurite;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\RecordAdminActivity;
use App\Http\Middleware\Role;
use App\Http\Middleware\TrackSessionTime;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(EntetesSecurite::class);

        $middleware->alias([
            'role' => Role::class,
            'track.time' => TrackSessionTime::class,
            'force.password.change' => ForcePasswordChange::class,
            'admin.activity' => RecordAdminActivity::class,
            'association.member' => EnsureAssociationMembership::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
