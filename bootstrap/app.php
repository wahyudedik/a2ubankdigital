<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            Route::middleware('web')
                ->prefix('ajax')
                ->group(base_path('routes/ajax.php'));
        },
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'sanitize' => \App\Http\Middleware\SanitizeInput::class,
        ]);

        // Rate limiting for API endpoints
        $middleware->throttleApi();

        // CSRF Protection: All ajax/* routes are served under the 'web' middleware group
        // (see bootstrap/app.php withRouting → then callback), which includes VerifyCsrfToken.
        // The SPA sends the XSRF-TOKEN cookie value in the X-XSRF-TOKEN header on every
        // AJAX request, satisfying CSRF verification automatically.
        // No routes are excluded — explicit CSRF protection is active on all ajax/* routes.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
