<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Load API routes under the web middleware group so they share
            // the browser session (EncryptCookies + StartSession already run).
            // This enables session-based auth for same-origin Axios requests
            // from the Inertia SPA without needing Sanctum.
            Route::prefix('api')
                ->middleware('web')
                ->group(base_path('routes/api.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->alias(['tenant' => \App\Http\Middleware\TenantMiddleware::class]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle database connection failures (SQLSTATE HY000 / error 2002).
        // This can be thrown by any middleware (including StartSession) before
        // the request ever reaches a controller, so it must be caught globally.
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            $previous = $e->getPrevious();
            $isConnectionError = ($previous && $previous->getCode() === 2002)
                || str_contains($e->getMessage(), '[2002]');

            if (! $isConnectionError) {
                return null; // let Laravel handle other query exceptions normally
            }

            if ($request->expectsJson()) {
                return response()->json(
                    ['message' => 'Database unavailable. Please try again later.'],
                    503
                );
            }

            return response()->view('errors.database', [], 503);
        });
    })->create();
