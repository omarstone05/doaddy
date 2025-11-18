<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

    /**
     * Create a new organization for the authenticated user
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        try {
            // Generate unique slug
            $baseSlug = Str::slug($request->name);
            if (empty($baseSlug)) {
                $baseSlug = 'organization-' . time();
            }
            $slug = $baseSlug;
            $counter = 1;
            
            while (Organization::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            // Create organization
            $organization = Organization::create([
                'id' => (string) Str::uuid(),
                'name' => trim($request->name),
                'slug' => $slug,
                'tone_preference' => 'professional',
                'currency' => 'ZMW',
                'timezone' => 'Africa/Lusaka',
            ]);

            // Note: Default dashboard cards are now handled by the modular dashboard system
            // via CardRegistry, so we don't need to create OrgDashboardCard entries

            // Add user to organization via pivot table as owner
            $user->organizations()->attach($organization->id, [
                'role' => 'owner',
                'is_active' => true,
                'joined_at' => now(),
            ]);

            // Switch to new organization
            session(['current_organization_id' => $organization->id]);
            $user->update(['organization_id' => $organization->id]);

            return response()->json([
                'success' => true,
                'message' => "Business '{$organization->name}' created successfully",
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'slug' => $organization->slug,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating organization', [
                'user_id' => $user->id,
                'name' => $request->name,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create business. Please try again.',
            ], 500);
        }
    }
}
