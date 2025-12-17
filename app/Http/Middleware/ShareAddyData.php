<?php

namespace App\Http\Middleware;

use App\Services\Addy\AddyCoreService;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class ShareAddyData
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->organization) {
            try {
                $addyCore = new AddyCoreService($request->user()->organization);
                
                // Evaluate immediately instead of using closure
                Inertia::share([
                    'addy' => $addyCore->getCurrentThought(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to load Addy data', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'user_id' => $request->user()->id,
                ]);
                
                // Provide a default state instead of null so bubble can show
                Inertia::share([
                    'addy' => [
                        'state' => [
                            'focus_area' => 'Overview',
                            'urgency' => 0.2,
                            'context' => 'Initializing...',
                            'mood' => 'neutral',
                            'priorities' => [],
                            'last_updated' => null,
                        ],
                        'top_insight' => null,
                        'insights_count' => 0,
                    ],
                ]);
            }
        } else {
            // User not authenticated or no organization - don't share Addy data
            Inertia::share([
                'addy' => null,
            ]);
        }

        return $next($request);
    }
}

