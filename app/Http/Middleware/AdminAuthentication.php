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

        $user = auth()->user();
        $currentOrgId = session('current_organization_id') ?? $user->organization_id;

        if (!$currentOrgId || !$user->isOwnerOf($currentOrgId)) {
            abort(403, 'Access denied. Organization owner privileges required.');
        }

        return $next($request);
    }
}
