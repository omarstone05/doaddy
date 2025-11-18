<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTimeEntry;
use App\Models\ProjectBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class ProjectReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Projects/Reports/Index');
    }

    public function performance(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now()->toDateString());

        $projects = Project::where('organization_id', $organizationId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->with(['tasks', 'milestones', 'timeEntries'])
            ->get()
            ->map(function ($project) {
                $totalTasks = $project->tasks->count();
                $completedTasks = $project->tasks->where('status', 'done')->count();
                $totalTime = $project->timeEntries->sum('hours');
                $completedMilestones = $project->milestones->where('status', 'completed')->count();
                $totalMilestones = $project->milestones->count();

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'progress_percentage' => $project->progress_percentage,
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'task_completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
                    'total_time' => $totalTime,
                    'milestones_completed' => $completedMilestones,
                    'total_milestones' => $totalMilestones,
                    'milestone_completion_rate' => $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100, 2) : 0,
                    'budget' => $project->budget,
                    'spent' => $project->spent,
                    'budget_utilization' => $project->budget > 0 ? round(($project->spent / $project->budget) * 100, 2) : 0,
                ];
            });

        return Inertia::render('Projects/Reports/Performance', [
            'projects' => $projects,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function timeTracking(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $projectId = $request->input('project_id');
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now()->toDateString());

        $query = ProjectTimeEntry::where('organization_id', $organizationId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->with(['project', 'user', 'task']);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $timeEntries = $query->get();

        // Group by project
        $byProject = $timeEntries->groupBy('project_id')->map(function ($entries, $projectId) {
            $project = $entries->first()->project;
            return [
                'project_id' => $projectId,
                'project_name' => $project->name ?? 'Unknown',
                'total_hours' => $entries->sum('hours'),
                'billable_hours' => $entries->where('is_billable', true)->sum('hours'),
                'total_cost' => $entries->sum(function ($entry) {
                    return $entry->hours * ($entry->billable_rate ?? 0);
                }),
                'entries_count' => $entries->count(),
            ];
        })->values();

        // Group by user
        $byUser = $timeEntries->groupBy('user_id')->map(function ($entries, $userId) {
            $user = $entries->first()->user;
            return [
                'user_id' => $userId,
                'user_name' => $user->name ?? 'Unknown',
                'total_hours' => $entries->sum('hours'),
                'billable_hours' => $entries->where('is_billable', true)->sum('hours'),
                'entries_count' => $entries->count(),
            ];
        })->values();

        // Daily breakdown
        $dailyBreakdown = $timeEntries->groupBy(function ($entry) {
            return Carbon::parse($entry->date)->format('Y-m-d');
        })->map(function ($entries, $date) {
            return [
                'date' => $date,
                'hours' => $entries->sum('hours'),
                'entries_count' => $entries->count(),
            ];
        })->values()->sortBy('date');

        $projects = Project::where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Projects/Reports/TimeTracking', [
            'byProject' => $byProject,
            'byUser' => $byUser,
            'dailyBreakdown' => $dailyBreakdown,
            'projects' => $projects,
            'selectedProjectId' => $projectId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function budget(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $projectId = $request->input('project_id');

        $query = Project::where('organization_id', $organizationId);

        if ($projectId) {
            $query->where('id', $projectId);
        }

        $projects = $query->with('budgets')->get()->map(function ($project) {
            $totalBudget = $project->budgets->sum('allocated_amount');
            $totalSpent = $project->budgets->sum('spent_amount');
            $projectBudget = $project->budget ?? 0;
            $projectSpent = $project->spent ?? 0;

            return [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'total_budget' => $totalBudget + $projectBudget,
                'total_spent' => $totalSpent + $projectSpent,
                'remaining' => ($totalBudget + $projectBudget) - ($totalSpent + $projectSpent),
                'utilization_percentage' => ($totalBudget + $projectBudget) > 0 
                    ? round((($totalSpent + $projectSpent) / ($totalBudget + $projectBudget)) * 100, 2) 
                    : 0,
                'budget_lines' => $project->budgets->map(function ($budget) {
                    return [
                        'name' => $budget->name,
                        'allocated' => $budget->allocated_amount,
                        'spent' => $budget->spent_amount,
                        'remaining' => $budget->allocated_amount - $budget->spent_amount,
                        'utilization' => $budget->allocated_amount > 0 
                            ? round(($budget->spent_amount / $budget->allocated_amount) * 100, 2) 
                            : 0,
                    ];
                }),
            ];
        });

        $allProjects = Project::where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Projects/Reports/Budget', [
            'projects' => $projects,
            'allProjects' => $allProjects,
            'selectedProjectId' => $projectId,
        ]);
    }
}

