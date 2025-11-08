<?php

namespace App\Http\Controllers;

use App\Models\StrategicGoal;
use App\Models\GoalMilestone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StrategicGoalController extends Controller
{
    public function index(Request $request)
    {
        $query = StrategicGoal::where('organization_id', Auth::user()->organization_id)
            ->with(['owner', 'milestones']);

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $goals = $query->orderBy('target_date')->paginate(20);

        return Inertia::render('Decisions/Goals/Index', [
            'goals' => $goals,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function create()
    {
        $users = User::where('organization_id', Auth::user()->organization_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Decisions/Goals/Create', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,completed,cancelled',
            'target_date' => 'required|date',
            'owner_id' => 'nullable|uuid|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $goal = StrategicGoal::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'created_by_id' => Auth::id(),
            ...$validated,
        ]);

        return redirect()->route('decisions.goals.show', $goal->id)->with('message', 'Strategic goal created successfully');
    }

    public function show($id)
    {
        $goal = StrategicGoal::where('organization_id', Auth::user()->organization_id)
            ->with(['owner', 'createdBy', 'milestones'])
            ->findOrFail($id);

        return Inertia::render('Decisions/Goals/Show', [
            'goal' => $goal,
        ]);
    }

    public function edit($id)
    {
        $goal = StrategicGoal::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $users = User::where('organization_id', Auth::user()->organization_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Decisions/Goals/Edit', [
            'goal' => $goal,
            'users' => $users,
        ]);
    }

    public function update(Request $request, $id)
    {
        $goal = StrategicGoal::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,completed,cancelled',
            'target_date' => 'required|date',
            'owner_id' => 'nullable|uuid|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $goal->update($validated);

        return redirect()->route('decisions.goals.show', $goal->id)->with('message', 'Strategic goal updated successfully');
    }

    public function destroy($id)
    {
        $goal = StrategicGoal::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $goal->delete();

        return redirect()->route('decisions.goals.index')->with('message', 'Strategic goal deleted successfully');
    }

    public function addMilestone(Request $request, $id)
    {
        $goal = StrategicGoal::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_date' => 'required|date',
        ]);

        $milestone = GoalMilestone::create([
            'id' => (string) Str::uuid(),
            'strategic_goal_id' => $goal->id,
            'display_order' => $goal->milestones()->max('display_order') + 1,
            ...$validated,
        ]);

        $goal->updateProgress();

        return back()->with('message', 'Milestone added successfully');
    }
}

