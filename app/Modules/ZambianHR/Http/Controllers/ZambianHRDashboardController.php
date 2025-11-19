<?php

namespace App\Modules\ZambianHR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Modules\ZambianHR\Models\FuneralGrant;
use App\Modules\ZambianHR\Models\GratuityCalculation;
use App\Modules\ZambianHR\Models\Grievance;
use App\Modules\ZambianHR\Models\Termination;
use App\Modules\ZambianHR\Models\ContractRenewal;

class ZambianHRDashboardController extends Controller
{
    /**
     * Helper to get the current organization
     */
    protected function getOrganization(): ?\App\Models\Organization
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        // Try to get from session first
        $currentOrgId = session('current_organization_id');
        if ($currentOrgId) {
            $org = $user->organizations()->where('organizations.id', $currentOrgId)->first();
            if ($org) {
                return $org;
            }
        }
        
        // Fallback to user's organization_id attribute (for backward compatibility)
        if ($user->attributes['organization_id'] ?? null) {
            $org = $user->organizations()->where('organizations.id', $user->attributes['organization_id'])->first();
            if ($org) {
                return $org;
            }
        }
        
        // Fallback to the first organization the user belongs to
        return $user->organizations()->first();
    }

    /**
     * Display the Zambian HR dashboard
     */
    public function index()
    {
        $organization = $this->getOrganization();

        if (!$organization) {
            return Inertia::render('Error', ['status' => 403, 'message' => 'You do not belong to any organization.']);
        }
        
        // Get Zambian HR statistics
        $stats = [
            'pending_funeral_grants' => FuneralGrant::where('organization_id', $organization->id)
                ->where('status', 'pending')
                ->count(),
            'pending_gratuity_calculations' => GratuityCalculation::where('organization_id', $organization->id)
                ->where('status', 'calculated')
                ->count(),
            'active_grievances' => Grievance::where('organization_id', $organization->id)
                ->whereIn('status', ['submitted', 'under_investigation', 'pending_resolution'])
                ->count(),
            'pending_terminations' => Termination::where('organization_id', $organization->id)
                ->whereNull('approved_at')
                ->count(),
            'contracts_expiring_soon' => ContractRenewal::where('organization_id', $organization->id)
                ->where('renewal_status', 'pending')
                ->where('renewal_deadline', '<=', now()->addDays(30))
                ->count(),
        ];
        
        return Inertia::render('ZambianHR/Dashboard', [
            'stats' => $stats,
        ]);
    }
}

