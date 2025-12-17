<?php

namespace App\Modules\ZambianHR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Modules\ZambianHR\Models\GratuityCalculation;
use App\Modules\HR\Models\Employee;

class GratuityController extends Controller
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

        $calculations = GratuityCalculation::where('organization_id', $organization->id)
            ->with('employee')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('ZambianHR/Gratuity/Index', [
            'calculations' => $calculations,
        ]);
    }

    public function calculate($employeeId)
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $employee = Employee::where('organization_id', $organization->id)
            ->findOrFail($employeeId);

        // Calculate years of service
        $startDate = Carbon::parse($employee->hired_date);
        $endDate = Carbon::now();
        $yearsOfService = $startDate->diffInYears($endDate);
        $monthsOfService = $startDate->diffInMonths($endDate);
        
        // Calculate gratuity (25% of basic salary per year)
        $baseSalary = $employee->base_salary ?? 0;
        $gratuityRate = 0.25; // 25%
        $totalGratuity = ($baseSalary * $yearsOfService) * $gratuityRate;
        
        // Pro-rata for partial years
        $proratedMonths = $monthsOfService % 12;
        $proratedAmount = ($baseSalary * ($proratedMonths / 12)) * $gratuityRate;

        return Inertia::render('ZambianHR/Gratuity/Calculate', [
            'employee' => $employee,
            'preview' => [
                'years_of_service' => $yearsOfService,
                'months_of_service' => $monthsOfService,
                'base_salary' => $baseSalary,
                'gratuity_rate' => $gratuityRate,
                'total_gratuity' => $totalGratuity,
                'prorated_amount' => $proratedAmount,
            ],
        ]);
    }

    public function store(Request $request, $employeeId)
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $validated = $request->validate([
            'employment_end_date' => 'required|date',
            'base_salary_used' => 'required|numeric|min:0',
            'deductions_amount' => 'nullable|numeric|min:0',
            'deductions_reason' => 'nullable|string',
        ]);

        $employee = Employee::where('organization_id', $organization->id)
            ->findOrFail($employeeId);

        DB::beginTransaction();
        try {
            $startDate = Carbon::parse($employee->hired_date);
            $endDate = Carbon::parse($validated['employment_end_date']);
            $yearsOfService = $startDate->diffInYears($endDate);
            $monthsOfService = $startDate->diffInMonths($endDate);
            
            $gratuityRate = 0.25; // 25%
            $totalGratuity = ($validated['base_salary_used'] * $yearsOfService) * $gratuityRate;
            
            // Pro-rata for partial years
            $proratedMonths = $monthsOfService % 12;
            $proratedAmount = ($validated['base_salary_used'] * ($proratedMonths / 12)) * $gratuityRate;
            
            $deductions = $validated['deductions_amount'] ?? 0;
            $netGratuity = ($totalGratuity + $proratedAmount) - $deductions;

            $calculation = GratuityCalculation::create([
                'id' => (string) Str::uuid(),
                'employee_id' => $employee->id,
                'organization_id' => $organization->id,
                'calculation_date' => now(),
                'employment_start_date' => $employee->hired_date,
                'employment_end_date' => $validated['employment_end_date'],
                'years_of_service' => $yearsOfService,
                'months_of_service' => $monthsOfService,
                'base_salary_used' => $validated['base_salary_used'],
                'gratuity_rate' => $gratuityRate,
                'total_gratuity_amount' => $totalGratuity,
                'prorated_amount' => $proratedAmount,
                'deductions_amount' => $deductions,
                'deductions_reason' => $validated['deductions_reason'] ?? null,
                'net_gratuity_amount' => $netGratuity,
                'calculation_formula' => "Gratuity = 25% × (Base Salary × Years of Service)",
                'calculation_breakdown' => [
                    'base_salary' => $validated['base_salary_used'],
                    'years_of_service' => $yearsOfService,
                    'gratuity_rate' => $gratuityRate,
                    'total_gratuity' => $totalGratuity,
                    'prorated_months' => $proratedMonths,
                    'prorated_amount' => $proratedAmount,
                    'deductions' => $deductions,
                    'net_gratuity' => $netGratuity,
                ],
                'status' => 'calculated',
            ]);

            DB::commit();

            return redirect()->route('zambian-hr.gratuity.show', $calculation->id)
                ->with('message', 'Gratuity calculated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to calculate gratuity: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $calculation = GratuityCalculation::where('organization_id', $organization->id)
            ->with('employee')
            ->findOrFail($id);

        return Inertia::render('ZambianHR/Gratuity/Show', [
            'calculation' => $calculation,
        ]);
    }
}

