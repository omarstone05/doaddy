<?php

namespace App\Http\Controllers;

use App\Models\TeamMember;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TeamMemberController extends Controller
{
    public function index(Request $request)
    {
        $query = TeamMember::where('organization_id', Auth::user()->organization_id);

        if ($request->has('department_id') && $request->department_id !== '') {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active === 'true');
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%");
            });
        }

        $teamMembers = $query->with(['user', 'department'])->orderBy('first_name')->paginate(20);

        $departments = Department::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Team/Index', [
            'teamMembers' => $teamMembers,
            'departments' => $departments,
            'filters' => $request->only(['department_id', 'is_active', 'search']),
        ]);
    }

    public function create()
    {
        $departments = Department::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $users = User::where('organization_id', Auth::user()->organization_id)
            ->whereDoesntHave('teamMember')
            ->orderBy('name')
            ->get();

        return Inertia::render('Team/Create', [
            'departments' => $departments,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'employee_number' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'employment_type' => 'nullable|in:full_time,part_time,contract,freelance',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'user_id' => 'nullable|uuid|exists:users,id',
            'is_active' => 'boolean',
        ]);

        $teamMember = TeamMember::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            ...$validated,
        ]);

        return redirect()->route('team.show', $teamMember->id)->with('message', 'Team member created successfully');
    }

    public function show($id)
    {
        $teamMember = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->with(['user', 'department', 'sales', 'attachments.uploadedBy', 'documents.createdBy', 'documents.attachments'])
            ->findOrFail($id);

        $organizationId = Auth::user()->organization_id;
        $organizationRoles = \App\Models\OrganizationRole::orderBy('level', 'desc')->get();
        
        // Get user's current role if linked
        $userRole = null;
        if ($teamMember->user) {
            $userRole = $teamMember->user->getOrganizationRole($organizationId);
        }

        return Inertia::render('Team/Show', [
            'teamMember' => $teamMember,
            'organizationRoles' => $organizationRoles,
            'userRole' => $userRole,
        ]);
    }

    public function edit($id)
    {
        $teamMember = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $departments = Department::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $users = User::where('organization_id', Auth::user()->organization_id)
            ->where(function ($q) use ($teamMember) {
                $q->whereDoesntHave('teamMember')
                  ->orWhereHas('teamMember', function ($q) use ($teamMember) {
                      $q->where('id', $teamMember->id);
                  });
            })
            ->orderBy('name')
            ->get();

        return Inertia::render('Team/Edit', [
            'teamMember' => $teamMember,
            'departments' => $departments,
            'users' => $users,
        ]);
    }

    public function update(Request $request, $id)
    {
        $teamMember = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'employee_number' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'employment_type' => 'nullable|in:full_time,part_time,contract,freelance',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'user_id' => 'nullable|uuid|exists:users,id',
            'is_active' => 'boolean',
        ]);

        $teamMember->update($validated);

        return redirect()->route('team.show', $teamMember->id)->with('message', 'Team member updated successfully');
    }

    public function destroy($id)
    {
        $teamMember = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        // Check if team member has sales
        if ($teamMember->sales()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete team member that has sales records.']);
        }

        $teamMember->delete();

        return redirect()->route('team.index')->with('message', 'Team member deleted successfully');
    }

    /**
     * Upload document for user
     */
    public function uploadDocument(Request $request, $id)
    {
        $teamMember = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->with('user')
            ->findOrFail($id);

        if (!$teamMember->user) {
            return back()->withErrors(['error' => 'Team member must be linked to a user account to upload documents.']);
        }

        $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,gif,pdf,doc,docx,xls,xlsx,txt|max:10240',
            'category' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $organizationId = Auth::user()->organization_id;

        // Create attachment
        $attachment = \App\Models\Attachment::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'attachable_type' => 'App\Models\User',
            'attachable_id' => $teamMember->user->id,
            'name' => $file->getClientOriginalName(),
            'file_path' => $file->store("attachments/{$organizationId}", 'public'),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by_id' => Auth::id(),
        ]);

        // Create document record
        $document = \App\Models\Document::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'name' => $file->getClientOriginalName(),
            'description' => "Uploaded for {$teamMember->first_name} {$teamMember->last_name}",
            'category' => $request->category ?? 'user_document',
            'type' => 'file',
            'status' => 'active',
            'created_by_id' => Auth::id(),
        ]);

        // Link document to attachment
        $document->attachments()->attach($attachment->id);

        return back()->with('message', 'Document uploaded successfully');
    }

    /**
     * Grant system access to team member
     */
    public function grantAccess(Request $request, $id)
    {
        $teamMember = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $request->validate([
            'email' => 'required|email|max:255',
            'action' => 'required|in:invite,set_password',
            'password' => 'required_if:action,set_password|string|min:8',
        ]);

        $organizationId = Auth::user()->organization_id;
        $organization = \App\Models\Organization::find($organizationId);

        // Update team member email if different
        if ($teamMember->email !== $request->email) {
            $teamMember->update(['email' => $request->email]);
        }

        // Find or create user account
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            // Create new user account
            $user = User::create([
                'id' => (string) Str::uuid(),
                'name' => trim("{$teamMember->first_name} {$teamMember->last_name}"),
                'email' => $request->email,
                'password' => $request->action === 'set_password' 
                    ? bcrypt($request->password) 
                    : bcrypt(Str::random(16)), // Random password if inviting
                'is_active' => true,
                'email_verified_at' => $request->action === 'set_password' ? null : now(), // Will verify on first login if invited
            ]);
        } else {
            // Update existing user
            $user->update([
                'is_active' => true,
            ]);
            
            // Set password if action is set_password
            if ($request->action === 'set_password') {
                $user->update([
                    'password' => bcrypt($request->password),
                ]);
            }
        }

        // Link team member to user
        $teamMember->update(['user_id' => $user->id]);

        // Ensure user belongs to organization
        if (!$user->belongsToOrganization($organizationId)) {
            // Get default role (Member) if no role specified
            $defaultRole = \App\Models\OrganizationRole::where('slug', 'member')->first();
            
            $user->organizations()->attach($organizationId, [
                'role_id' => $defaultRole?->id,
                'role' => $defaultRole?->slug ?? 'member',
                'is_active' => true,
                'joined_at' => now(),
            ]);
        }

        // Send invitation email if action is 'invite'
        if ($request->action === 'invite') {
            try {
                // Use Laravel's password reset system to send invitation
                $status = \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);
                
                if ($status !== \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
                    \Log::warning('Failed to send password reset link for invitation', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'status' => $status,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send invitation email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the request if email fails
            }
        }

        return back()->with('message', $request->action === 'invite' 
            ? 'Invitation sent successfully. User can now set their password via email.' 
            : 'Password set successfully. User has been activated.');
    }

    /**
     * Update user role and permissions
     */
    public function updateUserRole(Request $request, $id)
    {
        $teamMember = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->with('user')
            ->findOrFail($id);

        if (!$teamMember->user) {
            return back()->withErrors(['error' => 'Team member must be linked to a user account.']);
        }

        $request->validate([
            'role_id' => 'required|exists:organization_roles,id',
        ]);

        $organizationId = Auth::user()->organization_id;
        $role = \App\Models\OrganizationRole::findOrFail($request->role_id);

        // Ensure user belongs to organization
        if (!$teamMember->user->belongsToOrganization($organizationId)) {
            $teamMember->user->organizations()->attach($organizationId, [
                'role_id' => $role->id,
                'role' => $role->slug,
                'is_active' => true,
                'joined_at' => now(),
            ]);
        } else {
            $teamMember->user->organizations()->updateExistingPivot($organizationId, [
                'role_id' => $role->id,
                'role' => $role->slug,
            ]);
        }

        return back()->with('message', 'User role updated successfully');
    }
}

