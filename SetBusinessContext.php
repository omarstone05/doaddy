<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetBusinessContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // If user has no current business, set the first one
            if (!$user->current_business_id && $user->businesses()->exists()) {
                $firstBusiness = $user->businesses()->first();
                $user->update(['current_business_id' => $firstBusiness->id]);
            }

            // Load current business
            $currentBusiness = $user->currentBusiness;

            // Share with all views
            View::share('currentBusiness', $currentBusiness);
            View::share('currentRole', $user->currentRole());
            View::share('userBusinesses', $user->activeBusinesses()->get());

            // Add to request for easy access
            $request->attributes->set('business', $currentBusiness);
            $request->attributes->set('role', $user->currentRole());
        }

        return $next($request);
    }
}
