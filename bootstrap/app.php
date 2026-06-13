<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'sso.jwt' => \App\Http\Middleware\VerifyJwtToken::class,
            'iae.key' => \App\Http\Middleware\VerifyIaeKey::class,
            'auth.sso' => \App\Http\Middleware\AuthSSO::class,
            'guest.sso' => \App\Http\Middleware\RedirectIfSsoAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
