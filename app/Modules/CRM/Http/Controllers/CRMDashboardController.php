<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Models\Contact;
use App\Modules\CRM\Models\Opportunity;
use App\Modules\CRM\Models\Activity;
use App\Modules\CRM\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CRMDashboardController extends Controller
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

        // Stats
        $stats = [
            'total_leads' => Lead::where('organization_id', $organizationId)->count(),
            'new_leads' => Lead::where('organization_id', $organizationId)->where('lead_status', 'new')->count(),
            'total_contacts' => Contact::where('organization_id', $organizationId)->count(),
            'total_opportunities' => Opportunity::where('organization_id', $organizationId)
                ->where('is_won', false)
                ->where('is_lost', false)
                ->count(),
            'pipeline_value' => Opportunity::where('organization_id', $organizationId)
                ->where('is_won', false)
                ->where('is_lost', false)
                ->sum('amount'),
            'weighted_pipeline' => Opportunity::where('organization_id', $organizationId)
                ->where('is_won', false)
                ->where('is_lost', false)
                ->sum('expected_revenue'),
            'won_this_month' => Opportunity::where('organization_id', $organizationId)
                ->where('is_won', true)
                ->whereMonth('actual_close_date', now()->month)
                ->whereYear('actual_close_date', now()->year)
                ->sum('amount'),
            'activities_today' => Activity::where('organization_id', $organizationId)
                ->where('activity_date', today())
                ->count(),
            'pending_tasks' => Task::where('organization_id', $organizationId)
                ->where('is_completed', false)
                ->where('due_date', '<=', today())
                ->count(),
        ];

        // Recent activities
        $recentActivities = Activity::where('organization_id', $organizationId)
            ->with(['owner', 'relatedTo'])
            ->orderBy('activity_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Upcoming tasks
        $upcomingTasks = Task::where('organization_id', $organizationId)
            ->where('is_completed', false)
            ->where('due_date', '>=', today())
            ->with(['assignedTo', 'relatedTo'])
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();

        // Top opportunities
        $topOpportunities = Opportunity::where('organization_id', $organizationId)
            ->where('is_won', false)
            ->where('is_lost', false)
            ->with(['contact', 'owner'])
            ->orderBy('expected_revenue', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('CRM/Dashboard', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'upcomingTasks' => $upcomingTasks,
            'topOpportunities' => $topOpportunities,
        ]);
    }
}


