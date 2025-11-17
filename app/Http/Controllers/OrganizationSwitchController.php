<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrganizationSwitchController extends Controller
{
    /**
     * Switch to a different organization
     */
    public function switch(Request $request, Organization $organization)
    {
        $user = Auth::user();
        
        // Verify user belongs to this organization
        if (!$user->belongsToOrganization($organization->id)) {
            return back()->withErrors([
                'organization' => 'You do not have access to this organization.',
            ]);
        }

        // Set current organization in session
        session(['current_organization_id' => $organization->id]);
        
        // Update user's organization_id for backward compatibility
        $user->update(['organization_id' => $organization->id]);

        return back()->with('success', "Switched to {$organization->name}");
    }

    /**
     * Get user's organizations (for API/JSON requests)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $organizations = $user->organizations()
            ->wherePivot('is_active', true)
            ->get()
            ->map(function ($org) use ($user) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'role' => $user->getRoleInOrganization($org->id),
                    'is_current' => session('current_organization_id') === $org->id || 
                                   ($user->attributes['organization_id'] ?? null) === $org->id,
                ];
            });

        return response()->json($organizations);
    }
}
