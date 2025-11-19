<?php

namespace App\Modules\ZambianHR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Modules\ZambianHR\Models\Termination;
use App\Modules\HR\Models\Employee;

class TerminationController extends Controller
{
    protected function getOrganization(): ?\App\Models\Organization
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $currentOrgId = session('current_organization_id');
        if ($currentOrgId) {
            $org = $user->organizations()->where('organizations.id', $currentOrgId)->first();
            if ($org) {
                return $org;
            }
        }
        
        if ($user->attributes['organization_id'] ?? null) {
            $org = $user->organizations()->where('organizations.id', $user->attributes['organization_id'])->first();
            if ($org) {
                return $org;
            }
        }
        
        return $user->organizations()->first();
    }

    public function index()
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $terminations = Termination::where('organization_id', $organization->id)
            ->with('employee')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('ZambianHR/Terminations/Index', [
            'terminations' => $terminations,
        ]);
    }

    public function create($employeeId)
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $employee = Employee::where('organization_id', $organization->id)
            ->findOrFail($employeeId);

        return Inertia::render('ZambianHR/Terminations/Create', [
            'employee' => $employee,
        ]);
    }

    public function store(Request $request)
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:hr_employees,id',
            'termination_type' => 'required|in:resignation,notice,medical_discharge,redundancy,summary_dismissal,retirement,death,contract_expiry,mutual_agreement,abandonment',
            'termination_date' => 'required|date',
            'last_working_day' => 'required|date',
            'reason_category' => 'nullable|string',
            'reason_details' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::findOrFail($validated['employee_id']);
            
            $startDate = Carbon::parse($employee->hired_date);
            $endDate = Carbon::parse($validated['termination_date']);
            $yearsOfService = $startDate->diffInYears($endDate);
            $monthsOfService = $startDate->diffInMonths($endDate);
            
            $baseSalary = $employee->base_salary ?? 0;
            
            // Calculate severance based on termination type
            $severanceAmount = 0;
            $medicalDischargeTotal = 0;
            $redundancyTotal = 0;
            $noticePaymentInLieu = 0;
            
            if ($validated['termination_type'] === 'medical_discharge') {
                $medicalDischargeTotal = ($baseSalary * 3) * $yearsOfService; // 3 months per year
                $severanceAmount = $medicalDischargeTotal;
            } elseif ($validated['termination_type'] === 'redundancy') {
                $redundancyTotal = ($baseSalary * 2) * $yearsOfService; // 2 months per year
                $severanceAmount = $redundancyTotal;
            } elseif ($validated['termination_type'] === 'notice') {
                $noticePaymentInLieu = $baseSalary; // 1 month
            }
            
            // Calculate gratuity (25% of basic pay per year)
            $gratuityAmount = ($baseSalary * $yearsOfService) * 0.25;
            $proratedMonths = $monthsOfService % 12;
            $gratuityProrated = ($baseSalary * ($proratedMonths / 12)) * 0.25;
            $totalGratuity = $gratuityAmount + $gratuityProrated;
            
            // Calculate total settlement
            $totalGross = $severanceAmount + $noticePaymentInLieu + $totalGratuity;
            $netSettlement = $totalGross; // Deductions can be added later

            $termination = Termination::create([
                'id' => (string) Str::uuid(),
                'employee_id' => $employee->id,
                'organization_id' => $organization->id,
                'termination_type' => $validated['termination_type'],
                'termination_date' => $validated['termination_date'],
                'last_working_day' => $validated['last_working_day'],
                'notice_required_days' => 30,
                'notice_served_days' => 0,
                'notice_payment_in_lieu' => $noticePaymentInLieu,
                'reason_category' => $validated['reason_category'] ?? null,
                'reason_details' => $validated['reason_details'] ?? null,
                'severance_type' => $validated['termination_type'],
                'severance_amount' => $severanceAmount,
                'medical_discharge_months_per_year' => 3.00,
                'medical_discharge_total' => $medicalDischargeTotal,
                'redundancy_months_per_year' => 2.00,
                'redundancy_total' => $redundancyTotal,
                'gratuity_amount' => $totalGratuity,
                'gratuity_prorated' => $proratedMonths > 0,
                'total_gross_amount' => $totalGross,
                'total_deductions' => 0,
                'net_settlement_amount' => $netSettlement,
                'initiated_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('zambian-hr.terminations.show', $termination->id)
                ->with('message', 'Termination processed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process termination: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $termination = Termination::where('organization_id', $organization->id)
            ->with('employee')
            ->findOrFail($id);

        return Inertia::render('ZambianHR/Terminations/Show', [
            'termination' => $termination,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $termination = Termination::where('organization_id', $organization->id)
            ->findOrFail($id);

        $termination->update([
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('message', 'Termination approved successfully');
    }
}

