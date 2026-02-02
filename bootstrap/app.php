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
            \App\Http\Middleware\RefreshPendaToken::class,
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
        
        // Handle 404 Not Found errors with Inertia
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Not Found'], 404);
            }
            
            return \Inertia\Inertia::render('Errors/404')
                ->toResponse($request)
                ->setStatusCode(404);
        });
        
        // Handle 403 Forbidden errors with Inertia
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access Denied'], 403);
            }
            
            return \Inertia\Inertia::render('Errors/403')
                ->toResponse($request)
                ->setStatusCode(403);
        });
        
        // Handle 500 Server errors with Inertia (only in production)
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // Don't intercept authentication exceptions - let Laravel redirect to login
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return null;
            }
            
            // Don't intercept validation exceptions
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return null;
            }
            
            if (!app()->environment('local') && !$request->expectsJson()) {
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                
                if ($statusCode === 500) {
                    // Log the error before rendering the error page
                    \Illuminate\Support\Facades\Log::error('500 Error: ' . $e->getMessage(), [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'url' => $request->fullUrl(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    return \Inertia\Inertia::render('Errors/500')
                        ->toResponse($request)
                        ->setStatusCode(500);
                }
            }
            
            return null; // Let Laravel handle other exceptions
        });
    })->create(), function ($app) {
        $app->singleton('hash', fn ($app) => new Illuminate\Hashing\HashManager($app));
    });
