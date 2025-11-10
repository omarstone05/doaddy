<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to continue');
        }

        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Access denied. Super admin privileges required.');
        }

        return $next($request);
    }
}

