<?php

namespace App\Http\Controllers;

use App\Models\OrganizationRole;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrganizationRoleController extends Controller
{
    /**
     * Display a listing of roles
     */
    public function index()
    {
        $roles = OrganizationRole::orderBy('level', 'desc')->get();

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new role
     */
    public function create()
    {
        return Inertia::render('Roles/Create');
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:organization_roles,slug',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'level' => 'required|integer|min:0|max:100',
        ]);

        $validated['is_system'] = false; // Custom roles are not system roles

        $role = OrganizationRole::create($validated);

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully');
    }

    /**
     * Display the specified role
     */
    public function show(OrganizationRole $role)
    {
        $role->load('users');

        return Inertia::render('Roles/Show', [
            'role' => $role,
        ]);
    }

    /**
     * Show the form for editing the specified role
     */
    public function edit(OrganizationRole $role)
    {
        // Prevent editing system roles
        if ($role->is_system) {
            return redirect()->route('roles.index')
                ->with('error', 'System roles cannot be edited');
        }

        return Inertia::render('Roles/Edit', [
            'role' => $role,
        ]);
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, OrganizationRole $role)
    {
        // Prevent updating system roles
        if ($role->is_system) {
            return redirect()->route('roles.index')
                ->with('error', 'System roles cannot be updated');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:organization_roles,slug,' . $role->id,
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'level' => 'required|integer|min:0|max:100',
        ]);

        $role->update($validated);

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully');
    }

    /**
     * Remove the specified role
     */
    public function destroy(OrganizationRole $role)
    {
        // Prevent deleting system roles
        if ($role->is_system) {
            return redirect()->route('roles.index')
                ->with('error', 'System roles cannot be deleted');
        }

        // Check if role is in use
        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')
                ->with('error', 'Cannot delete role that is assigned to users');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully');
    }
}
