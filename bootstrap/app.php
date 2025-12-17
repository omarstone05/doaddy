<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return tap(Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\ShareAddyData::class,
            \App\Http\Middleware\TrackUserActivity::class,
        ]);
        
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminAuthentication::class,
            'auth.rate' => \App\Http\Middleware\AuthRateLimiting::class,
            'module' => \App\Http\Middleware\CheckModuleEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle CSRF token mismatch (419 errors)
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Your session has expired. Please refresh the page and try again.',
                    'error' => 'CSRF token mismatch'
                ], 419);
            }
            
            return redirect()->back()
                ->withInput($request->except('password', '_token'))
                ->withErrors(['error' => 'Your session has expired. Please refresh the page and try again.']);
        });
    })->create(), function ($app) {
        $app->singleton('hash', fn ($app) => new Illuminate\Hashing\HashManager($app));
    });
