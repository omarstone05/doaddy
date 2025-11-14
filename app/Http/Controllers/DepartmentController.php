<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::where('organization_id', Auth::user()->organization_id);

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active === 'true');
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $departments = $query->with(['manager', 'teamMembers'])->orderBy('name')->paginate(20);

        return Inertia::render('Departments/Index', [
            'departments' => $departments,
            'filters' => $request->only(['is_active', 'search']),
        ]);
    }

    public function create()
    {
        $teamMembers = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Departments/Create', [
            'teamMembers' => $teamMembers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|uuid|exists:team_members,id',
            'is_active' => 'boolean',
        ]);

        $department = Department::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            ...$validated,
        ]);

        try {
            return $this->notifyAndRedirect('departments.show', $department->id, 'success', 'Department Created', 'Department created successfully.');
        } catch (\Exception $e) {
            return redirect()->route('departments.show', $department->id)->with('message', 'Department created successfully');
        }
    }

    public function show($id)
    {
        $department = Department::where('organization_id', Auth::user()->organization_id)
            ->with(['manager', 'teamMembers'])
            ->findOrFail($id);

        return Inertia::render('Departments/Show', [
            'department' => $department,
        ]);
    }

    public function edit($id)
    {
        $department = Department::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $teamMembers = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Departments/Edit', [
            'department' => $department,
            'teamMembers' => $teamMembers,
        ]);
    }

    public function update(Request $request, $id)
    {
        $department = Department::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|uuid|exists:team_members,id',
            'is_active' => 'boolean',
        ]);

        $department->update($validated);

        return redirect()->route('departments.show', $department->id)->with('message', 'Department updated successfully');
    }

    public function destroy($id)
    {
        $department = Department::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        // Check if department has team members
        if ($department->teamMembers()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete department that has team members. Reassign members first.']);
        }

        $department->delete();

        return redirect()->route('departments.index')->with('message', 'Department deleted successfully');
    }
}

