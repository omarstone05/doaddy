<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectMilestone;
use App\Models\AddyInsight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class ProjectsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $organizationId = session('current_organization_id') ?? $user->current_organization_id ?? $user->organization_id;
        
        // Calculate stats
        $totalProjects = Project::where('organization_id', $organizationId)->count();
        $activeProjects = Project::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->count();
        
        $completedProjects = Project::where('organization_id', $organizationId)
            ->where('status', 'completed')
            ->count();
        
        $overdueProjects = Project::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->where('target_completion_date', '<', Carbon::now())
            ->whereNotNull('target_completion_date')
            ->count();
        
        // Calculate total tasks
        $totalTasks = ProjectTask::where('organization_id', $organizationId)->count();
        $completedTasks = ProjectTask::where('organization_id', $organizationId)
            ->where('status', 'done')
            ->count();
        
        // Calculate total time
        $totalTime = \App\Models\ProjectTimeEntry::where('organization_id', $organizationId)
            ->sum('hours');
        
        // Calculate budget stats
        $totalBudget = Project::where('organization_id', $organizationId)
            ->sum('budget');
        $totalSpent = Project::where('organization_id', $organizationId)
            ->sum('spent');
        
        // Chart data: Project status distribution
        $statusDistribution = Project::where('organization_id', $organizationId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst(str_replace('_', ' ', $item->status)),
                    'value' => $item->count,
                ];
            });
        
        // Chart data: Task status breakdown
        $taskStatusBreakdown = ProjectTask::where('organization_id', $organizationId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst(str_replace('_', ' ', $item->status)),
                    'value' => $item->count,
                ];
            });
        
        // Chart data: Project priority distribution
        $priorityDistribution = Project::where('organization_id', $organizationId)
            ->selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst($item->priority),
                    'value' => $item->count,
                ];
            });
        
        // Chart data: Project progress over time (last 6 months)
        // Use database-agnostic date formatting
        $dbDriver = DB::connection()->getDriverName();
        if ($dbDriver === 'sqlite') {
            $progressData = Project::where('organization_id', $organizationId)
                ->where('created_at', '>=', Carbon::now()->subMonths(6))
                ->selectRaw('strftime("%Y-%m", created_at) as month, AVG(progress_percentage) as avg_progress')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => Carbon::createFromFormat('Y-m', $item->month)->format('M Y'),
                        'progress' => round($item->avg_progress, 1),
                    ];
                });
        } else {
            $progressData = Project::where('organization_id', $organizationId)
                ->where('created_at', '>=', Carbon::now()->subMonths(6))
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, AVG(progress_percentage) as avg_progress')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => Carbon::createFromFormat('Y-m', $item->month)->format('M Y'),
                        'progress' => round($item->avg_progress, 1),
                    ];
                });
        }
        
        // Get recent projects
        $recentProjects = Project::where('organization_id', $organizationId)
            ->with(['projectManager', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'priority' => $project->priority,
                    'progress_percentage' => $project->progress_percentage,
                    'created_at' => $project->created_at,
                    'project_manager' => $project->projectManager ? [
                        'name' => $project->projectManager->name,
                    ] : null,
                ];
            });
        
        // Get Projects-specific insights
        $insights = AddyInsight::active($organizationId)
            ->where(function($query) {
                $query->where('category', 'projects')
                      ->orWhere('category', 'cross-section');
            })
            ->orderBy('priority', 'desc')
            ->limit(3)
            ->get()
            ->map(fn($insight) => [
                'id' => $insight->id,
                'type' => $insight->type,
                'title' => $insight->title,
                'description' => $insight->description,
                'priority' => (float) $insight->priority,
                'is_actionable' => $insight->is_actionable,
                'action_url' => $insight->action_url,
            ]);

        return Inertia::render('Projects/SectionIndex', [
            'stats' => [
                'total_projects' => $totalProjects,
                'active_projects' => $activeProjects,
                'completed_projects' => $completedProjects,
                'overdue_projects' => $overdueProjects,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'total_time' => (float) $totalTime,
                'total_budget' => (float) $totalBudget,
                'total_spent' => (float) $totalSpent,
            ],
            'chartData' => [
                'statusDistribution' => $statusDistribution,
                'taskStatusBreakdown' => $taskStatusBreakdown,
                'priorityDistribution' => $priorityDistribution,
                'progressTrend' => $progressData,
            ],
            'recent_projects' => $recentProjects,
            'insights' => $insights,
        ]);
    }
}

