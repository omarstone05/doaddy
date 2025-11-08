<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveRequest::where('organization_id', Auth::user()->organization_id)
            ->with(['teamMember', 'leaveType', 'approvedBy']);

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('team_member_id') && $request->team_member_id !== '') {
            $query->where('team_member_id', $request->team_member_id);
        }

        if ($request->has('leave_type_id') && $request->leave_type_id !== '') {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $leaveRequests = $query->orderBy('created_at', 'desc')->paginate(20);

        $teamMembers = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $leaveTypes = LeaveType::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Leave/Requests/Index', [
            'leaveRequests' => $leaveRequests,
            'teamMembers' => $teamMembers,
            'leaveTypes' => $leaveTypes,
            'filters' => $request->only(['status', 'team_member_id', 'leave_type_id']),
        ]);
    }

    public function create()
    {
        $teamMembers = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $leaveTypes = LeaveType::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Leave/Requests/Create', [
            'teamMembers' => $teamMembers,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'team_member_id' => 'required|uuid|exists:team_members,id',
            'leave_type_id' => 'required|uuid|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        
        // Calculate business days
        $days = 0;
        $current = $start->copy();
        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $days++;
            }
            $current->addDay();
        }

        $leaveRequest = LeaveRequest::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'team_member_id' => $validated['team_member_id'],
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'number_of_days' => $days,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('leave.requests.show', $leaveRequest->id)->with('message', 'Leave request submitted successfully');
    }

    public function show($id)
    {
        $leaveRequest = LeaveRequest::where('organization_id', Auth::user()->organization_id)
            ->with(['teamMember', 'leaveType', 'approvedBy'])
            ->findOrFail($id);

        return Inertia::render('Leave/Requests/Show', [
            'leaveRequest' => $leaveRequest,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending leave requests can be approved.']);
        }

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by_id' => Auth::id(),
            'approved_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        return back()->with('message', 'Leave request approved successfully');
    }

    public function reject(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending leave requests can be rejected.']);
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by_id' => Auth::id(),
            'approved_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        return back()->with('message', 'Leave request rejected');
    }
}

