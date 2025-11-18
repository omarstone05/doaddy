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

        // Get sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort fields
        $allowedSorts = ['name', 'status', 'priority', 'progress_percentage', 'created_at', 'target_completion_date'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        
        $projects = $query->orderBy($sortBy, $sortOrder)->paginate(20);

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters' => $request->only(['status', 'priority', 'search', 'sort_by', 'sort_order']),
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
            'budget' => 'nullable|numeric|min:0',
            'color' => 'nullable|string|max:7',
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

    public function show(Request $request, $id)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->with([
                'projectManager',
                'createdBy',
                'tasks' => function($q) {
                    $q->with(['assignedTo', 'createdBy'])->orderBy('order');
                },
                'milestones' => function($q) {
                    $q->orderBy('order');
                },
                'members.user',
                'timeEntries.user',
                'budgets'
            ])
            ->findOrFail($id);

        // Calculate additional stats
        $taskStats = [
            'total' => $project->tasks->count(),
            'todo' => $project->tasks->where('status', 'todo')->count(),
            'in_progress' => $project->tasks->where('status', 'in_progress')->count(),
            'done' => $project->tasks->where('status', 'done')->count(),
        ];

        $totalTime = $project->timeEntries->sum('hours');
        $totalBudget = $project->budgets->sum('allocated_amount') + ($project->budget ?? 0);
        $totalSpent = $project->budgets->sum('spent_amount') + ($project->spent ?? 0);

        $activeTab = $request->get('tab', 'overview');

        // Get users for dropdowns
        $users = User::where('organization_id', Auth::user()->organization_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'taskStats' => $taskStats,
            'totalTime' => $totalTime,
            'totalBudget' => $totalBudget,
            'totalSpent' => $totalSpent,
            'activeTab' => $activeTab,
            'users' => $users,
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
            'budget' => 'nullable|numeric|min:0',
            'color' => 'nullable|string|max:7',
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

    /**
     * API endpoint to get list of projects with filters
     */
    public function list(Request $request)
    {
        $query = Project::where('organization_id', Auth::user()->organization_id)
            ->with(['projectManager', 'createdBy']);

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority !== '') {
            $query->where('priority', $request->priority);
        }

        if ($request->boolean('overdue')) {
            $query->where('status', 'active')
                ->where('target_completion_date', '<', now())
                ->whereNotNull('target_completion_date');
        }

        $projects = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'projects' => $projects,
        ]);
    }

    /**
     * API endpoint to get projects with budget details
     */
    public function budgetDetails()
    {
        $projects = Project::where('organization_id', Auth::user()->organization_id)
            ->with(['projectManager', 'budgets'])
            ->where(function($q) {
                $q->whereNotNull('budget')
                  ->orWhereHas('budgets');
            })
            ->get()
            ->map(function($project) {
                $totalBudget = $project->budgets->sum('allocated_amount') + ($project->budget ?? 0);
                $totalSpent = $project->budgets->sum('spent_amount') + ($project->spent ?? 0);
                
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'status' => $project->status,
                    'priority' => $project->priority,
                    'progress_percentage' => $project->progress_percentage,
                    'project_manager' => $project->projectManager ? [
                        'name' => $project->projectManager->name,
                    ] : null,
                    'target_completion_date' => $project->target_completion_date,
                    'budget' => $totalBudget,
                    'spent' => $totalSpent,
                ];
            });

        return response()->json([
            'projects' => $projects,
        ]);
    }

    /**
     * API endpoint to get projects with time tracking details
     */
    public function timeDetails()
    {
        $projects = Project::where('organization_id', Auth::user()->organization_id)
            ->with(['projectManager', 'timeEntries'])
            ->whereHas('timeEntries')
            ->get()
            ->map(function($project) {
                $totalTime = $project->timeEntries->sum('hours');
                
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'status' => $project->status,
                    'priority' => $project->priority,
                    'progress_percentage' => $project->progress_percentage,
                    'project_manager' => $project->projectManager ? [
                        'name' => $project->projectManager->name,
                    ] : null,
                    'target_completion_date' => $project->target_completion_date,
                    'total_time' => $totalTime,
                ];
            });

        return response()->json([
            'projects' => $projects,
        ]);
    }
}

