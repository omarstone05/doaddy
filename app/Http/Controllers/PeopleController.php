<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PayrollRun;
use App\Models\LeaveRequest;
use App\Models\CommissionRule;
use App\Models\AddyInsight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PeopleController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        // Calculate stats
        $totalTeam = User::where('organization_id', $organizationId)->count();
        
        $activePayroll = PayrollRun::where('organization_id', $organizationId)
            ->where('status', 'open')
            ->count();
        
        $pendingLeave = LeaveRequest::where('organization_id', $organizationId)
            ->where('status', 'pending')
            ->count();
        
        $commissionRules = CommissionRule::where('organization_id', $organizationId)->count();
        
        // Get People-specific insights
        $insights = AddyInsight::active($organizationId)
            ->where('category', 'people')
            ->orderBy('priority', 'desc')
            ->limit(3)
            ->get()
            ->map(fn($insight) => [
                'id' => $insight->id,
                'type' => $insight->type,
                'title' => $insight->title,
                'description' => $insight->description,
                'priority' => (float) $insight->priority,
                'is_actionable' => $insight->is_actionable,
                'action_url' => $insight->action_url,
            ]);

        return Inertia::render('People/Index', [
            'stats' => [
                'total_team' => $totalTeam,
                'active_payroll' => $activePayroll,
                'pending_leave' => $pendingLeave,
                'commission_rules' => $commissionRules,
            ],
            'insights' => $insights,
        ]);
    }
}

