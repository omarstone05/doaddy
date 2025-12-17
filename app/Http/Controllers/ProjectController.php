<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::where('organization_id', Auth::user()->organization_id)
            ->with(['projectManager', 'createdBy']);

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority !== '') {
            $query->where('priority', $request->priority);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters' => $request->only(['status', 'priority', 'search']),
        ]);
    }

    public function create()
    {
        $users = User::where('organization_id', Auth::user()->organization_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Projects/Create', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:planning,active,on_hold,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'target_completion_date' => 'nullable|date',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'project_manager_id' => 'nullable|uuid|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $project = Project::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'created_by_id' => Auth::id(),
            'progress_percentage' => $validated['progress_percentage'] ?? 0,
            ...$validated,
        ]);

        return redirect()->route('projects.show', $project->id)->with('message', 'Project created successfully');
    }

    public function show($id)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->with(['projectManager', 'createdBy'])
            ->findOrFail($id);

        return Inertia::render('Projects/Show', [
            'project' => $project,
        ]);
    }

    public function edit($id)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $users = User::where('organization_id', Auth::user()->organization_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Projects/Edit', [
            'project' => $project,
            'users' => $users,
        ]);
    }

    public function update(Request $request, $id)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:planning,active,on_hold,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'target_completion_date' => 'nullable|date',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'project_manager_id' => 'nullable|uuid|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $project->update($validated);

        return redirect()->route('projects.show', $project->id)->with('message', 'Project updated successfully');
    }

    public function destroy($id)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $project->delete();

        return redirect()->route('projects.index')->with('message', 'Project deleted successfully');
    }
}

