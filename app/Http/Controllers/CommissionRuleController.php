<?php

namespace App\Http\Controllers;

use App\Models\CommissionRule;
use App\Models\TeamMember;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CommissionRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = CommissionRule::where('organization_id', Auth::user()->organization_id);

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active === 'true');
        }

        if ($request->has('applicable_to') && $request->applicable_to !== '') {
            $query->where('applicable_to', $request->applicable_to);
        }

        $commissionRules = $query->with(['teamMember', 'department'])
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Commissions/Rules/Index', [
            'commissionRules' => $commissionRules,
            'filters' => $request->only(['is_active', 'applicable_to']),
        ]);
    }

    public function create()
    {
        $teamMembers = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $departments = Department::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Commissions/Rules/Create', [
            'teamMembers' => $teamMembers,
            'departments' => $departments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rule_type' => 'required|in:percentage,fixed,tiered',
            'rate' => 'required_if:rule_type,percentage|nullable|numeric|min:0|max:100',
            'fixed_amount' => 'required_if:rule_type,fixed|nullable|numeric|min:0',
            'tiers' => 'required_if:rule_type,tiered|nullable|array',
            'applicable_to' => 'required|in:all,team_member,department',
            'team_member_id' => 'required_if:applicable_to,team_member|nullable|uuid|exists:team_members,id',
            'department_id' => 'required_if:applicable_to,department|nullable|uuid|exists:departments,id',
            'is_active' => 'boolean',
        ]);

        $commissionRule = CommissionRule::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            ...$validated,
        ]);

        return redirect()->route('commissions.rules.index')->with('message', 'Commission rule created successfully');
    }

    public function edit($id)
    {
        $commissionRule = CommissionRule::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $teamMembers = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $departments = Department::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Commissions/Rules/Edit', [
            'commissionRule' => $commissionRule,
            'teamMembers' => $teamMembers,
            'departments' => $departments,
        ]);
    }

    public function update(Request $request, $id)
    {
        $commissionRule = CommissionRule::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rule_type' => 'required|in:percentage,fixed,tiered',
            'rate' => 'required_if:rule_type,percentage|nullable|numeric|min:0|max:100',
            'fixed_amount' => 'required_if:rule_type,fixed|nullable|numeric|min:0',
            'tiers' => 'required_if:rule_type,tiered|nullable|array',
            'applicable_to' => 'required|in:all,team_member,department',
            'team_member_id' => 'required_if:applicable_to,team_member|nullable|uuid|exists:team_members,id',
            'department_id' => 'required_if:applicable_to,department|nullable|uuid|exists:departments,id',
            'is_active' => 'boolean',
        ]);

        $commissionRule->update($validated);

        return redirect()->route('commissions.rules.index')->with('message', 'Commission rule updated successfully');
    }

    public function destroy($id)
    {
        $commissionRule = CommissionRule::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        // Check if rule has earnings
        if ($commissionRule->earnings()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete commission rule that has earnings.']);
        }

        $commissionRule->delete();

        return redirect()->route('commissions.rules.index')->with('message', 'Commission rule deleted successfully');
    }
}

