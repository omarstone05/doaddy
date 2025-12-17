<?php

namespace App\Http\Controllers\PrintShop;

use App\Http\Controllers\Controller;
use App\Models\Print\PrintMaterial;
use App\Models\Print\InkConfiguration;
use App\Models\Print\PricingRule;
use App\Models\Print\PrintJob;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrintShopController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = $request->user()->current_organization_id;

        // Get stats
        $stats = [
            'total_materials' => PrintMaterial::where('organization_id', $organizationId)->where('is_active', true)->count(),
            'total_ink_configs' => InkConfiguration::where('organization_id', $organizationId)->count(),
            'total_pricing_rules' => PricingRule::where('organization_id', $organizationId)->where('is_active', true)->count(),
            'pending_jobs' => PrintJob::where('organization_id', $organizationId)
                ->whereIn('status', ['draft', 'quoted', 'approved', 'in_progress'])
                ->count(),
            'completed_jobs' => PrintJob::where('organization_id', $organizationId)
                ->where('status', 'completed')
                ->count(),
            'total_revenue' => PrintJob::where('organization_id', $organizationId)
                ->where('status', 'completed')
                ->get()
                ->sum('grand_total'),
            'total_profit' => PrintJob::where('organization_id', $organizationId)
                ->where('status', 'completed')
                ->get()
                ->sum('total_margin'),
        ];

        // Recent jobs
        $recentJobs = PrintJob::where('organization_id', $organizationId)
            ->with(['customer', 'printMaterial'])
            ->latest()
            ->take(5)
            ->get();

        // Materials for quick calculator
        $materials = PrintMaterial::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->with('inkConfigurations')
            ->get();

        return Inertia::render('PrintShop/Index', [
            'stats' => $stats,
            'recentJobs' => $recentJobs,
            'materials' => $materials,
        ]);
    }
}

