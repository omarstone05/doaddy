<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $unreadNotificationCount = 0;
        $organizations = [];
        $currentOrganization = null;
        
        if ($user) {
            $currentOrgId = session('current_organization_id') ?? $user->current_organization_id;
            
            // Get user's organizations ordered by joined_at (ascending) to determine first company
            $allOrganizations = $user->organizations()
                ->wherePivot('is_active', true)
                ->orderBy('organization_user.joined_at', 'asc')
                ->get();
            
            $organizations = $allOrganizations->map(function ($org, $index) use ($user, $currentOrgId) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'role' => $user->getRoleInOrganization($org->id),
                    'is_current' => $org->id === $currentOrgId,
                    'index' => $index, // 0 = first company, 1 = second, etc.
                ];
            })->toArray();
            
            // Get current organization
            if ($currentOrgId) {
                $currentOrganization = $allOrganizations->firstWhere('id', $currentOrgId);
            }
            
            // Fallback to first organization if no current
            if (!$currentOrganization) {
                $currentOrganization = $allOrganizations->first();
                if ($currentOrganization) {
                    session(['current_organization_id' => $currentOrganization->id]);
                }
            }
            
            // Determine organization index for theme (0 = first company)
            $organizationIndex = $currentOrganization 
                ? $allOrganizations->search(function ($org) use ($currentOrganization) {
                    return $org->id === $currentOrganization->id;
                })
                : 0;
            
            // Get notification count for current organization
            if ($currentOrganization) {
                $unreadNotificationCount = \App\Models\Notification::where('user_id', $user->id)
                    ->where('organization_id', $currentOrganization->id)
                    ->where('is_read', false)
                    ->count();
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'organization' => $currentOrganization ? [
                        'id' => $currentOrganization->id,
                        'name' => $currentOrganization->name,
                        'theme_index' => $organizationIndex ?? 0, // 0 = first company (default theme)
                    ] : null,
                    'organizations' => $organizations,
                ] : null,
            ],
            'flash' => [
                'message' => $request->session()->get('message'),
                'error' => $request->session()->get('error'),
                'success' => $request->session()->get('success'),
            ],
            'unreadNotificationCount' => $unreadNotificationCount,
            'url' => $request->path(),
        ];
    }
}
