<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveType::where('organization_id', Auth::user()->organization_id);

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active === 'true');
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $leaveTypes = $query->orderBy('name')->paginate(20);

        return Inertia::render('Leave/Types/Index', [
            'leaveTypes' => $leaveTypes,
            'filters' => $request->only(['is_active', 'search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Leave/Types/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'maximum_days_per_year' => 'required|integer|min:0',
            'can_carry_forward' => 'boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $leaveType = LeaveType::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            ...$validated,
        ]);

        return redirect()->route('leave.types.index')->with('message', 'Leave type created successfully');
    }

    public function edit($id)
    {
        $leaveType = LeaveType::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        return Inertia::render('Leave/Types/Edit', [
            'leaveType' => $leaveType,
        ]);
    }

    public function update(Request $request, $id)
    {
        $leaveType = LeaveType::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'maximum_days_per_year' => 'required|integer|min:0',
            'can_carry_forward' => 'boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $leaveType->update($validated);

        return redirect()->route('leave.types.index')->with('message', 'Leave type updated successfully');
    }

    public function destroy($id)
    {
        $leaveType = LeaveType::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        // Check if leave type has requests
        if ($leaveType->leaveRequests()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete leave type that has leave requests.']);
        }

        $leaveType->delete();

        return redirect()->route('leave.types.index')->with('message', 'Leave type deleted successfully');
    }
}

