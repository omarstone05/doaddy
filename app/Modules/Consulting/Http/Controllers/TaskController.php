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
            ->get();

        return Inertia::render('Consulting/Tasks/Index', [
            'project' => $project,
            'tasks' => $tasks,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|uuid|exists:users,id',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|integer|min:0',
            'billable' => 'boolean',
        ]);

        $task = $project->tasks()->create([
            ...$validated,
            'status' => 'pending',
        ]);

        return redirect()->back()
            ->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Project $project, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:pending,in_progress,review,blocked,completed',
            'assigned_to' => 'nullable|uuid|exists:users,id',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        $task->update($validated);

        // Update project progress
        $project->updateProgress();

        return redirect()->back()
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Project $project, Task $task)
    {
        $task->delete();

        // Update project progress
        $project->updateProgress();

        return redirect()->back()
            ->with('success', 'Task deleted successfully.');
    }
}

