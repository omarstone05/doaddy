<?php

namespace App\Http\Controllers;

use App\Mail\AddedToOrganizationMail;
use App\Mail\OrganizationInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AccessControlController extends Controller
{
    /**
     * Invite a new user to the organization
     */
    public function invite(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role_id' => 'required|exists:organization_roles,id',
            'name' => 'nullable|string|max:255',
        ]);

        $currentUser = Auth::user();
        $organizationId = $currentUser->organization_id;
        $organization = Organization::findOrFail($organizationId);

        // Check if current user has permission to invite
        if (!$currentUser->hasPermissionInOrganization($organizationId, 'users.invite')) {
            return back()->withErrors(['error' => 'You do not have permission to invite users.']);
        }

        $role = OrganizationRole::findOrFail($request->role_id);
        $currentUserRole = $currentUser->getOrganizationRole($organizationId);

        // Can only assign roles lower than your own
        if ($currentUserRole && !$currentUserRole->canManage($role)) {
            return back()->withErrors(['error' => 'You cannot assign a role equal to or higher than your own.']);
        }

        // Check if user already exists
        $existingUser = User::where('email', $request->email)->first();

        if ($existingUser) {
            // Check if already a member
            if ($existingUser->belongsToOrganization($organizationId)) {
                return back()->withErrors(['error' => 'This user is already a member of the organization.']);
            }

            // Add existing user to organization
            $existingUser->organizations()->attach($organizationId, [
                'role_id' => $role->id,
                'role' => $role->slug,
                'is_active' => true,
                'joined_at' => now(),
            ]);

            Log::info('User added to organization', [
                'user_id' => $existingUser->id,
                'organization_id' => $organizationId,
                'role' => $role->slug,
                'invited_by' => $currentUser->id,
            ]);

            // Send notification email to the added user
            try {
                Mail::to($existingUser->email)->send(new AddedToOrganizationMail(
                    $organization,
                    $role,
                    $currentUser
                ));
            } catch (\Exception $e) {
                Log::error('Failed to send added-to-organization email', [
                    'user_id' => $existingUser->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return back()->with('success', "{$existingUser->name} has been added to the organization as {$role->name}.");
        }

        // Create invitation for new user
        $inviteToken = Str::random(64);
        
        DB::table('organization_invitations')->insert([
            'id' => Str::uuid(),
            'organization_id' => $organizationId,
            'email' => $request->email,
            'name' => $request->name,
            'role_id' => $role->id,
            'token' => hash('sha256', $inviteToken),
            'invited_by' => $currentUser->id,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send invitation email
        try {
            Mail::to($request->email)->send(new OrganizationInvitationMail(
                $organization,
                $role,
                $currentUser,
                $inviteToken,
                $request->name
            ));

            Log::info('Organization invitation email sent', [
                'email' => $request->email,
                'organization_id' => $organizationId,
                'role' => $role->slug,
                'invited_by' => $currentUser->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send invitation email', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the invite if email fails - invitation is still recorded
        }

        return back()->with('success', "Invitation sent to {$request->email}.");
    }

    /**
     * Change a user's role in the organization
     */
    public function changeRole(Request $request, string $userId)
    {
        $request->validate([
            'role_id' => 'required|exists:organization_roles,id',
        ]);

        $currentUser = Auth::user();
        $organizationId = $currentUser->organization_id;

        // Check if current user has permission to change roles
        if (!$currentUser->hasPermissionInOrganization($organizationId, 'users.change_role')) {
            return back()->withErrors(['error' => 'You do not have permission to change user roles.']);
        }

        $targetUser = User::findOrFail($userId);
        $newRole = OrganizationRole::findOrFail($request->role_id);
        $currentUserRole = $currentUser->getOrganizationRole($organizationId);
        $targetUserRole = $targetUser->getOrganizationRole($organizationId);

        // Cannot change your own role
        if ($targetUser->id === $currentUser->id) {
            return back()->withErrors(['error' => 'You cannot change your own role.']);
        }

        // Can only manage roles lower than your own
        if ($currentUserRole && $targetUserRole && !$currentUserRole->canManage($targetUserRole)) {
            return back()->withErrors(['error' => 'You cannot change the role of a user with equal or higher privileges.']);
        }

        // Can only assign roles lower than your own
        if ($currentUserRole && !$currentUserRole->canManage($newRole)) {
            return back()->withErrors(['error' => 'You cannot assign a role equal to or higher than your own.']);
        }

        // Update the role
        $targetUser->organizations()->updateExistingPivot($organizationId, [
            'role_id' => $newRole->id,
            'role' => $newRole->slug,
        ]);

        Log::info('User role changed', [
            'user_id' => $targetUser->id,
            'organization_id' => $organizationId,
            'old_role' => $targetUserRole?->slug,
            'new_role' => $newRole->slug,
            'changed_by' => $currentUser->id,
        ]);

        return back()->with('success', "{$targetUser->name}'s role has been changed to {$newRole->name}.");
    }

    /**
     * Remove a user's access from the organization
     */
    public function remove(string $userId)
    {
        $currentUser = Auth::user();
        $organizationId = $currentUser->organization_id;

        // Check if current user has permission to remove users
        if (!$currentUser->hasPermissionInOrganization($organizationId, 'users.remove')) {
            return back()->withErrors(['error' => 'You do not have permission to remove users.']);
        }

        $targetUser = User::findOrFail($userId);
        $currentUserRole = $currentUser->getOrganizationRole($organizationId);
        $targetUserRole = $targetUser->getOrganizationRole($organizationId);

        // Cannot remove yourself
        if ($targetUser->id === $currentUser->id) {
            return back()->withErrors(['error' => 'You cannot remove yourself from the organization.']);
        }

        // Can only remove users with lower privileges
        if ($currentUserRole && $targetUserRole && !$currentUserRole->canManage($targetUserRole)) {
            return back()->withErrors(['error' => 'You cannot remove a user with equal or higher privileges.']);
        }

        // Check if this is the last owner
        if ($targetUserRole?->slug === 'owner') {
            $ownerCount = DB::table('organization_user')
                ->join('organization_roles', 'organization_user.role_id', '=', 'organization_roles.id')
                ->where('organization_user.organization_id', $organizationId)
                ->where('organization_user.is_active', true)
                ->where('organization_roles.slug', 'owner')
                ->count();

            if ($ownerCount <= 1) {
                return back()->withErrors(['error' => 'Cannot remove the last owner of the organization.']);
            }
        }

        // Remove the user from the organization
        $targetUser->organizations()->detach($organizationId);

        // If this was the user's current organization, clear it
        if ($targetUser->organization_id === $organizationId) {
            $newOrg = $targetUser->organizations()->first();
            $targetUser->update(['organization_id' => $newOrg?->id]);
        }

        Log::info('User removed from organization', [
            'user_id' => $targetUser->id,
            'organization_id' => $organizationId,
            'removed_by' => $currentUser->id,
        ]);

        return back()->with('success', "{$targetUser->name} has been removed from the organization.");
    }

    /**
     * Transfer organization ownership to another user
     */
    public function transferOwnership(string $userId)
    {
        $currentUser = Auth::user();
        $organizationId = $currentUser->organization_id;
        $currentUserRole = $currentUser->getOrganizationRole($organizationId);

        // Only owner can transfer ownership
        if ($currentUserRole?->slug !== 'owner') {
            return back()->withErrors(['error' => 'Only the organization owner can transfer ownership.']);
        }

        $targetUser = User::findOrFail($userId);

        // Cannot transfer to yourself
        if ($targetUser->id === $currentUser->id) {
            return back()->withErrors(['error' => 'You cannot transfer ownership to yourself.']);
        }

        // Target user must be a member of the organization
        if (!$targetUser->belongsToOrganization($organizationId)) {
            return back()->withErrors(['error' => 'The user must be a member of the organization to receive ownership.']);
        }

        $ownerRole = OrganizationRole::where('slug', 'owner')->first();
        $adminRole = OrganizationRole::where('slug', 'admin')->first();

        if (!$ownerRole || !$adminRole) {
            return back()->withErrors(['error' => 'Required roles not found in the system.']);
        }

        // Use transaction for atomic operation
        DB::transaction(function () use ($currentUser, $targetUser, $organizationId, $ownerRole, $adminRole) {
            // Make the target user the new owner
            $targetUser->organizations()->updateExistingPivot($organizationId, [
                'role_id' => $ownerRole->id,
                'role' => $ownerRole->slug,
            ]);

            // Demote current owner to admin
            $currentUser->organizations()->updateExistingPivot($organizationId, [
                'role_id' => $adminRole->id,
                'role' => $adminRole->slug,
            ]);
        });

        Log::info('Organization ownership transferred', [
            'organization_id' => $organizationId,
            'from_user_id' => $currentUser->id,
            'to_user_id' => $targetUser->id,
        ]);

        return back()->with('success', "Ownership has been transferred to {$targetUser->name}. You are now an Admin.");
    }

    /**
     * Toggle a user's active status in the organization
     */
    public function toggleStatus(string $userId)
    {
        $currentUser = Auth::user();
        $organizationId = $currentUser->organization_id;

        // Check if current user has permission
        if (!$currentUser->hasPermissionInOrganization($organizationId, 'users.manage')) {
            return back()->withErrors(['error' => 'You do not have permission to manage users.']);
        }

        $targetUser = User::findOrFail($userId);
        $currentUserRole = $currentUser->getOrganizationRole($organizationId);
        $targetUserRole = $targetUser->getOrganizationRole($organizationId);

        // Cannot toggle your own status
        if ($targetUser->id === $currentUser->id) {
            return back()->withErrors(['error' => 'You cannot change your own status.']);
        }

        // Can only manage users with lower privileges
        if ($currentUserRole && $targetUserRole && !$currentUserRole->canManage($targetUserRole)) {
            return back()->withErrors(['error' => 'You cannot change the status of a user with equal or higher privileges.']);
        }

        $pivot = $targetUser->organizations()
            ->where('organizations.id', $organizationId)
            ->first()
            ?->pivot;

        $newStatus = !($pivot?->is_active ?? true);

        $targetUser->organizations()->updateExistingPivot($organizationId, [
            'is_active' => $newStatus,
        ]);

        $statusText = $newStatus ? 'activated' : 'deactivated';

        Log::info('User status toggled', [
            'user_id' => $targetUser->id,
            'organization_id' => $organizationId,
            'new_status' => $newStatus,
            'changed_by' => $currentUser->id,
        ]);

        return back()->with('success', "{$targetUser->name} has been {$statusText}.");
    }
}
