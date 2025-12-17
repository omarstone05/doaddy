<?php

namespace App\Modules\ZambianHR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Modules\ZambianHR\Models\Grievance;
use App\Modules\ZambianHR\Models\GrievanceMeeting;

class GrievanceController extends Controller
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

        $grievances = Grievance::where('organization_id', $organization->id)
            ->with(['employee', 'meetings'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('ZambianHR/Grievances/Index', [
            'grievances' => $grievances,
        ]);
    }

    public function create()
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $employees = \App\Modules\HR\Models\Employee::where('organization_id', $organization->id)
            ->where('is_active', true)
            ->get();

        return Inertia::render('ZambianHR/Grievances/Create', [
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
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'grievance_category' => 'required|in:harassment,discrimination,working_conditions,salary,benefits,management_action,safety,bullying,other',
            'filed_against_employee_id' => 'nullable|uuid|exists:hr_employees,id',
            'filed_against_manager_id' => 'nullable|uuid|exists:hr_employees,id',
            'incident_date' => 'nullable|date',
            'witnesses' => 'nullable|array',
            'supporting_documents' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // Generate grievance number
            $grievanceNumber = 'GRIEV-' . date('Y') . '-' . str_pad(
                Grievance::where('organization_id', $organization->id)
                    ->whereYear('created_at', date('Y'))
                    ->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            $grievance = Grievance::create([
                'id' => (string) Str::uuid(),
                'employee_id' => $validated['employee_id'],
                'organization_id' => $organization->id,
                'grievance_number' => $grievanceNumber,
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'grievance_category' => $validated['grievance_category'],
                'filed_against_employee_id' => $validated['filed_against_employee_id'] ?? null,
                'filed_against_manager_id' => $validated['filed_against_manager_id'] ?? null,
                'incident_date' => $validated['incident_date'] ?? null,
                'filed_date' => now(),
                'witnesses' => $validated['witnesses'] ?? [],
                'supporting_documents' => $validated['supporting_documents'] ?? [],
                'status' => 'submitted',
                'priority' => 'medium',
                'is_confidential' => true,
            ]);

            DB::commit();

            return redirect()->route('zambian-hr.grievances.show', $grievance->id)
                ->with('message', 'Grievance filed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to file grievance: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $grievance = Grievance::where('organization_id', $organization->id)
            ->with(['employee', 'meetings'])
            ->findOrFail($id);

        return Inertia::render('ZambianHR/Grievances/Show', [
            'grievance' => $grievance,
        ]);
    }

    public function storeMeeting(Request $request, $grievanceId)
    {
        $organization = $this->getOrganization();
        if (!$organization) {
            abort(403, 'You must belong to an organization.');
        }

        $grievance = Grievance::where('organization_id', $organization->id)
            ->findOrFail($grievanceId);

        $validated = $request->validate([
            'meeting_type' => 'required|in:initial_hearing,investigation,resolution,appeal',
            'meeting_date' => 'required|date',
            'meeting_time' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:255',
            'attendees' => 'required|array',
            'chairperson_id' => 'nullable|uuid',
            'minutes' => 'nullable|string',
            'decisions_made' => 'nullable|string',
            'action_items' => 'nullable|array',
        ]);

        $meeting = GrievanceMeeting::create([
            'id' => (string) Str::uuid(),
            'grievance_id' => $grievance->id,
            'meeting_type' => $validated['meeting_type'],
            'meeting_date' => $validated['meeting_date'],
            'meeting_time' => $validated['meeting_time'] ?? null,
            'location' => $validated['location'] ?? null,
            'attendees' => $validated['attendees'],
            'chairperson_id' => $validated['chairperson_id'] ?? null,
            'minutes' => $validated['minutes'] ?? null,
            'decisions_made' => $validated['decisions_made'] ?? null,
            'action_items' => $validated['action_items'] ?? [],
        ]);

        return back()->with('message', 'Meeting recorded successfully');
    }
}

