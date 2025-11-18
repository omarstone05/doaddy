<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTimeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProjectTimeEntryController extends Controller
{
    public function index($projectId)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($projectId);

        $timeEntries = ProjectTimeEntry::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->with(['user', 'task'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['timeEntries' => $timeEntries]);
    }

    public function store(Request $request, $projectId)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($projectId);

        $validated = $request->validate([
            'task_id' => 'nullable|uuid|exists:project_tasks,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.1|max:24',
            'description' => 'nullable|string',
            'is_billable' => 'boolean',
            'billable_rate' => 'nullable|numeric|min:0',
        ]);

        $timeEntry = ProjectTimeEntry::create([
            'id' => (string) Str::uuid(),
            'project_id' => $projectId,
            'organization_id' => Auth::user()->organization_id,
            'user_id' => Auth::id(),
            'is_billable' => $validated['is_billable'] ?? true,
            ...$validated,
        ]);

        return response()->json([
            'timeEntry' => $timeEntry->load(['user', 'task']),
            'message' => 'Time entry created successfully',
        ]);
    }

    public function update(Request $request, $projectId, $timeEntryId)
    {
        $timeEntry = ProjectTimeEntry::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($timeEntryId);

        $validated = $request->validate([
            'task_id' => 'nullable|uuid|exists:project_tasks,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.1|max:24',
            'description' => 'nullable|string',
            'is_billable' => 'boolean',
            'billable_rate' => 'nullable|numeric|min:0',
        ]);

        $timeEntry->update($validated);

        return response()->json([
            'timeEntry' => $timeEntry->load(['user', 'task']),
            'message' => 'Time entry updated successfully',
        ]);
    }

    public function destroy($projectId, $timeEntryId)
    {
        $timeEntry = ProjectTimeEntry::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($timeEntryId);

        $timeEntry->delete();

        return response()->json(['message' => 'Time entry deleted successfully']);
    }
}

