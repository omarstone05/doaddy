<?php

namespace App\Http\Controllers;

use App\Models\DashboardCard;
use App\Models\OrgDashboardCard;
use App\Models\MoneyAccount;
use App\Models\MoneyMovement;
use App\Models\Sale;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\GoodsAndService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected function getDateRange($timeframe = 'today')
    {
        $now = Carbon::now();
        
        return match($timeframe) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'yesterday' => [
                'start' => $now->copy()->subDay()->startOfDay(),
                'end' => $now->copy()->subDay()->endOfDay(),
            ],
            'this_week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'last_week' => [
                'start' => $now->copy()->subWeek()->startOfWeek(),
                'end' => $now->copy()->subWeek()->endOfWeek(),
            ],
            'this_month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'last_month' => [
                'start' => $now->copy()->subMonth()->startOfMonth(),
                'end' => $now->copy()->subMonth()->endOfMonth(),
            ],
            'this_quarter' => [
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
            ],
            'last_quarter' => [
                'start' => $now->copy()->subQuarter()->startOfQuarter(),
                'end' => $now->copy()->subQuarter()->endOfQuarter(),
            ],
            'this_year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            'last_year' => [
                'start' => $now->copy()->subYear()->startOfYear(),
                'end' => $now->copy()->subYear()->endOfYear(),
            ],
            default => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
        };
    }

    protected function getPreviousRange($timeframe)
    {
        $now = Carbon::now();
        
        return match($timeframe) {
            'today' => [
                'start' => $now->copy()->subDay()->startOfDay(),
                'end' => $now->copy()->subDay()->endOfDay(),
            ],
            'yesterday' => [
                'start' => $now->copy()->subDays(2)->startOfDay(),
                'end' => $now->copy()->subDays(2)->endOfDay(),
            ],
            'this_week' => [
                'start' => $now->copy()->subWeek()->startOfWeek(),
                'end' => $now->copy()->subWeek()->endOfWeek(),
            ],
            'last_week' => [
                'start' => $now->copy()->subWeeks(2)->startOfWeek(),
                'end' => $now->copy()->subWeeks(2)->endOfWeek(),
            ],
            'this_month' => [
                'start' => $now->copy()->subMonth()->startOfMonth(),
                'end' => $now->copy()->subMonth()->endOfMonth(),
            ],
            'last_month' => [
                'start' => $now->copy()->subMonths(2)->startOfMonth(),
                'end' => $now->copy()->subMonths(2)->endOfMonth(),
            ],
            'this_quarter' => [
                'start' => $now->copy()->subQuarter()->startOfQuarter(),
                'end' => $now->copy()->subQuarter()->endOfQuarter(),
            ],
            'last_quarter' => [
                'start' => $now->copy()->subQuarters(2)->startOfQuarter(),
                'end' => $now->copy()->subQuarters(2)->endOfQuarter(),
            ],
            'this_year' => [
                'start' => $now->copy()->subYear()->startOfYear(),
                'end' => $now->copy()->subYear()->endOfYear(),
            ],
            'last_year' => [
                'start' => $now->copy()->subYears(2)->startOfYear(),
                'end' => $now->copy()->subYears(2)->endOfYear(),
            ],
            default => [
                'start' => $now->copy()->subDay()->startOfDay(),
                'end' => $now->copy()->subDay()->endOfDay(),
            ],
        };
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Check if user needs onboarding
        if ($user && $user->organization) {
            $org = $user->organization;
            // If organization is missing key onboarding fields, redirect to onboarding
            if (!$org->industry || !$org->currency || !$org->tone_preference) {
                return redirect()->route('onboarding');
            }
        }
        
        $organizationId = $user->organization_id;
        $timeframe = $request->get('timeframe', 'today');
        $dateRange = $this->getDateRange($timeframe);
        $previousRange = $this->getPreviousRange($timeframe);
        
        // Get all available cards - handle missing tables gracefully
        try {
            $availableCards = DashboardCard::where('is_active', true)->get();
        } catch (\Exception $e) {
            \Log::warning('DashboardCard query failed - table may not exist', ['error' => $e->getMessage()]);
            $availableCards = collect([]);
        }
        
        // Get organization's configured cards with layout - handle missing tables gracefully
        try {
            $orgCards = OrgDashboardCard::where('organization_id', $organizationId)
                ->where('is_visible', true)
                ->with('dashboardCard')
                ->orderBy('display_order')
                ->get();
        } catch (\Exception $e) {
            \Log::warning('OrgDashboardCard query failed - table may not exist', ['error' => $e->getMessage()]);
            $orgCards = collect([]);
        }
        
        // Calculate quick stats for the timeframe
        $totalAccounts = MoneyAccount::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->count();
        
        $totalRevenue = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$dateRange['start'], $dateRange['end']])
            ->sum('amount');
        
        $totalExpenses = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$dateRange['start'], $dateRange['end']])
            ->sum('amount');
        
        $netBalance = $totalRevenue - $totalExpenses;
        
        // Get data for charts - use timeframe
        $revenueTrend = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$dateRange['start'], $dateRange['end']])
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(amount) as amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        $expenseTrend = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$dateRange['start'], $dateRange['end']])
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(amount) as amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Comparison data (current vs previous period)
        $previousRevenue = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$previousRange['start'], $previousRange['end']])
            ->sum('amount');
        
        $previousExpenses = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$previousRange['start'], $previousRange['end']])
            ->sum('amount');
        
        // Top products (for the timeframe) - handle missing tables gracefully
        try {
            $topProducts = DB::table('sales')
                ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                ->join('goods_and_services', 'sale_items.goods_service_id', '=', 'goods_and_services.id')
                ->where('sales.organization_id', $organizationId)
                ->whereBetween('sales.created_at', [$dateRange['start'], $dateRange['end']])
                ->select(
                    'goods_and_services.name',
                    DB::raw('SUM(sale_items.quantity) as quantity'),
                    DB::raw('SUM(sale_items.total) as revenue')
                )
                ->groupBy('goods_and_services.id', 'goods_and_services.name')
                ->orderByDesc('revenue')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            \Log::warning('Top products query failed - tables may not exist', ['error' => $e->getMessage()]);
            $topProducts = collect([]);
        }
        
        // Top customers (for the timeframe) - handle missing tables gracefully
        try {
            $topCustomers = DB::table('sales')
                ->join('customers', 'sales.customer_id', '=', 'customers.id')
                ->where('sales.organization_id', $organizationId)
                ->whereBetween('sales.created_at', [$dateRange['start'], $dateRange['end']])
                ->select(
                    'customers.name',
                    DB::raw('COUNT(sales.id) as sales_count'),
                    DB::raw('SUM(sales.total_amount) as revenue')
                )
                ->groupBy('customers.id', 'customers.name')
                ->orderByDesc('revenue')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            \Log::warning('Top customers query failed - tables may not exist', ['error' => $e->getMessage()]);
            $topCustomers = collect([]);
        }
        
        // Recent sales - handle missing tables gracefully
        try {
            $recentSales = Sale::where('organization_id', $organizationId)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->with(['customer', 'cashier'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(function($sale) {
                    return [
                        'id' => $sale->id,
                        'customer_name' => $sale->customer->name ?? 'Walk-in',
                        'total' => (float) $sale->total_amount,
                        'date' => $sale->created_at->format('M d, Y'),
                    ];
                });
        } catch (\Exception $e) {
            \Log::warning('Recent sales query failed - tables may not exist', ['error' => $e->getMessage()]);
            $recentSales = collect([]);
        }
        
        // Pending invoices - handle missing tables gracefully
        try {
            $pendingInvoices = Invoice::where('organization_id', $organizationId)
                ->where('status', '!=', 'paid')
                ->whereRaw('total_amount > paid_amount')
                ->with('customer')
                ->latest()
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            \Log::warning('Pending invoices query failed - table may not exist', ['error' => $e->getMessage()]);
            $pendingInvoices = collect([]);
        }
        
        // Low stock products - handle missing tables gracefully
        try {
            $lowStockProducts = GoodsAndService::where('organization_id', $organizationId)
                ->where('type', 'product')
                ->where('track_stock', true)
                ->whereColumn('current_stock', '<=', 'minimum_stock')
                ->orderBy('current_stock')
                ->limit(5)
                ->get();
            
            // Get total products count
            $totalProducts = GoodsAndService::where('organization_id', $organizationId)
                ->where('type', 'product')
                ->count();
        } catch (\Exception $e) {
            \Log::warning('Low stock products query failed - tables may not exist', ['error' => $e->getMessage()]);
            $lowStockProducts = collect([]);
            $totalProducts = 0;
        }
        
        // Budget data - handle missing columns gracefully
        try {
            $budgets = \App\Models\BudgetLine::where('organization_id', $organizationId)
                ->get()
                ->map(function($budget) use ($dateRange) {
                    $spent = \App\Models\MoneyMovement::where('organization_id', $budget->organization_id)
                        ->where('budget_line_id', $budget->id)
                        ->where('flow_type', 'expense')
                        ->where('status', 'approved')
                        ->whereBetween('transaction_date', [$dateRange['start'], $dateRange['end']])
                        ->sum('amount');
                    return [
                        'name' => $budget->name,
                        'budget' => $budget->amount,
                        'spent' => $spent,
                    ];
                });
        } catch (\Exception $e) {
            \Log::warning('Budget query failed - table/column may not exist', ['error' => $e->getMessage()]);
            $budgets = collect([]);
        }
        
        // Revenue by category (simplified - can be enhanced)
        $revenueByCategory = [
            ['name' => 'Products', 'value' => $totalRevenue * 0.6],
            ['name' => 'Services', 'value' => $totalRevenue * 0.3],
            ['name' => 'Other', 'value' => $totalRevenue * 0.1],
        ];
        
        // Expense breakdown (simplified - can be enhanced)
        $expenseBreakdown = [
            ['name' => 'Operations', 'amount' => $totalExpenses * 0.4],
            ['name' => 'Marketing', 'amount' => $totalExpenses * 0.3],
            ['name' => 'Salaries', 'amount' => $totalExpenses * 0.2],
            ['name' => 'Other', 'amount' => $totalExpenses * 0.1],
        ];
        
        // Customer growth data (last 6 months) - handle missing tables gracefully
        try {
            $customerGrowth = [];
            for ($i = 5; $i >= 0; $i--) {
                $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
                $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
                $newCustomers = \App\Models\Customer::where('organization_id', $organizationId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();
                $customerGrowth[] = [
                    'name' => $monthStart->format('M'),
                    'value' => $newCustomers,
                ];
            }
            
            // Total customers
            $totalCustomers = \App\Models\Customer::where('organization_id', $organizationId)->count();
            $previousMonthCustomers = \App\Models\Customer::where('organization_id', $organizationId)
                ->where('created_at', '<', Carbon::now()->subMonth()->startOfMonth())
                ->count();
            $customerGrowthRate = $previousMonthCustomers > 0 
                ? round((($totalCustomers - $previousMonthCustomers) / $previousMonthCustomers) * 100, 1)
                : 0;
        } catch (\Exception $e) {
            \Log::warning('Customer growth query failed - table may not exist', ['error' => $e->getMessage()]);
            $customerGrowth = [];
            $totalCustomers = 0;
            $customerGrowthRate = 0;
        }
        
        // Projects data - handle missing tables gracefully
        try {
            $projects = \App\Models\Project::where('organization_id', $organizationId)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'status' => $project->status ?? 'in_progress',
                    ];
                });
        } catch (\Exception $e) {
            \Log::warning('Projects query failed - table may not exist', ['error' => $e->getMessage()]);
            $projects = collect([]);
        }
        
        // Team stats (simplified) - handle missing tables gracefully
        try {
            $teamStats = [
                'totalMembers' => \App\Models\TeamMember::where('organization_id', $organizationId)->count(),
                'goalsCompleted' => 0, // Can be enhanced with OKR data
                'avgPerformance' => 85, // Placeholder
                'topPerformers' => [], // Can be enhanced
            ];
        } catch (\Exception $e) {
            \Log::warning('Team stats query failed - table may not exist', ['error' => $e->getMessage()]);
            $teamStats = [
                'totalMembers' => 0,
                'goalsCompleted' => 0,
                'avgPerformance' => 0,
                'topPerformers' => [],
            ];
        }
        
        return Inertia::render('Dashboard', [
            'user' => $request->user(),
            'stats' => [
                'total_accounts' => $totalAccounts,
                'total_revenue' => (float) $totalRevenue,
                'total_expenses' => (float) $totalExpenses,
                'net_balance' => (float) $netBalance,
                'previous_revenue' => (float) $previousRevenue,
                'previous_expenses' => (float) $previousExpenses,
                'revenue_trend' => $revenueTrend,
                'expense_trend' => $expenseTrend,
                'top_products' => $topProducts,
                'top_customers' => $topCustomers,
                'recent_sales' => $recentSales,
                'pending_invoices' => $pendingInvoices->map(function($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'customer_name' => $invoice->customer->name ?? 'N/A',
                        'total_amount' => (float) $invoice->total_amount,
                        'due_date' => $invoice->due_date?->format('M d, Y'),
                    ];
                }),
                'low_stock_products' => $lowStockProducts,
                'total_products' => $totalProducts ?? 0,
                'budgets' => $budgets,
                'revenue_by_category' => $revenueByCategory,
                'expense_breakdown' => $expenseBreakdown,
                'customer_growth' => $customerGrowth,
                'total_customers' => $totalCustomers,
                'customer_growth_rate' => $customerGrowthRate,
                'projects' => $projects,
                'team_stats' => $teamStats,
            ],
        ]);
    }
}
