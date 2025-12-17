<?php

namespace App\Modules\ZambianHR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Modules\ZambianHR\Models\ContractRenewal;
use App\Modules\HR\Models\Employee;

class ContractRenewalController extends Controller
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

        $renewals = ContractRenewal::where('organization_id', $organization->id)
            ->with('employee')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('ZambianHR/ContractRenewals/Index', [
            'renewals' => $renewals,
        ]);
    }

    public function create()
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $employees = Employee::where('organization_id', $organization->id)
            ->where('is_active', true)
            ->whereNotNull('contract_end_date')
            ->get();

        return Inertia::render('ZambianHR/ContractRenewals/Create', [
            'employees' => $employees,
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
            'new_contract_start' => 'required|date',
            'new_contract_end' => 'required|date|after:new_contract_start',
            'new_contract_type' => 'nullable|string',
            'new_salary' => 'nullable|numeric|min:0',
            'new_job_title' => 'nullable|string|max:255',
            'changes_summary' => 'nullable|string',
            'renewal_deadline' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::findOrFail($validated['employee_id']);

            $renewal = ContractRenewal::create([
                'id' => (string) Str::uuid(),
                'employee_id' => $employee->id,
                'organization_id' => $organization->id,
                'current_contract_start' => $employee->contract_start_date ?? now(),
                'current_contract_end' => $employee->contract_end_date ?? now(),
                'renewal_status' => 'offered',
                'renewal_offered_date' => now(),
                'renewal_deadline' => $validated['renewal_deadline'] ?? now()->addDays(14),
                'new_contract_start' => $validated['new_contract_start'],
                'new_contract_end' => $validated['new_contract_end'],
                'new_contract_type' => $validated['new_contract_type'] ?? $employee->contract_type,
                'new_salary' => $validated['new_salary'] ?? $employee->base_salary,
                'new_job_title' => $validated['new_job_title'] ?? $employee->job_title,
                'changes_summary' => $validated['changes_summary'] ?? null,
                'initiated_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('zambian-hr.contract-renewals.show', $renewal->id)
                ->with('message', 'Contract renewal offered successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create contract renewal: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $renewal = ContractRenewal::where('organization_id', $organization->id)
            ->with('employee')
            ->findOrFail($id);

        return Inertia::render('ZambianHR/ContractRenewals/Show', [
            'renewal' => $renewal,
        ]);
    }
}

