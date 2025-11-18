<?php

namespace App\Http\Controllers;

use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskAssignmentController extends Controller
{
    /**
     * Display the task assignment page
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $organizationId = $user->organization_id;
        $role = $user->getOrganizationRole($organizationId);
        
        // Check if user is manager or coordinator (can assign tasks)
        $canAssignTasks = $role && in_array($role->slug, ['project_manager', 'project_coordinator']);

        if ($canAssignTasks) {
            // Managers/Coordinators see all tasks in the organization
            $query = ProjectTask::where('organization_id', $organizationId)
                ->with(['project', 'createdBy', 'assignedUsers', 'assignedTo']);
        } else {
            // Regular users see only their assigned tasks
            $query = $user->assignedTasksInOrganization($organizationId)
                ->with(['project', 'createdBy', 'assignedUsers']);
        }

        // Filter by status if provided
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('status', 'in_progress')
                    ->whereNotNull('started_working_at');
            } else {
                $query->where('status', $request->status);
            }
        }

        $tasks = $query->orderBy('started_working_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get task statistics
        if ($canAssignTasks) {
            // For managers, get stats for all tasks
            $stats = [
                'total' => ProjectTask::where('organization_id', $organizationId)->count(),
                'todo' => ProjectTask::where('organization_id', $organizationId)->where('status', 'todo')->count(),
                'in_progress' => ProjectTask::where('organization_id', $organizationId)->where('status', 'in_progress')->count(),
                'review' => ProjectTask::where('organization_id', $organizationId)->where('status', 'review')->count(),
                'done' => ProjectTask::where('organization_id', $organizationId)->where('status', 'done')->count(),
                'blocked' => ProjectTask::where('organization_id', $organizationId)->where('status', 'blocked')->count(),
                'active' => ProjectTask::where('organization_id', $organizationId)
                    ->where('status', 'in_progress')
                    ->whereNotNull('started_working_at')
                    ->count(),
            ];
        } else {
            // For regular users, get their own stats
            $stats = $user->getTaskStats($organizationId);
        }

        // Get all users for assignment dropdown
        $users = User::where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Tasks/Assignment', [
            'initialTasks' => $tasks,
            'initialStats' => $stats,
            'users' => $users,
            'canAssignTasks' => $canAssignTasks,
        ]);
    }
}

