<?php

namespace App\Modules\ZambianHR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Modules\ZambianHR\Models\ConflictOfInterest;
use App\Modules\HR\Models\Employee;

class ConflictOfInterestController extends Controller
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

        $declarations = ConflictOfInterest::where('organization_id', $organization->id)
            ->with('employee')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('ZambianHR/ConflictOfInterest/Index', [
            'declarations' => $declarations,
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
            ->get();

        return Inertia::render('ZambianHR/ConflictOfInterest/Create', [
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
            'declaration_type' => 'required|in:outside_employment,board_membership,business_interest,family_business,shareholding,consultancy,other',
            'organization_name' => 'required|string|max:255',
            'position_held' => 'nullable|string|max:255',
            'nature_of_interest' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_ongoing' => 'boolean',
            'monetary_value' => 'nullable|numeric|min:0',
            'ownership_percentage' => 'nullable|numeric|min:0|max:100',
            'supporting_documents' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $declaration = ConflictOfInterest::create([
                'id' => (string) Str::uuid(),
                'employee_id' => $validated['employee_id'],
                'organization_id' => $organization->id,
                'declaration_type' => $validated['declaration_type'],
                'organization_name' => $validated['organization_name'],
                'position_held' => $validated['position_held'] ?? null,
                'nature_of_interest' => $validated['nature_of_interest'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'is_ongoing' => $validated['is_ongoing'] ?? true,
                'monetary_value' => $validated['monetary_value'] ?? null,
                'ownership_percentage' => $validated['ownership_percentage'] ?? null,
                'status' => 'declared',
                'declared_date' => now(),
                'requires_annual_renewal' => true,
                'next_renewal_due' => now()->addYear(),
                'supporting_documents' => $validated['supporting_documents'] ?? [],
            ]);

            DB::commit();

            return redirect()->route('zambian-hr.conflict-of-interest.show', $declaration->id)
                ->with('message', 'Conflict of interest declared successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to declare conflict of interest: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $declaration = ConflictOfInterest::where('organization_id', $organization->id)
            ->with('employee')
            ->findOrFail($id);

        return Inertia::render('ZambianHR/ConflictOfInterest/Show', [
            'declaration' => $declaration,
        ]);
    }
}

