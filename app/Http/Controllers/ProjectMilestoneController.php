<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProjectMilestoneController extends Controller
{
    public function index($projectId)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($projectId);

        $milestones = ProjectMilestone::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->orderBy('order')
            ->get();

        return response()->json(['milestones' => $milestones]);
    }

    public function store(Request $request, $projectId)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($projectId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_date' => 'required|date',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $milestone = ProjectMilestone::create([
            'id' => (string) Str::uuid(),
            'project_id' => $projectId,
            'organization_id' => Auth::user()->organization_id,
            'order' => ProjectMilestone::where('project_id', $projectId)->max('order') + 1,
            ...$validated,
        ]);

        return response()->json([
            'milestone' => $milestone,
            'message' => 'Milestone created successfully',
        ]);
    }

    public function update(Request $request, $projectId, $milestoneId)
    {
        $milestone = ProjectMilestone::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($milestoneId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_date' => 'required|date',
            'completed_date' => 'nullable|date',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'order' => 'nullable|integer',
        ]);

        $milestone->update($validated);

        return response()->json([
            'milestone' => $milestone,
            'message' => 'Milestone updated successfully',
        ]);
    }

    public function destroy($projectId, $milestoneId)
    {
        $milestone = ProjectMilestone::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($milestoneId);

        $milestone->delete();

        return response()->json(['message' => 'Milestone deleted successfully']);
    }
}

