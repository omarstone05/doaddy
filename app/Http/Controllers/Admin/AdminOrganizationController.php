<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\AdminActivityLog;
use App\Services\Admin\EmailService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminOrganizationController extends Controller
{
    public function __construct(
        protected EmailService $emailService
    ) {}

    public function index(Request $request)
    {
        $organizations = Organization::query()
            ->with(['users' => fn($q) => $q->limit(5)])
            ->withCount('users')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->plan, function ($query, $plan) {
                $query->where('billing_plan', $plan);
            })
            ->orderBy($request->sort ?? 'created_at', $request->direction ?? 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Organizations/Index', [
            'organizations' => $organizations,
            'filters' => $request->only(['search', 'status', 'plan', 'sort', 'direction']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Organizations/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:organizations,slug',
            'business_type' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'tone_preference' => 'nullable|in:professional,casual,motivational,sassy,technical,formal,conversational,friendly',
            'currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,suspended,trial,cancelled',
            'billing_plan' => 'nullable|in:free,starter,professional,enterprise',
            'mrr' => 'nullable|numeric|min:0',
            'trial_ends_at' => 'nullable|date',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug']) && !empty($validated['name'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
            // Ensure uniqueness
            $baseSlug = $validated['slug'];
            $counter = 1;
            while (Organization::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $baseSlug . '-' . $counter;
                $counter++;
            }
        }

        // Set defaults
        $validated['tone_preference'] = $validated['tone_preference'] ?? 'professional';
        $validated['currency'] = $validated['currency'] ?? 'ZMW';
        $validated['timezone'] = $validated['timezone'] ?? 'Africa/Lusaka';
        $validated['status'] = $validated['status'] ?? 'trial';

        $organization = Organization::create($validated);

        AdminActivityLog::log('created', $organization, null, $validated);

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organization created successfully');
    }

    public function show(Organization $organization)
    {
        $organization->load([
            'users',
            'supportTickets' => fn($q) => $q->latest()->limit(10),
        ]);

        return Inertia::render('Admin/Organizations/Show', [
            'organization' => $organization,
            'stats' => [
                'users_count' => $organization->users()->count(),
                'support_tickets' => $organization->supportTickets()->count(),
                'addy_insights' => \App\Models\AddyInsight::where('organization_id', $organization->id)->count(),
            ],
        ]);
    }

    public function update(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,suspended,trial,cancelled',
            'billing_plan' => 'sometimes|nullable|in:free,starter,professional,enterprise',
            'tone_preference' => 'sometimes|nullable|in:professional,casual,motivational,sassy,technical,formal,conversational,friendly',
            'mrr' => 'sometimes|numeric|min:0',
            'trial_ends_at' => 'sometimes|nullable|date',
        ]);

        $oldValues = $organization->only(array_keys($validated));

        $organization->update($validated);

        AdminActivityLog::log('updated', $organization, $oldValues, $validated);

        return back()->with('success', 'Organization updated successfully');
    }

    public function suspend(Request $request, Organization $organization)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $organization->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $request->reason,
        ]);

        AdminActivityLog::log('suspended', $organization, null, [
            'reason' => $request->reason,
        ]);

        // Send notification email
        $this->emailService->sendSuspensionNotification($organization, $request->reason);

        return back()->with('success', 'Organization suspended successfully');
    }

    public function unsuspend(Organization $organization)
    {
        $organization->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        AdminActivityLog::log('unsuspended', $organization);

        return back()->with('success', 'Organization reactivated successfully');
    }

    public function destroy(Organization $organization)
    {
        AdminActivityLog::log('deleted', $organization);

        $organization->delete();

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organization deleted successfully');
    }
}
