<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $orgId = $user->organization_id;
        
        // Check permission
        if (!$user->hasPermissionInOrganization($orgId, 'documents.view')) {
            abort(403, 'You do not have permission to view documents.');
        }
        
        $query = Document::where('organization_id', $orgId);

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('category') && $request->category !== '') {
            $query->where('category', $request->category);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $documents = $query->with(['createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get categories for filter
        $categories = Document::where('organization_id', Auth::user()->organization_id)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('Compliance/Documents/Index', [
            'documents' => $documents,
            'filters' => $request->only(['status', 'category', 'search']),
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $orgId = $user->organization_id;
        
        // Check permission
        if (!$user->hasPermissionInOrganization($orgId, 'documents.create')) {
            abort(403, 'You do not have permission to create documents.');
        }
        
        return Inertia::render('Compliance/Documents/Create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $orgId = $user->organization_id;
        
        // Check permission
        if (!$user->hasPermissionInOrganization($orgId, 'documents.create')) {
            abort(403, 'You do not have permission to create documents.');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'status' => 'required|in:draft,active,archived',
        ]);

        $document = Document::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'created_by_id' => Auth::id(),
            ...$validated,
        ]);

        return redirect()->route('compliance.documents.show', $document->id)->with('message', 'Document created successfully');
    }

    public function show($id)
    {
        $user = Auth::user();
        $orgId = $user->organization_id;
        
        // Check permission
        if (!$user->hasPermissionInOrganization($orgId, 'documents.view')) {
            abort(403, 'You do not have permission to view documents.');
        }
        
        $document = Document::where('organization_id', $orgId)
            ->with(['createdBy', 'versions', 'attachments'])
            ->findOrFail($id);

        return Inertia::render('Compliance/Documents/Show', [
            'document' => $document,
        ]);
    }

    public function edit($id)
    {
        $user = Auth::user();
        $orgId = $user->organization_id;
        
        // Check permission
        if (!$user->hasPermissionInOrganization($orgId, 'documents.update')) {
            abort(403, 'You do not have permission to edit documents.');
        }
        
        $document = Document::where('organization_id', $orgId)
            ->findOrFail($id);

        return Inertia::render('Compliance/Documents/Edit', [
            'document' => $document,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $orgId = $user->organization_id;
        
        // Check permission
        if (!$user->hasPermissionInOrganization($orgId, 'documents.update')) {
            abort(403, 'You do not have permission to update documents.');
        }
        
        $document = Document::where('organization_id', $orgId)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'status' => 'required|in:draft,active,archived',
        ]);

        $document->update($validated);

        return redirect()->route('compliance.documents.show', $document->id)->with('message', 'Document updated successfully');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $orgId = $user->organization_id;
        
        // Check permission
        if (!$user->hasPermissionInOrganization($orgId, 'documents.delete')) {
            abort(403, 'You do not have permission to delete documents.');
        }
        
        $document = Document::where('organization_id', $orgId)
            ->findOrFail($id);

        $document->delete();

        return redirect()->route('compliance.documents.index')->with('message', 'Document deleted successfully');
    }

    /**
     * Assign document to team members
     */
    public function assignToTeamMembers(Request $request, $id)
    {
        $document = Document::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'team_member_ids' => 'required|array',
            'team_member_ids.*' => 'required|uuid|exists:team_members,id',
        ]);

        // Verify all team members belong to the same organization
        $teamMemberIds = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->whereIn('id', $validated['team_member_ids'])
            ->pluck('id')
            ->toArray();

        $document->teamMembers()->sync($teamMemberIds);

        return response()->json([
            'success' => true,
            'message' => 'Document assigned to team members successfully',
        ]);
    }

    /**
     * Unassign document from team member
     */
    public function unassignFromTeamMember(Request $request, $id, $teamMemberId)
    {
        $document = Document::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $teamMember = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($teamMemberId);

        $document->teamMembers()->detach($teamMember->id);

        return response()->json([
            'success' => true,
            'message' => 'Document unassigned from team member successfully',
        ]);
    }
}

