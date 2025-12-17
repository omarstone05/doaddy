<?php

namespace App\Modules\HR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class HRDashboardController extends Controller
{
    /**
     * Display the HR dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get current organization - try multiple methods
        $organization = null;
        $currentOrgId = session('current_organization_id') ?? $user->current_organization_id;
        
        if ($currentOrgId) {
            $organization = $user->organizations()->where('organizations.id', $currentOrgId)->first();
        }
        
        // Fallback to first organization
        if (!$organization) {
            $organization = $user->organizations()->first();
        }
        
        if (!$organization) {
            abort(403, 'You must belong to an organization to access HR features.');
        }
        
        // TODO: Add HR statistics and data
        $stats = [
            'total_employees' => 0,
            'active_employees' => 0,
            'on_leave_today' => 0,
            'pending_leave_requests' => 0,
            'upcoming_reviews' => 0,
            'open_positions' => 0,
        ];
        
        return Inertia::render('HR/Dashboard', [
            'stats' => $stats,
        ]);
    }
}

