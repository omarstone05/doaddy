<?php

namespace App\Modules\ZambianHR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Modules\ZambianHR\Models\FuneralGrant;
use App\Modules\ZambianHR\Models\EmployeeBeneficiary;

class FuneralGrantController extends Controller
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

        $grants = FuneralGrant::where('organization_id', $organization->id)
            ->with(['employee', 'beneficiary'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('ZambianHR/FuneralGrants/Index', [
            'grants' => $grants,
        ]);
    }

    public function create()
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        // Get employees and beneficiaries
        $employees = \App\Modules\HR\Models\Employee::where('organization_id', $organization->id)
            ->where('is_active', true)
            ->get();

        return Inertia::render('ZambianHR/FuneralGrants/Create', [
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
            'employee_id' => 'nullable|uuid|exists:hr_employees,id',
            'deceased_person' => 'required|in:employee,spouse,child,parent',
            'deceased_name' => 'required|string|max:255',
            'relationship_to_employee' => 'required|string|max:255',
            'date_of_death' => 'required|date',
            'death_certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'grant_amount' => 'required|numeric|min:0',
            'calculation_basis' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Handle file upload
            $certificateFile = null;
            if ($request->hasFile('death_certificate_file')) {
                $file = $request->file('death_certificate_file');
                $certificateFile = $file->store('funeral-grants', 'public');
            }

            $grant = FuneralGrant::create([
                'id' => (string) Str::uuid(),
                'employee_id' => $validated['employee_id'] ?? null,
                'organization_id' => $organization->id,
                'deceased_person' => $validated['deceased_person'],
                'deceased_name' => $validated['deceased_name'],
                'relationship_to_employee' => $validated['relationship_to_employee'],
                'date_of_death' => $validated['date_of_death'],
                'death_certificate_file' => $certificateFile,
                'grant_amount' => $validated['grant_amount'],
                'currency' => 'ZMW',
                'calculation_basis' => $validated['calculation_basis'] ?? null,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return redirect()->route('zambian-hr.funeral-grants.show', $grant->id)
                ->with('message', 'Funeral grant created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create funeral grant: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $grant = FuneralGrant::where('organization_id', $organization->id)
            ->with(['employee', 'beneficiary'])
            ->findOrFail($id);

        return Inertia::render('ZambianHR/FuneralGrants/Show', [
            'grant' => $grant,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $grant = FuneralGrant::where('organization_id', $organization->id)
            ->findOrFail($id);

        $grant->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('message', 'Funeral grant approved successfully');
    }

    public function pay(Request $request, $id)
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $validated = $request->validate([
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string',
            'paid_to_beneficiary_id' => 'nullable|uuid',
            'paid_to_name' => 'nullable|string',
        ]);

        $grant = FuneralGrant::where('organization_id', $organization->id)
            ->findOrFail($id);

        $grant->update([
            'status' => 'paid',
            'payment_method' => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'] ?? null,
            'paid_to_beneficiary_id' => $validated['paid_to_beneficiary_id'] ?? null,
            'paid_to_name' => $validated['paid_to_name'] ?? null,
            'paid_at' => now(),
        ]);

        return back()->with('message', 'Funeral grant marked as paid');
    }
}

