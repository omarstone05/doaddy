<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProjectTaskController extends Controller
{
    public function index($projectId)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($projectId);

        $tasks = ProjectTask::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->with(['assignedTo', 'createdBy', 'subtasks'])
            ->whereNull('parent_task_id')
            ->orderBy('order')
            ->get();

        $users = User::where('organization_id', Auth::user()->organization_id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'tasks' => $tasks,
            'users' => $users,
        ]);
    }

    public function store(Request $request, $projectId)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($projectId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:todo,in_progress,review,done,blocked',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to_id' => 'nullable|uuid|exists:users,id',
            'due_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'estimated_hours' => 'nullable|integer|min:0',
            'parent_task_id' => 'nullable|uuid|exists:project_tasks,id',
            'tags' => 'nullable|array',
        ]);

        $task = ProjectTask::create([
            'id' => (string) Str::uuid(),
            'project_id' => $projectId,
            'organization_id' => Auth::user()->organization_id,
            'created_by_id' => Auth::id(),
            'order' => ProjectTask::where('project_id', $projectId)
                ->whereNull('parent_task_id')
                ->max('order') + 1,
            ...$validated,
        ]);

        return response()->json([
            'task' => $task->load(['assignedTo', 'createdBy']),
            'message' => 'Task created successfully',
        ]);
    }

    public function update(Request $request, $projectId, $taskId)
    {
        $task = ProjectTask::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($taskId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:todo,in_progress,review,done,blocked',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to_id' => 'nullable|uuid|exists:users,id',
            'due_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'estimated_hours' => 'nullable|integer|min:0',
            'actual_hours' => 'nullable|integer|min:0',
            'order' => 'nullable|integer',
            'tags' => 'nullable|array',
        ]);

        // Handle work tracking when status changes
        $oldStatus = $task->status;
        $newStatus = $validated['status'];

        // If status is changing to 'in_progress' and not already being worked on, start tracking
        if ($newStatus === 'in_progress' && $oldStatus !== 'in_progress' && !$task->started_working_at) {
            $validated['started_working_at'] = now();
        }
        // If status is changing away from 'in_progress', clear the started_working_at
        elseif ($oldStatus === 'in_progress' && $newStatus !== 'in_progress') {
            $validated['started_working_at'] = null;
        }

        $task->update($validated);

        return response()->json([
            'task' => $task->load(['assignedTo', 'createdBy']),
            'message' => 'Task updated successfully',
        ]);
    }

    public function show($projectId, $taskId)
    {
        $task = ProjectTask::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->with([
                'project',
                'assignedTo',
                'createdBy',
                'parentTask',
                'subtasks.assignedTo',
                'subtasks.createdBy',
                'timeEntries.user',
            ])
            ->findOrFail($taskId);

        // Check permission for granular view
        $user = Auth::user();
        $organizationId = $user->organization_id;
        $role = $user->getOrganizationRole($organizationId);
        
        if (!$role || !$role->hasPermission('tasks.view_granular')) {
            // Check if user is assigned to this task or is project manager
            $canView = $task->assigned_to_id === $user->id 
                || $task->project->project_manager_id === $user->id
                || $task->created_by_id === $user->id;
            
            if (!$canView) {
                abort(403, 'You do not have permission to view this task in detail.');
            }
        }

        // Calculate task statistics
        $totalTime = $task->timeEntries->sum('hours');
        $subtaskStats = [
            'total' => $task->subtasks->count(),
            'done' => $task->subtasks->where('status', 'done')->count(),
            'in_progress' => $task->subtasks->where('status', 'in_progress')->count(),
            'todo' => $task->subtasks->where('status', 'todo')->count(),
        ];

        // Get users for assignment dropdown
        $users = User::where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Projects/Tasks/Show', [
            'task' => $task,
            'totalTime' => $totalTime,
            'subtaskStats' => $subtaskStats,
            'users' => $users,
            'canEdit' => $this->canEditTask($user, $task, $role),
            'canDelete' => $this->canDeleteTask($user, $task, $role),
        ]);
    }

    public function destroy($projectId, $taskId)
    {
        $task = ProjectTask::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($taskId);

        // Check permission
        $user = Auth::user();
        $organizationId = $user->organization_id;
        $role = $user->getOrganizationRole($organizationId);
        
        if (!$this->canDeleteTask($user, $task, $role)) {
            abort(403, 'You do not have permission to delete this task.');
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

    /**
     * Check if user can edit task
     */
    private function canEditTask($user, $task, $role): bool
    {
        if (!$role) {
            return false;
        }

        // Project managers and coordinators can edit any task
        if ($role->hasPermission('tasks.edit') && 
            ($role->slug === 'project_manager' || $role->slug === 'project_coordinator')) {
            return true;
        }

        // Task assignees can edit their own tasks
        if ($role->slug === 'task_assignee' && $task->assigned_to_id === $user->id) {
            return true;
        }

        // Creator can edit their own tasks
        if ($task->created_by_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can delete task
     */
    private function canDeleteTask($user, $task, $role): bool
    {
        if (!$role) {
            return false;
        }

        // Only project managers can delete tasks
        if ($role->hasPermission('tasks.delete') && $role->slug === 'project_manager') {
            return true;
        }

        // Creator can delete their own tasks if they're not assigned to anyone
        if ($task->created_by_id === $user->id && !$task->assigned_to_id) {
            return true;
        }

        return false;
    }

    /**
     * Start working on a task
     */
    public function startWork($projectId, $taskId)
    {
        $task = ProjectTask::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($taskId);

        $user = Auth::user();

        // Check if user is assigned to this task
        if ($task->assigned_to_id !== $user->id) {
            abort(403, 'You can only start working on tasks assigned to you.');
        }

        // Start work on the task
        $task->startWork();

        return response()->json([
            'task' => $task->load(['assignedTo', 'createdBy', 'project']),
            'message' => 'Started working on task',
        ]);
    }

    /**
     * Stop working on a task
     */
    public function stopWork(Request $request, $projectId, $taskId)
    {
        $task = ProjectTask::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($taskId);

        $user = Auth::user();

        // Check if user is assigned to this task
        if ($task->assigned_to_id !== $user->id) {
            abort(403, 'You can only stop working on tasks assigned to you.');
        }

        $validated = $request->validate([
            'status' => 'nullable|in:todo,review,done,blocked',
        ]);

        $newStatus = $validated['status'] ?? 'todo';
        $task->stopWork($newStatus);

        return response()->json([
            'task' => $task->load(['assignedTo', 'createdBy', 'project']),
            'message' => 'Stopped working on task',
        ]);
    }

    /**
     * Get all tasks assigned to the authenticated user
     */
    public function myTasks(Request $request)
    {
        $user = Auth::user();
        $organizationId = $user->organization_id;

        $query = $user->assignedTasksInOrganization($organizationId)
            ->with(['project', 'createdBy']);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by active tasks only
        if ($request->boolean('active_only')) {
            $query->where('status', 'in_progress')
                ->whereNotNull('started_working_at');
        }

        $tasks = $query->orderBy('started_working_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get task statistics
        $stats = $user->getTaskStats($organizationId);

        return response()->json([
            'tasks' => $tasks,
            'stats' => $stats,
        ]);
    }

    /**
     * Get active tasks for the authenticated user
     */
    public function myActiveTasks()
    {
        $user = Auth::user();
        $organizationId = $user->organization_id;

        $tasks = $user->activeTasksInOrganization($organizationId)
            ->with(['project', 'createdBy'])
            ->orderBy('started_working_at', 'desc')
            ->get();

        return response()->json([
            'tasks' => $tasks,
            'count' => $tasks->count(),
        ]);
    }

    /**
     * Get tasks for a specific user (for managers/admins)
     */
    public function userTasks(Request $request, $userId)
    {
        $currentUser = Auth::user();
        $organizationId = $currentUser->organization_id;

        // Check permission - only managers/coordinators can view other users' tasks
        $role = $currentUser->getOrganizationRole($organizationId);
        if (!$role || !in_array($role->slug, ['project_manager', 'project_coordinator'])) {
            abort(403, 'You do not have permission to view other users\' tasks.');
        }

        $user = User::where('organization_id', $organizationId)
            ->findOrFail($userId);

        $query = $user->assignedTasksInOrganization($organizationId)
            ->with(['project', 'createdBy']);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by active tasks only
        if ($request->boolean('active_only')) {
            $query->where('status', 'in_progress')
                ->whereNotNull('started_working_at');
        }

        $tasks = $query->orderBy('started_working_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get task statistics for this user
        $stats = $user->getTaskStats($organizationId);

        return response()->json([
            'user' => $user->only(['id', 'name', 'email']),
            'tasks' => $tasks,
            'stats' => $stats,
        ]);
    }

    /**
     * Get all tasks in the organization
     */
    public function all(Request $request)
    {
        $user = Auth::user();
        $organizationId = $user->organization_id;

        $query = ProjectTask::where('organization_id', $organizationId)
            ->with(['project', 'assignedTo', 'createdBy']);

        // Filter by status if provided
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'tasks' => $tasks,
        ]);
    }

    /**
     * Show the task assignment page
     */
    public function showAssign($projectId, $taskId)
    {
        $task = ProjectTask::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->with(['assignedUsers', 'project'])
            ->findOrFail($taskId);

        $user = Auth::user();
        $organizationId = $user->organization_id;

        // Check permission - only managers/coordinators can assign tasks
        $role = $user->getOrganizationRole($organizationId);
        if (!$role || !in_array($role->slug, ['project_manager', 'project_coordinator'])) {
            abort(403, 'You do not have permission to assign tasks.');
        }

        $users = User::where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Tasks/Assign', [
            'task' => $task,
            'users' => $users,
            'assignedUsers' => $task->assignedUsers,
        ]);
    }

    /**
     * Assign a task to one or more users with privileges
     */
    public function assignUsers(Request $request, $projectId, $taskId)
    {
        $task = ProjectTask::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($taskId);

        $user = Auth::user();
        $organizationId = $user->organization_id;

        // Check permission - only managers/coordinators can assign tasks
        $role = $user->getOrganizationRole($organizationId);
        if (!$role || !in_array($role->slug, ['project_manager', 'project_coordinator'])) {
            abort(403, 'You do not have permission to assign tasks.');
        }

        $validated = $request->validate([
            'users' => 'required|array|min:1',
            'users.*.user_id' => 'required|uuid|exists:users,id',
            'users.*.can_edit' => 'boolean',
            'users.*.can_delete' => 'boolean',
            'users.*.can_assign' => 'boolean',
            'users.*.can_view_time' => 'boolean',
            'users.*.can_manage_subtasks' => 'boolean',
            'users.*.can_change_status' => 'boolean',
            'users.*.can_change_priority' => 'boolean',
        ]);

        $assignments = [];
        foreach ($validated['users'] as $userAssignment) {
            $userId = $userAssignment['user_id'];
            
            // Verify user belongs to organization
            $assignedUser = User::where('organization_id', $organizationId)
                ->findOrFail($userId);

            $assignments[$userId] = [
                'assigned_by_id' => $user->id,
                'can_edit' => $userAssignment['can_edit'] ?? true,
                'can_delete' => $userAssignment['can_delete'] ?? false,
                'can_assign' => $userAssignment['can_assign'] ?? false,
                'can_view_time' => $userAssignment['can_view_time'] ?? true,
                'can_manage_subtasks' => $userAssignment['can_manage_subtasks'] ?? false,
                'can_change_status' => $userAssignment['can_change_status'] ?? true,
                'can_change_priority' => $userAssignment['can_change_priority'] ?? false,
                'assigned_at' => now(),
            ];
        }

        // Sync assignments (this will update existing or create new)
        $task->assignedUsers()->sync($assignments);

        // Also update the main assigned_to_id for backward compatibility (use first user)
        if (!empty($assignments)) {
            $firstUserId = array_key_first($assignments);
            $task->update(['assigned_to_id' => $firstUserId]);
        }

        return response()->json([
            'task' => $task->load(['assignedUsers', 'assignedTo', 'createdBy']),
            'message' => 'Task assigned successfully',
        ]);
    }

    /**
     * Remove a user from a task assignment
     */
    public function unassignUser(Request $request, $projectId, $taskId, $userId)
    {
        $task = ProjectTask::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($taskId);

        $user = Auth::user();
        $organizationId = $user->organization_id;

        // Check permission
        $role = $user->getOrganizationRole($organizationId);
        if (!$role || !in_array($role->slug, ['project_manager', 'project_coordinator'])) {
            abort(403, 'You do not have permission to unassign tasks.');
        }

        // Detach the user
        $task->assignedUsers()->detach($userId);

        // If this was the main assigned user, clear assigned_to_id
        if ($task->assigned_to_id === $userId) {
            $remainingUsers = $task->assignedUsers()->first();
            $task->update([
                'assigned_to_id' => $remainingUsers ? $remainingUsers->id : null,
            ]);
        }

        return response()->json([
            'message' => 'User unassigned from task successfully',
        ]);
    }

    /**
     * Update privileges for a user on a task
     */
    public function updateUserPrivileges(Request $request, $projectId, $taskId, $userId)
    {
        $task = ProjectTask::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($taskId);

        $user = Auth::user();
        $organizationId = $user->organization_id;

        // Check permission
        $role = $user->getOrganizationRole($organizationId);
        if (!$role || !in_array($role->slug, ['project_manager', 'project_coordinator'])) {
            abort(403, 'You do not have permission to update task privileges.');
        }

        $validated = $request->validate([
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
            'can_assign' => 'boolean',
            'can_view_time' => 'boolean',
            'can_manage_subtasks' => 'boolean',
            'can_change_status' => 'boolean',
            'can_change_priority' => 'boolean',
        ]);

        // Update the pivot record
        $task->assignedUsers()->updateExistingPivot($userId, $validated);

        return response()->json([
            'task' => $task->load(['assignedUsers', 'assignedTo']),
            'message' => 'Privileges updated successfully',
        ]);
    }
}

