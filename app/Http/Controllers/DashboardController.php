<?php

namespace App\Http\Controllers;

use App\Models\DashboardCard;
use App\Models\OrgDashboardCard;
use App\Models\MoneyAccount;
use App\Models\MoneyMovement;
use App\Modules\Retail\Models\Sale;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\GoodsAndService;
use App\Services\Dashboard\CardRegistry;
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
        // Check for new dashboard feature flag
        if (config('features.new_dashboard', false)) {
            return $this->renderNewDashboard($request);
        }
        
        $user = Auth::user();
        
        // Check if user needs onboarding
        if ($user && $user->organization) {
            $org = $user->organization;
            // If organization is missing key onboarding fields, redirect to Penda Cloud onboarding
            if (!$org->industry || !$org->currency || !$org->tone_preference) {
                $pendaCloudUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
                return redirect($pendaCloudUrl . '/onboarding/step-1');
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
        
        // Budget data - handle missing columns gracefully (optimized to avoid N+1)
        try {
            $budgetLines = \App\Models\BudgetLine::where('organization_id', $organizationId)->get();
            
            // Get all spent amounts in a single query (optimized to avoid N+1)
            if ($budgetLines->isNotEmpty()) {
                $budgetIds = $budgetLines->pluck('id');
                $spentAmounts = \App\Models\MoneyMovement::where('organization_id', $organizationId)
                    ->whereIn('budget_line_id', $budgetIds)
                    ->where('flow_type', 'expense')
                    ->where('status', 'approved')
                    ->whereBetween('transaction_date', [$dateRange['start'], $dateRange['end']])
                    ->selectRaw('budget_line_id, SUM(amount) as total_spent')
                    ->groupBy('budget_line_id')
                    ->pluck('total_spent', 'budget_line_id');
            } else {
                $spentAmounts = collect([]);
            }
            
            $budgets = $budgetLines->map(function($budget) use ($spentAmounts) {
                return [
                    'name' => $budget->name,
                    'budget' => $budget->amount,
                    'spent' => $spentAmounts->get($budget->id, 0),
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
        
        // Get available modular cards from CardRegistry
        $modularCards = CardRegistry::getAllCards();
        
        // Ensure cards are properly formatted as array
        $modularCards = array_values($modularCards); // Re-index array
        
        // Preload card data for common cards (with Redis caching for instant loading)
        $cardDataController = new \App\Http\Controllers\DashboardCardDataController();
        $preloadedCardData = [];
        
        // Preload data for common cards that are likely to be displayed
        $commonCardIds = ['finance.revenue', 'finance.expenses', 'finance.profit', 'finance.cash_flow'];
        foreach ($commonCardIds as $cardId) {
            try {
                $cardData = $cardDataController->getCardDataDirect($organizationId, $cardId);
                if (!isset($cardData['error'])) {
                    $preloadedCardData[$cardId] = $cardData;
                }
            } catch (\Exception $e) {
                // Silently fail - cards will fetch their own data
                \Log::debug('Failed to preload card data', ['card' => $cardId, 'error' => $e->getMessage()]);
            }
        }
        
        return Inertia::render('Dashboard', [
            'user' => $request->user(),
            'preloadedCardData' => $preloadedCardData,
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
            'modularCards' => array_values($modularCards), // Available modular cards
        ]);
    }

    /**
     * Render the new modern dashboard with gamification
     */
    protected function renderNewDashboard(Request $request)
    {
        $user = Auth::user();
        
        // Check if user needs onboarding
        if ($user && $user->organization) {
            $org = $user->organization;
            if (!$org->industry || !$org->currency || !$org->tone_preference) {
                $pendaCloudUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
                return redirect($pendaCloudUrl . '/onboarding/step-1');
            }
        }
        
        $organizationId = $user->organization_id;
        
        // Get period from request (week, month, year)
        $period = $request->get('period', 'month');
        
        // Calculate date ranges based on period
        $now = Carbon::now();
        switch ($period) {
            case 'week':
                $currentStart = $now->copy()->startOfWeek();
                $currentEnd = $now->copy()->endOfWeek();
                $previousStart = $now->copy()->subWeek()->startOfWeek();
                $previousEnd = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'year':
                $currentStart = $now->copy()->startOfYear();
                $currentEnd = $now->copy()->endOfYear();
                $previousStart = $now->copy()->subYear()->startOfYear();
                $previousEnd = $now->copy()->subYear()->endOfYear();
                break;
            case 'month':
            default:
                $currentStart = $now->copy()->startOfMonth();
                $currentEnd = $now->copy()->endOfMonth();
                $previousStart = $now->copy()->subMonth()->startOfMonth();
                $previousEnd = $now->copy()->subMonth()->endOfMonth();
                break;
        }
        
        // Current period stats
        $revenue = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$currentStart, $currentEnd])
            ->sum('amount');
        
        $expenses = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$currentStart, $currentEnd])
            ->sum('amount');
        
        $profit = $revenue - $expenses;
        $profitMargin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;
        
        // Previous period for comparison
        $prevRevenue = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$previousStart, $previousEnd])
            ->sum('amount');
        
        $prevExpenses = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$previousStart, $previousEnd])
            ->sum('amount');
        
        $revenueChange = $prevRevenue > 0 
            ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1) 
            : 0;
        
        $expenseChange = $prevExpenses > 0 
            ? round((($expenses - $prevExpenses) / $prevExpenses) * 100, 1) 
            : 0;
        
        $prevProfit = $prevRevenue - $prevExpenses;
        $profitChange = $prevProfit > 0 
            ? round((($profit - $prevProfit) / abs($prevProfit)) * 100, 1) 
            : 0;
        
        // Outstanding invoices
        $outstanding = Invoice::where('organization_id', $organizationId)
            ->where('status', '!=', 'paid')
            ->sum(DB::raw('total_amount - COALESCE(paid_amount, 0)'));
        
        $pendingInvoicesCount = Invoice::where('organization_id', $organizationId)
            ->where('status', '!=', 'paid')
            ->count();
        
        // Cash flow data based on period
        $cashFlowData = [];
        
        switch ($period) {
            case 'week':
                // Last 7 days
                for ($i = 6; $i >= 0; $i--) {
                    $day = Carbon::now()->subDays($i);
                    $dayRevenue = MoneyMovement::where('organization_id', $organizationId)
                        ->where('flow_type', 'income')
                        ->where('status', 'approved')
                        ->whereDate('transaction_date', $day->toDateString())
                        ->sum('amount');
                    
                    $dayExpenses = MoneyMovement::where('organization_id', $organizationId)
                        ->where('flow_type', 'expense')
                        ->where('status', 'approved')
                        ->whereDate('transaction_date', $day->toDateString())
                        ->sum('amount');
                    
                    $cashFlowData[] = [
                        'month' => $day->format('D'),
                        'revenue' => round($dayRevenue / 1000, 1),
                        'expenses' => round($dayExpenses / 1000, 1),
                    ];
                }
                break;
                
            case 'year':
                // Last 12 months
                for ($i = 11; $i >= 0; $i--) {
                    $month = Carbon::now()->subMonths($i);
                    $monthRevenue = MoneyMovement::where('organization_id', $organizationId)
                        ->where('flow_type', 'income')
                        ->where('status', 'approved')
                        ->whereMonth('transaction_date', $month->month)
                        ->whereYear('transaction_date', $month->year)
                        ->sum('amount');
                    
                    $monthExpenses = MoneyMovement::where('organization_id', $organizationId)
                        ->where('flow_type', 'expense')
                        ->where('status', 'approved')
                        ->whereMonth('transaction_date', $month->month)
                        ->whereYear('transaction_date', $month->year)
                        ->sum('amount');
                    
                    $cashFlowData[] = [
                        'month' => $month->format('M'),
                        'revenue' => round($monthRevenue / 1000, 1),
                        'expenses' => round($monthExpenses / 1000, 1),
                    ];
                }
                break;
                
            case 'month':
            default:
                // Last 4 weeks
                $weekLabels = ['W1', 'W2', 'W3', 'Now'];
                for ($i = 3; $i >= 0; $i--) {
                    $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
                    $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();
                    
                    $weekRevenue = MoneyMovement::where('organization_id', $organizationId)
                        ->where('flow_type', 'income')
                        ->where('status', 'approved')
                        ->whereBetween('transaction_date', [$weekStart, $weekEnd])
                        ->sum('amount');
                    
                    $weekExpenses = MoneyMovement::where('organization_id', $organizationId)
                        ->where('flow_type', 'expense')
                        ->where('status', 'approved')
                        ->whereBetween('transaction_date', [$weekStart, $weekEnd])
                        ->sum('amount');
                    
                    $cashFlowData[] = [
                        'month' => $weekLabels[3 - $i],
                        'revenue' => round($weekRevenue / 1000, 1),
                        'expenses' => round($weekExpenses / 1000, 1),
                    ];
                }
                break;
        }
        
        // Recent sales
        try {
            $recentSales = Sale::where('organization_id', $organizationId)
                ->with('customer')
                ->latest()
                ->limit(5)
                ->get()
                ->map(function($sale) {
                    return [
                        'id' => $sale->id,
                        'sale_number' => $sale->sale_number,
                        'customer_name' => $sale->customer->name ?? $sale->customer_name ?? 'Walk-in',
                        'total_amount' => (float) $sale->total_amount,
                        'created_at' => $sale->created_at->toISOString(),
                    ];
                });
        } catch (\Exception $e) {
            $recentSales = collect([]);
        }
        
        // Pending invoices with deadlines
        try {
            $pendingInvoices = Invoice::where('organization_id', $organizationId)
                ->where('status', '!=', 'paid')
                ->with('customer')
                ->orderBy('due_date')
                ->limit(5)
                ->get()
                ->map(function($invoice) {
                    $status = $invoice->status;
                    if ($invoice->due_date && Carbon::parse($invoice->due_date)->isPast()) {
                        $status = 'overdue';
                    }
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'customer_name' => $invoice->customer->name ?? 'N/A',
                        'total_amount' => (float) $invoice->total_amount,
                        'due_date' => $invoice->due_date,
                        'status' => $status,
                    ];
                });
        } catch (\Exception $e) {
            $pendingInvoices = collect([]);
        }
        
        // Get gamification stats if enabled
        $gamification = [];
        if (config('features.gamification', true)) {
            try {
                $gamificationService = app(\App\Services\Addy\GamificationService::class);
                $gamification = $gamificationService->getUserStats($user->id);
            } catch (\Exception $e) {
                \Log::warning('Failed to load gamification stats', ['error' => $e->getMessage()]);
                $gamification = [
                    'total_xp' => 0,
                    'level' => 1,
                    'level_title' => 'Emerging Business',
                    'xp_for_next_level' => 100,
                    'xp_progress_percentage' => 0,
                    'current_streak' => 0,
                    'longest_streak' => 0,
                    'badges' => [],
                    'recent_badges' => [],
                ];
            }
        }
        
        return Inertia::render('Addy/Dashboard', [
            'stats' => [
                'revenue' => (float) $revenue,
                'expenses' => (float) $expenses,
                'profit' => (float) $profit,
                'profitMargin' => $profitMargin,
                'revenueChange' => $revenueChange,
                'expenseChange' => $expenseChange,
                'profitChange' => $profitChange,
                'outstanding' => (float) $outstanding,
                'pendingInvoicesCount' => $pendingInvoicesCount,
            ],
            'recentSales' => $recentSales,
            'cashFlowData' => $cashFlowData,
            'pendingInvoices' => $pendingInvoices,
            'gamification' => $gamification,
            'period' => $period,
        ]);
    }
}
