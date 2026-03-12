<?php

use App\Http\Middleware\OneCApiToken;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TelegramWebAppAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web/admin.php',
            __DIR__.'/../routes/web/user.php',
        ],
        api: __DIR__.'/../routes/api/routes.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                return null;
            }

            $guard = $request->route()->middleware();
            if ($guard[1] == 'auth:admin') {
                return route('dashboard.login');
            } else {
                return route('auth.view');
            }
        });

        $middleware->alias([
            'role' => RoleMiddleware::class,
            '1c.token' => OneCApiToken::class,
        ]);

        $middleware->web([
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
    })->create();
