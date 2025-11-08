<?php

namespace App\Http\Controllers;

use App\Models\OKR;
use App\Models\Project;
use App\Models\StrategicGoal;
use App\Models\AddyInsight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DecisionsController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        // Calculate stats
        $activeOkrs = OKR::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->count();
        
        $activeProjects = Project::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->count();
        
        $strategicGoals = StrategicGoal::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->count();
        
        // Get Decisions-specific insights (using cross-section insights that relate to decisions)
        $insights = AddyInsight::active($organizationId)
            ->whereIn('category', ['cross-section'])
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

        return Inertia::render('Decisions/Index', [
            'stats' => [
                'active_okrs' => $activeOkrs,
                'active_projects' => $activeProjects,
                'strategic_goals' => $strategicGoals,
                'reports' => 4, // Fixed number of report types
            ],
            'insights' => $insights,
        ]);
    }
}

