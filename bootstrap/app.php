<?php

use App\Http\Middleware\EnsureInternalRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Neither group goes through the 'web'/'api' middleware groups,
            // so route-model binding needs SubstituteBindings added explicitly.
            Route::middleware(['throttle:webhooks', SubstituteBindings::class])
                ->prefix('webhooks')
                ->group(__DIR__.'/../routes/webhooks.php');

            Route::middleware(['internal', 'throttle:60,1', SubstituteBindings::class])
                ->prefix('internal')
                ->group(__DIR__.'/../routes/internal.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->throttleApi();

        $middleware->alias([
            'internal' => EnsureInternalRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('webhooks/*') || $request->is('internal/*') || $request->expectsJson(),
        );
    })->create();
