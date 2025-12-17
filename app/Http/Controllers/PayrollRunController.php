<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\PayrollItem;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;

class PayrollRunController extends Controller
{
    public function index(Request $request)
    {
        $query = PayrollRun::where('organization_id', Auth::user()->organization_id)
            ->with(['createdBy', 'items']);

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $payrollRuns = $query->orderBy('pay_period', 'desc')->paginate(20);

        return Inertia::render('Payroll/Runs/Index', [
            'payrollRuns' => $payrollRuns,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create()
    {
        $teamMembers = TeamMember::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Payroll/Runs/Create', [
            'teamMembers' => $teamMembers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pay_period' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'team_member_ids' => 'required|array',
            'team_member_ids.*' => 'uuid|exists:team_members,id',
            'notes' => 'nullable|string',
        ]);

        $payrollRun = PayrollRun::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'pay_period' => $validated['pay_period'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'draft',
            'created_by_id' => Auth::id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create payroll items for selected team members
        foreach ($validated['team_member_ids'] as $teamMemberId) {
            $teamMember = TeamMember::find($teamMemberId);
            
            PayrollItem::create([
                'id' => (string) Str::uuid(),
                'payroll_run_id' => $payrollRun->id,
                'team_member_id' => $teamMemberId,
                'basic_salary' => $teamMember->salary ?? 0,
                'gross_pay' => $teamMember->salary ?? 0,
                'net_pay' => $teamMember->salary ?? 0,
            ]);
        }

        return redirect()->route('payroll.runs.show', $payrollRun->id)->with('message', 'Payroll run created successfully');
    }

    public function show($id)
    {
        $payrollRun = PayrollRun::where('organization_id', Auth::user()->organization_id)
            ->with(['createdBy', 'items.teamMember'])
            ->findOrFail($id);

        return Inertia::render('Payroll/Runs/Show', [
            'payrollRun' => $payrollRun,
        ]);
    }

    public function process($id)
    {
        $payrollRun = PayrollRun::where('organization_id', Auth::user()->organization_id)
            ->with('items')
            ->findOrFail($id);

        if ($payrollRun->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft payroll runs can be processed.']);
        }

        $payrollRun->status = 'processing';
        $payrollRun->save();

        // Calculate payroll for each item
        foreach ($payrollRun->items as $item) {
            $item->calculatePay();
        }

        $payrollRun->calculateTotal();
        $payrollRun->status = 'completed';
        $payrollRun->processed_at = now();
        $payrollRun->save();

        return back()->with('message', 'Payroll run processed successfully');
    }
}

