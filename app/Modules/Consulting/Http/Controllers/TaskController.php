<?php

namespace App\Modules\Consulting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Consulting\Models\Project;
use App\Modules\Consulting\Models\Task;
use App\Modules\Consulting\Models\TaskComment;
use App\Modules\Consulting\Models\TaskStep;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
        $organizationId = $request->user()->organization_id;
        
        // Get users from the same organization
        $users = \App\Models\User::whereHas('organizations', function($query) use ($organizationId) {
            $query->where('organizations.id', $organizationId)
                  ->where('organization_user.is_active', true);
        })
        ->orderBy('name')
        ->get(['id', 'name', 'email']);

        $task->load([
            'assignedUser', 
            'assignedTeam', 
            'followers',
            'creator',
            'parentTask', 
            'project',
            'comments.user',
            'comments.attachments',
            'comments.replies.user',
            'steps.completedBy'
        ]);
        
        return Inertia::render('Consulting/Tasks/Show', [
            'project' => $project->only(['id', 'name', 'code']),
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->toDateString(),
                'estimated_hours' => $task->estimated_hours,
                'assigned_to_id' => $task->assigned_to,
                'assigned_to_name' => $task->assignedUser?->name,
                'assigned_team' => $task->assignedTeam->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
                'followers' => $task->followers->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
                'created_by_id' => $task->created_by,
                'created_by_name' => $task->creator?->name,
                'created_at' => $task->created_at,
                'comments' => $task->comments->map(function($comment) {
                    return [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'user_id' => $comment->user_id,
                        'user_name' => $comment->user->name,
                        'is_internal' => $comment->is_internal,
                        'created_at' => $comment->created_at->toISOString(),
                        'created_at_human' => $comment->created_at->diffForHumans(),
                        'attachments' => $comment->attachments->map(fn($a) => [
                            'id' => $a->id,
                            'file_name' => $a->file_name,
                            'original_name' => $a->original_name,
                            'file_path' => $a->file_path,
                            'file_size' => $a->file_size,
                            'file_url' => Storage::url($a->file_path),
                        ]),
                        'replies' => $comment->replies->map(fn($r) => [
                            'id' => $r->id,
                            'comment' => $r->comment,
                            'user_id' => $r->user_id,
                            'user_name' => $r->user->name,
                            'created_at' => $r->created_at->toISOString(),
                            'created_at_human' => $r->created_at->diffForHumans(),
                        ]),
                    ];
                }),
                'steps' => $task->steps->map(fn($step) => [
                    'id' => $step->id,
                    'title' => $step->title,
                    'description' => $step->description,
                    'order' => $step->order,
                    'is_completed' => $step->is_completed,
                    'completed_at' => $step->completed_at?->toDateString(),
                    'completed_by_name' => $step->completedBy?->name,
                ]),
            ],
            'users' => $users,
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
            'created_by' => $request->user()->id,
        ]);

        // Handle multiple assignees
        if ($request->has('assigned_team') && is_array($request->assigned_team)) {
            $task->assignedTeam()->sync($request->assigned_team);
        }

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

    public function toggleStatus(Request $request, Project $project, Task $task)
    {
        $oldStatus = $task->status;
        
        $validated = $request->validate([
            'status' => 'required|string|in:todo,in_progress,review,done,blocked',
        ]);

        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'done' ? now() : null,
        ]);

        // Notify about status change
        $this->notifyTaskChanges($task, $oldStatus, null, null, $request->user());

        // Update project progress
        $project->updateProgress();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Task status updated',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Task status updated.');
    }

    public function addComment(Request $request, Project $project, Task $task)
    {
        $validated = $request->validate([
            'comment' => 'required|string',
            'is_internal' => 'boolean',
            'parent_comment_id' => 'nullable|uuid|exists:consulting_task_comments,id',
        ]);

        $comment = $task->allComments()->create([
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'],
            'is_internal' => $validated['is_internal'] ?? false,
            'parent_comment_id' => $validated['parent_comment_id'] ?? null,
        ]);

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('task-comments', 'public');
                $comment->attachments()->create([
                    'file_name' => basename($path),
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]);
            }
        }

        // Notify task participants
        $this->notifyNewComment($task, $comment, $request->user());

        return redirect()->back()->with('success', 'Comment added successfully.');
    }

    public function addStep(Request $request, Project $project, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $maxOrder = $task->steps()->max('order') ?? 0;
        
        $task->steps()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'order' => $maxOrder + 1,
        ]);

        return redirect()->back()->with('success', 'Step added successfully.');
    }

    public function toggleStep(Request $request, Project $project, Task $task, TaskStep $step)
    {
        $step->update([
            'is_completed' => !$step->is_completed,
            'completed_at' => !$step->is_completed ? now() : null,
            'completed_by' => !$step->is_completed ? $request->user()->id : null,
        ]);

        // Update task progress based on steps
        $completedSteps = $task->steps()->where('is_completed', true)->count();
        $totalSteps = $task->steps()->count();
        if ($totalSteps > 0) {
            $task->update([
                'progress_percentage' => round(($completedSteps / $totalSteps) * 100),
            ]);
        }

        return redirect()->back()->with('success', 'Step updated successfully.');
    }

    public function updateDueDate(Request $request, Project $project, Task $task)
    {
        $oldDueDate = $task->due_date?->toDateString();
        
        $validated = $request->validate([
            'due_date' => 'nullable|date',
        ]);

        $task->update([
            'due_date' => $validated['due_date'] ?? null,
        ]);

        // Notify about due date change
        $this->notifyTaskChanges($task, null, null, $oldDueDate, $request->user());

        return redirect()->back()->with('success', 'Due date updated successfully.');
    }

    public function toggleFollower(Request $request, Project $project, Task $task)
    {
        $userId = $request->user()->id;
        $isFollowing = $task->followers()->where('user_id', $userId)->exists();

        if ($isFollowing) {
            $task->followers()->detach($userId);
            $message = 'You are no longer following this task.';
        } else {
            $task->followers()->attach($userId);
            $message = 'You are now following this task.';
        }

        return redirect()->back()->with('success', $message);
    }

    protected function notifyTaskChanges(Task $task, $oldStatus, $oldAssignee, $oldDueDate, $user)
    {
        $organizationId = $task->project->organization_id;
        $notifyUsers = collect();

        // Add assigned users
        if ($task->assigned_to) {
            $notifyUsers->push($task->assigned_to);
        }
        $task->assignedTeam->each(fn($u) => $notifyUsers->push($u->id));

        // Add followers
        $task->followers->each(fn($u) => $notifyUsers->push($u->id));

        // Add creator
        if ($task->created_by) {
            $notifyUsers->push($task->created_by);
        }

        // Remove current user from notifications
        $notifyUsers = $notifyUsers->unique()->reject(fn($id) => $id === $user->id);

        $changes = [];
        if ($oldStatus && $oldStatus !== $task->status) {
            $changes[] = "Status changed from {$oldStatus} to {$task->status}";
        }
        if ($oldAssignee && $oldAssignee !== $task->assigned_to) {
            $oldUser = \App\Models\User::find($oldAssignee);
            $newUser = $task->assignedUser;
            $changes[] = "Assigned to " . ($newUser ? $newUser->name : 'Unassigned');
        }
        if ($oldDueDate && $oldDueDate !== $task->due_date?->toDateString()) {
            $changes[] = "Due date changed to " . ($task->due_date ? $task->due_date->format('M d, Y') : 'No due date');
        }

        if (empty($changes)) {
            return;
        }

        $message = "Task '{$task->title}' was updated:\n" . implode("\n", $changes);
        $actionUrl = route('consulting.projects.tasks.show', [$task->project_id, $task->id]);

        foreach ($notifyUsers as $userId) {
            Notification::createForUser(
                $userId,
                $organizationId,
                'task_updated',
                'Task Updated',
                $message,
                $actionUrl
            );
        }
    }

    protected function notifyNewComment(Task $task, TaskComment $comment, $user)
    {
        $organizationId = $task->project->organization_id;
        $notifyUsers = collect();

        // Add assigned users
        if ($task->assigned_to) {
            $notifyUsers->push($task->assigned_to);
        }
        $task->assignedTeam->each(fn($u) => $notifyUsers->push($u->id));

        // Add followers
        $task->followers->each(fn($u) => $notifyUsers->push($u->id));

        // Add creator
        if ($task->created_by) {
            $notifyUsers->push($task->created_by);
        }

        // Remove comment author from notifications
        $notifyUsers = $notifyUsers->unique()->reject(fn($id) => $id === $user->id);

        $message = "{$user->name} commented on task '{$task->title}'";
        $actionUrl = route('consulting.projects.tasks.show', [$task->project_id, $task->id]);

        foreach ($notifyUsers as $userId) {
            Notification::createForUser(
                $userId,
                $organizationId,
                'task_comment',
                'New Comment on Task',
                $message,
                $actionUrl
            );
        }
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

