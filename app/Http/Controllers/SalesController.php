<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\AddyInsight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        // Calculate stats
        $totalCustomers = Customer::where('organization_id', $organizationId)->count();
        
        $thisMonthStart = Carbon::now()->startOfMonth();
        
        $monthlySales = Sale::where('organization_id', $organizationId)
            ->where('created_at', '>=', $thisMonthStart)
            ->sum('total_amount');
        
        $pendingInvoices = Invoice::where('organization_id', $organizationId)
            ->where('status', '!=', 'paid')
            ->whereRaw('total_amount > paid_amount')
            ->count();
        
        $pendingQuotes = Quote::where('organization_id', $organizationId)
            ->whereIn('status', ['draft', 'sent'])
            ->count();
        
        // Get recent sales
        $recentSales = Sale::where('organization_id', $organizationId)
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                    'total_amount' => $sale->total_amount,
                    'created_at' => $sale->created_at,
                    'customer' => $sale->customer ? [
                        'name' => $sale->customer->name,
                    ] : null,
                ];
            });
        
        // Get Sales-specific insights
        $insights = AddyInsight::active($organizationId)
            ->where('category', 'sales')
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

        return Inertia::render('Sales/Index', [
            'stats' => [
                'total_customers' => $totalCustomers,
                'monthly_sales' => $monthlySales,
                'pending_invoices' => $pendingInvoices,
                'pending_quotes' => $pendingQuotes,
                'recent_sales' => $recentSales,
            ],
            'insights' => $insights,
        ]);
    }
}

