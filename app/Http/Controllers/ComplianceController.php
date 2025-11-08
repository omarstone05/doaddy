<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\License;
use App\Models\ActivityLog;
use App\Models\AddyInsight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;

class ComplianceController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        // Calculate stats
        $totalDocuments = Document::where('organization_id', $organizationId)->count();
        
        $activeLicenses = License::where('organization_id', $organizationId)
            ->where('expiry_date', '>', Carbon::now())
            ->count();
        
        $expiringSoon = License::where('organization_id', $organizationId)
            ->where('expiry_date', '>', Carbon::now())
            ->where('expiry_date', '<=', Carbon::now()->addDays(30))
            ->count();
        
        $auditLogs = ActivityLog::where('organization_id', $organizationId)
            ->count();
        
        // Get Compliance-specific insights (using cross-section insights that relate to compliance)
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

        return Inertia::render('Compliance/Index', [
            'stats' => [
                'total_documents' => $totalDocuments,
                'active_licenses' => $activeLicenses,
                'expiring_soon' => $expiringSoon,
                'audit_logs' => $auditLogs,
            ],
            'insights' => $insights,
        ]);
    }
}

