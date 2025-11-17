<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BusinessController extends Controller
{
    /**
     * Show all businesses user has access to
     */
    public function index()
    {
        $user = auth()->user();
        
        $businesses = $user->businesses()
            ->with(['users' => function($query) {
                $query->wherePivot('is_active', true);
            }])
            ->get()
            ->map(function ($business) use ($user) {
                $role = $business->getUserRole($user);
                
                return [
                    'id' => $business->id,
                    'name' => $business->name,
                    'slug' => $business->slug,
                    'business_type' => $business->business_type,
                    'is_active' => $business->is_active,
                    'is_current' => $business->id === $user->current_business_id,
                    'role' => [
                        'name' => $role->name,
                        'slug' => $role->slug,
                        'level' => $role->level,
                    ],
                    'user_count' => $business->activeUsers()->count(),
                    'created_at' => $business->created_at->format('M d, Y'),
                ];
            });

        return Inertia::render('Business/Index', [
            'businesses' => $businesses,
        ]);
    }

    /**
     * Show create business form
     */
    public function create()
    {
        return Inertia::render('Business/Create');
    }

    /**
     * Create a new business
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'business_type' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string',
        ]);

        $user = $request->user();
        
        $business = $user->createBusiness($validated);

        return redirect()->route('business.show', $business)
            ->with('success', 'Business created successfully!');
    }

    /**
     * Show specific business details
     */
    public function show(Business $business)
    {
        $user = auth()->user();

        // Check access
        if (!$user->businesses->contains($business->id)) {
            abort(403, 'You do not have access to this business.');
        }

        $role = $business->getUserRole($user);

        // Get team members with their roles
        $team = $business->users()
            ->wherePivot('is_active', true)
            ->get()
            ->map(function ($member) use ($business) {
                $memberRole = $business->getUserRole($member);
                
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => [
                        'name' => $memberRole->name,
                        'slug' => $memberRole->slug,
                        'level' => $memberRole->level,
                    ],
                    'joined_at' => $member->pivot->joined_at?->format('M d, Y'),
                ];
            });

        return Inertia::render('Business/Show', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'slug' => $business->slug,
                'business_type' => $business->business_type,
                'email' => $business->email,
                'phone' => $business->phone,
                'address' => $business->address,
                'tax_number' => $business->tax_number,
                'currency' => $business->currency,
                'is_active' => $business->is_active,
            ],
            'userRole' => [
                'name' => $role->name,
                'slug' => $role->slug,
                'level' => $role->level,
                'permissions' => $role->permissions,
            ],
            'team' => $team,
            'canManage' => $role->hasPermission('users.invite'),
        ]);
    }

    /**
     * Update business details
     */
    public function update(Request $request, Business $business)
    {
        $user = $request->user();

        // Check permission
        if (!$business->userCan($user, 'business.update')) {
            abort(403, 'You do not have permission to update this business.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'business_type' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string',
        ]);

        $business->update($validated);

        return back()->with('success', 'Business updated successfully!');
    }

    /**
     * Switch to a different business
     */
    public function switch(Request $request, Business $business)
    {
        $user = $request->user();

        try {
            $user->switchBusiness($business);

            return back()->with('success', "Switched to {$business->name}");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Invite user to business
     */
    public function inviteUser(Request $request, Business $business)
    {
        $user = $request->user();

        // Check permission
        if (!$business->userCan($user, 'users.invite')) {
            abort(403, 'You do not have permission to invite users.');
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|exists:roles,slug',
        ]);

        // Find or create user
        $invitedUser = User::firstOrCreate(
            ['email' => $validated['email']],
            ['name' => explode('@', $validated['email'])[0]]
        );

        // Check if already member
        if ($business->users->contains($invitedUser->id)) {
            return back()->with('error', 'User is already a member of this business.');
        }

        // Add to business
        $business->addUser($invitedUser, $validated['role'], [
            'invited_at' => now(),
        ]);

        // TODO: Send invitation email

        return back()->with('success', 'User invited successfully!');
    }

    /**
     * Change user's role in business
     */
    public function changeUserRole(Request $request, Business $business, User $member)
    {
        $user = $request->user();

        // Check permission
        if (!$business->userCan($user, 'users.change_role')) {
            abort(403, 'You do not have permission to change user roles.');
        }

        $validated = $request->validate([
            'role' => 'required|exists:roles,slug',
        ]);

        // Can't change role of user not in business
        if (!$business->users->contains($member->id)) {
            abort(404, 'User is not a member of this business.');
        }

        // Can't demote yourself if you're the only owner
        if ($member->id === $user->id) {
            $ownerCount = $business->users()
                ->wherePivot('role_id', Role::where('slug', 'owner')->first()->id)
                ->count();

            if ($ownerCount === 1 && $validated['role'] !== 'owner') {
                return back()->with('error', 'Cannot change your own role. There must be at least one owner.');
            }
        }

        $business->changeUserRole($member, $validated['role']);

        return back()->with('success', 'User role changed successfully!');
    }

    /**
     * Remove user from business
     */
    public function removeUser(Request $request, Business $business, User $member)
    {
        $user = $request->user();

        // Check permission
        if (!$business->userCan($user, 'users.remove')) {
            abort(403, 'You do not have permission to remove users.');
        }

        // Can't remove yourself if you're the only owner
        if ($member->id === $user->id) {
            $ownerCount = $business->users()
                ->wherePivot('role_id', Role::where('slug', 'owner')->first()->id)
                ->count();

            if ($ownerCount === 1) {
                return back()->with('error', 'Cannot remove yourself. Transfer ownership first.');
            }
        }

        $business->removeUser($member);

        return back()->with('success', 'User removed from business successfully!');
    }

    /**
     * Delete business (owner only)
     */
    public function destroy(Request $request, Business $business)
    {
        $user = $request->user();

        // Check permission
        if (!$business->userCan($user, 'business.delete')) {
            abort(403, 'Only the business owner can delete the business.');
        }

        $name = $business->name;
        
        // If this was the user's current business, switch to another
        if ($user->current_business_id === $business->id) {
            $nextBusiness = $user->businesses()
                ->where('id', '!=', $business->id)
                ->first();
            
            if ($nextBusiness) {
                $user->update(['current_business_id' => $nextBusiness->id]);
            } else {
                $user->update(['current_business_id' => null]);
            }
        }

        $business->delete();

        return redirect()->route('business.index')
            ->with('success', "Business '{$name}' has been deleted.");
    }
}
