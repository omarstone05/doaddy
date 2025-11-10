<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->with('organization')
            ->when($request->search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->organization_id, function ($query, $orgId) {
                $query->where('organization_id', $orgId);
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
            'organization',
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
}

