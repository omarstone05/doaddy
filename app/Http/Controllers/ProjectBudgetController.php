<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProjectBudgetController extends Controller
{
    public function index($projectId)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($projectId);

        $budgets = ProjectBudget::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->get();

        return response()->json(['budgets' => $budgets]);
    }

    public function store(Request $request, $projectId)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($projectId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'allocated_amount' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
        ]);

        $budget = ProjectBudget::create([
            'id' => (string) Str::uuid(),
            'project_id' => $projectId,
            'organization_id' => Auth::user()->organization_id,
            'spent_amount' => 0,
            ...$validated,
        ]);

        return response()->json([
            'budget' => $budget,
            'message' => 'Budget created successfully',
        ]);
    }

    public function update(Request $request, $projectId, $budgetId)
    {
        $budget = ProjectBudget::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($budgetId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'allocated_amount' => 'required|numeric|min:0',
            'spent_amount' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:255',
        ]);

        $budget->update($validated);

        return response()->json([
            'budget' => $budget,
            'message' => 'Budget updated successfully',
        ]);
    }

    public function destroy($projectId, $budgetId)
    {
        $budget = ProjectBudget::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($budgetId);

        $budget->delete();

        return response()->json(['message' => 'Budget deleted successfully']);
    }
}

