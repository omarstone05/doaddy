<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->with(['organizations' => function($query) {
                $query->select('organizations.id', 'organizations.name', 'organizations.slug')
                      ->withPivot('role', 'is_active', 'joined_at')
                      ->wherePivot('is_active', true); // Only show active organization memberships
            }])
            ->when($request->search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->organization_id, function ($query, $orgId) {
                $query->whereHas('organizations', function($q) use ($orgId) {
                    $q->where('organizations.id', $orgId);
                });
            })
            ->when($request->is_super_admin !== null, function ($query) use ($request) {
                $query->where('is_super_admin', $request->is_super_admin);
            })
            ->orderBy($request->sort ?? 'created_at', $request->direction ?? 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'organization_id', 'sort', 'direction']),
        ]);
    }

    public function show(User $user)
    {
        $user->load([
            'organizations' => function($query) {
                $query->select('organizations.id', 'organizations.name', 'organizations.slug')
                      ->withPivot('role', 'is_active', 'joined_at')
                      ->orderBy('organization_user.joined_at', 'asc');
            },
        ]);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'stats' => [],
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'admin_notes' => 'sometimes|nullable|string',
        ]);

        $oldValues = $user->only(array_keys($validated));

        $user->update($validated);

        AdminActivityLog::log('updated', $user, $oldValues, $validated);

        return back()->with('success', 'User updated successfully');
    }

    public function toggleSuperAdmin(Request $request, User $user)
    {
        $user->update(['is_super_admin' => !$user->is_super_admin]);
        
        AdminActivityLog::log('super_admin_toggled', $user, null, [
            'is_super_admin' => $user->is_super_admin,
        ]);

        return back()->with('success', $user->is_super_admin 
            ? 'User granted super admin access' 
            : 'Super admin access removed');
    }

    public function changePassword(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);

            // The User model has 'password' => 'hashed' in casts, so it will auto-hash
            // We just need to pass the plain password
            $user->password = $validated['password'];
            $user->save();

            // Log the activity
            try {
                AdminActivityLog::log('password_changed', $user, null, [
                    'changed_by' => $request->user()->email,
                ]);
            } catch (\Exception $e) {
                // Log error but don't fail the password change
                \Log::warning('Failed to log password change activity', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);
            }

            return back()->with('success', 'Password changed successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Error changing user password', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return back()->with('error', 'Failed to change password: ' . $e->getMessage());
        }
    }

    public function sendPasswordReset(Request $request, User $user)
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            AdminActivityLog::log('password_reset_sent', $user, null, [
                'sent_by' => $request->user()->email,
            ]);

            return back()->with('success', 'Password reset email sent successfully to ' . $user->email);
        }

        return back()->with('error', 'Failed to send password reset email. Please try again.');
    }
}

