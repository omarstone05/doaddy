<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProjectMemberController extends Controller
{
    public function index($projectId)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($projectId);

        $members = ProjectMember::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->with('user')
            ->get();

        $availableUsers = User::where('organization_id', Auth::user()->organization_id)
            ->whereNotIn('id', $members->pluck('user_id'))
            ->orderBy('name')
            ->get();

        return response()->json([
            'members' => $members,
            'availableUsers' => $availableUsers,
        ]);
    }

    public function store(Request $request, $projectId)
    {
        $project = Project::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($projectId);

        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'role' => 'required|in:manager,member,viewer,contributor',
            'permissions' => 'nullable|array',
        ]);

        // Check if user is already a member
        $existing = ProjectMember::where('project_id', $projectId)
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'User is already a member'], 422);
        }

        $member = ProjectMember::create([
            'id' => (string) Str::uuid(),
            'project_id' => $projectId,
            'organization_id' => Auth::user()->organization_id,
            'joined_at' => now(),
            ...$validated,
        ]);

        return response()->json([
            'member' => $member->load('user'),
            'message' => 'Member added successfully',
        ]);
    }

    public function update(Request $request, $projectId, $memberId)
    {
        $member = ProjectMember::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($memberId);

        $validated = $request->validate([
            'role' => 'required|in:manager,member,viewer,contributor',
            'permissions' => 'nullable|array',
        ]);

        $member->update($validated);

        return response()->json([
            'member' => $member->load('user'),
            'message' => 'Member updated successfully',
        ]);
    }

    public function destroy($projectId, $memberId)
    {
        $member = ProjectMember::where('project_id', $projectId)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail($memberId);

        $member->delete();

        return response()->json(['message' => 'Member removed successfully']);
    }
}

