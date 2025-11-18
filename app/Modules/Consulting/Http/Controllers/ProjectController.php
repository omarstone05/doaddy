<?php

namespace App\Modules\Consulting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Consulting\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = $request->user()->organization_id;
        
        $projects = Project::forOrganization($organizationId)
            ->with(['projectManager', 'lead'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Consulting/Projects/Index', [
            'projects' => $projects,
        ]);
    }

    public function create()
    {
        return Inertia::render('Consulting/Projects/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:consulting_projects,code',
            'type' => 'nullable|string|in:consulting,campaign,build,audit,event',
            'description' => 'nullable|string',
            'client_id' => 'nullable|uuid',
            'client_name' => 'nullable|string|max:255',
            'project_manager_id' => 'nullable|uuid|exists:users,id',
            'budget_total' => 'nullable|numeric|min:0',
            'billing_model' => 'nullable|string|in:fixed,time_materials,milestone,retainer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $project = Project::create([
            ...$validated,
            'organization_id' => $request->user()->organization_id,
            'status' => 'proposed',
        ]);

        return redirect()->route('consulting.projects.show', $project->id)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $project->load([
            'projectManager',
            'lead',
            'tasks',
            'milestones',
            'deliverables',
            'expenses',
            'timeEntries',
            'risks',
            'issues',
        ]);

        return Inertia::render('Consulting/Projects/Show', [
            'project' => $project,
        ]);
    }

    public function edit(Project $project)
    {
        return Inertia::render('Consulting/Projects/Edit', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:consulting_projects,code,' . $project->id,
            'type' => 'nullable|string|in:consulting,campaign,build,audit,event',
            'description' => 'nullable|string',
            'status' => 'required|string|in:proposed,active,paused,complete,cancelled',
            'client_id' => 'nullable|uuid',
            'client_name' => 'nullable|string|max:255',
            'project_manager_id' => 'nullable|uuid|exists:users,id',
            'budget_total' => 'nullable|numeric|min:0',
            'billing_model' => 'nullable|string|in:fixed,time_materials,milestone,retainer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $project->update($validated);

        return redirect()->route('consulting.projects.show', $project->id)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('consulting.projects.index')
            ->with('success', 'Project deleted successfully.');
    }
}

