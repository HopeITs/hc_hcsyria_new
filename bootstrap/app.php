<?php

use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\RedirectToHttps;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ThrottleRequestsWithoutSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // The staff dashboard; it names its own `web` + `auth` middleware.
            Route::group([], base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // First, so even a refused or failed request carries the headers.
        $middleware->prepend(SecurityHeaders::class);

        // Appended, not prepended: it must run after TrustProxies so that
        // isSecure() believes a trusted proxy's X-Forwarded-Proto.
        $middleware->append(RedirectToHttps::class);

        // Global, not in the `web` group: it must see the cookies before they
        // are decrypted and refuse before a session file is written. After
        // TrustProxies, so $request->ip() is the visitor behind a proxy.
        $middleware->append(ThrottleRequestsWithoutSession::class);

        // `throttle:guest` limits signed-out requests that do carry a session.
        $middleware->web(append: ['throttle:guest']);

        $middleware->alias(['admin.access' => EnsureAdminAccess::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
