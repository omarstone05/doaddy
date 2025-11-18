<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\UserMetricsService;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track for authenticated users
        if (Auth::check() && !$request->is('api/*')) {
            try {
                $user = Auth::user();
                $metricsService = app(UserMetricsService::class);

                // Track page view
                $path = $request->path();
                if (!in_array($path, ['login', 'register', 'logout'])) {
                    $metricsService->trackPageView($user, $path, [
                        'method' => $request->method(),
                        'referer' => $request->header('referer'),
                    ]);
                }

                // Update last_active_at
                if (!$user->last_active_at || $user->last_active_at->lt(now()->subMinutes(5))) {
                    $user->update(['last_active_at' => now()]);
                }
            } catch (\Exception $e) {
                // Silently fail to not interrupt the request
                \Log::warning('Failed to track user activity', ['error' => $e->getMessage()]);
            }
        }

        return $response;
    }
}



