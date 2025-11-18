<?php

namespace App\Modules\Consulting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Consulting\Models\Project;
use App\Modules\Consulting\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $tasks = $project->tasks()
            ->with(['assignedUser', 'parentTask'])
            ->orderBy('order')
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'due_date' => $task->due_date,
                    'estimated_hours' => $task->estimated_hours,
                    'assigned_to_id' => $task->assigned_to,
                    'assigned_to_name' => $task->assignedUser?->name,
                    'created_at' => $task->created_at,
                ];
            });

        return Inertia::render('Consulting/Tasks/Index', [
            'project' => $project->only(['id', 'name', 'code']),
            'tasks' => $tasks,
            'auth' => [
                'user' => [
                    'id' => $request->user()->id,
                ],
            ],
        ]);
    }

    public function create(Request $request, Project $project)
    {
        // Get users from the same organization
        $organizationId = $request->user()->organization_id;
        $users = \App\Models\User::whereHas('organizations', function($query) use ($organizationId) {
            $query->where('organizations.id', $organizationId)
                  ->where('organization_user.is_active', true);
        })
        ->orderBy('name')
        ->get(['id', 'name', 'email']);

        return Inertia::render('Consulting/Tasks/Create', [
            'project' => $project->only(['id', 'name', 'code']),
            'users' => $users,
        ]);
    }

    public function show(Request $request, Project $project, Task $task)
    {
        $task->load(['assignedUser', 'parentTask', 'project']);
        
        return Inertia::render('Consulting/Tasks/Show', [
            'project' => $project->only(['id', 'name', 'code']),
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date,
                'estimated_hours' => $task->estimated_hours,
                'assigned_to_id' => $task->assigned_to_id,
                'assigned_to_name' => $task->assignedUser?->name,
                'created_at' => $task->created_at,
            ],
        ]);
    }

    public function edit(Request $request, Project $project, Task $task)
    {
        // Get users from the same organization
        $organizationId = $request->user()->organization_id;
        $users = \App\Models\User::whereHas('organizations', function($query) use ($organizationId) {
            $query->where('organizations.id', $organizationId)
                  ->where('organization_user.is_active', true);
        })
        ->orderBy('name')
        ->get(['id', 'name', 'email']);

        return Inertia::render('Consulting/Tasks/Edit', [
            'project' => $project->only(['id', 'name', 'code']),
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date,
                'estimated_hours' => $task->estimated_hours,
                'assigned_to_id' => $task->assigned_to,
            ],
            'users' => $users,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:todo,in_progress,review,done,blocked',
            'assigned_to_id' => 'nullable|uuid|exists:users,id',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);

        $task = $project->tasks()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'todo',
            'assigned_to' => $validated['assigned_to_id'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'due_date' => $validated['due_date'] ?? null,
            'estimated_hours' => $validated['estimated_hours'] ?? null,
        ]);

        return redirect()->route('consulting.projects.tasks.index', $project->id)
            ->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Project $project, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:todo,in_progress,review,done,blocked',
            'assigned_to_id' => 'nullable|uuid|exists:users,id',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'assigned_to' => $validated['assigned_to_id'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'due_date' => $validated['due_date'] ?? null,
            'estimated_hours' => $validated['estimated_hours'] ?? null,
        ]);

        // Update project progress
        $project->updateProgress();

        return redirect()->route('consulting.projects.tasks.show', [$project->id, $task->id])
            ->with('success', 'Task updated successfully.');
    }

    public function markAsDone(Request $request, Project $project, Task $task)
    {
        $task->update([
            'status' => 'done',
            'completed_at' => now(),
        ]);

        // Update project progress
        $project->updateProgress();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Task marked as done',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Task marked as done.');
    }

    public function destroy(Project $project, Task $task)
    {
        $task->delete();

        // Update project progress
        $project->updateProgress();

        return redirect()->route('consulting.projects.tasks.index', $project->id)
            ->with('success', 'Task deleted successfully.');
    }
}

