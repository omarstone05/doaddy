<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LeadController extends Controller
{
    protected function getOrganizationId(): ?string
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $currentOrgId = session('current_organization_id');
        if ($currentOrgId) {
            $org = $user->organizations()->where('organizations.id', $currentOrgId)->first();
            if ($org) {
                return $org->id;
            }
        }
        
        if ($user->attributes['organization_id'] ?? null) {
            $org = $user->organizations()->where('organizations.id', $user->attributes['organization_id'])->first();
            if ($org) {
                return $org->id;
            }
        }
        
        return $user->organizations()->first()?->id;
    }

    public function index(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            $pendaCloudUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
            return redirect($pendaCloudUrl . '/onboarding/step-1');
        }

        $query = Lead::where('organization_id', $organizationId)
            ->with(['assignedTo']);

        // Filters
        if ($request->has('lead_status') && $request->lead_status !== '') {
            $query->where('lead_status', $request->lead_status);
        }

        if ($request->has('lead_source') && $request->lead_source !== '') {
            $query->where('lead_source', $request->lead_source);
        }

        if ($request->has('rating') && $request->rating !== '') {
            $query->where('rating', $request->rating);
        }

        if ($request->has('assigned_to') && $request->assigned_to !== '') {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $leads = $query->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('CRM/Leads/Index', [
            'leads' => $leads,
            'filters' => $request->only(['lead_status', 'lead_source', 'rating', 'assigned_to', 'search']),
        ]);
    }

    public function create()
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            $pendaCloudUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
            return redirect($pendaCloudUrl . '/onboarding/step-1');
        }

        return Inertia::render('CRM/Leads/Create');
    }

    public function store(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found.']);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'whatsapp_number' => 'nullable|string|max:255',
            'lead_source' => 'required|in:website,referral,social_media,cold_call,event,partner,advertisement,walk_in,whatsapp,other',
            'lead_status' => 'required|in:new,contacted,qualified,unqualified,converted,lost,nurturing',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $lead = Lead::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organizationId,
                ...$validated,
            ]);

            DB::commit();

            return redirect()->route('crm.leads.show', $lead->id)->with('message', 'Lead created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create lead: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            $pendaCloudUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
            return redirect($pendaCloudUrl . '/onboarding/step-1');
        }

        $lead = Lead::where('organization_id', $organizationId)
            ->with(['assignedTo', 'activities', 'convertedToContact', 'convertedToOpportunity'])
            ->findOrFail($id);

        return Inertia::render('CRM/Leads/Show', [
            'lead' => $lead,
        ]);
    }
}


