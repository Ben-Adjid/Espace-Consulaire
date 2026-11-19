<?php

use App\Http\Middleware\SecurityHeaders;
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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [SecurityHeaders::class]);
        $middleware->api(append: [SecurityHeaders::class]);
        // Routes protégées hors Filament (pièces, reçus, export) : un visiteur non connecté va à la connexion
        // de l'administration (il n'existe pas de route nommée « login ») ; une requête JSON reçoit un 401.
        $middleware->redirectGuestsTo(fn ($request) => $request->expectsJson() ? null : route('filament.admin.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
