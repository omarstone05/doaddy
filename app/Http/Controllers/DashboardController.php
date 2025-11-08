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
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        // Get all available cards
        $availableCards = DashboardCard::where('is_active', true)->get();
        
        // Get organization's configured cards
        $orgCards = OrgDashboardCard::where('organization_id', $organizationId)
            ->where('is_visible', true)
            ->with('dashboardCard')
            ->orderBy('display_order')
            ->get();
        
        // Calculate quick stats
        $totalAccounts = MoneyAccount::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->count();
        
        $totalRevenue = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->sum('amount');
        
        $totalExpenses = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->sum('amount');
        
        $netBalance = $totalRevenue - $totalExpenses;
        
        // Get data for charts
        $thisMonthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        
        // Revenue trends (last 7 days)
        $revenueTrend = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Expense trends (last 7 days)
        $expenseTrend = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // This month vs last month comparison
        $thisMonthRevenue = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->whereBetween('created_at', [$thisMonthStart, Carbon::now()])
            ->sum('amount');
        
        $lastMonthRevenue = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount');
        
        $thisMonthExpenses = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('created_at', [$thisMonthStart, Carbon::now()])
            ->sum('amount');
        
        $lastMonthExpenses = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount');
        
        // Top products (last 30 days)
        $topProducts = DB::table('sales')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->join('goods_and_services', 'sale_items.goods_service_id', '=', 'goods_and_services.id')
            ->where('sales.organization_id', $organizationId)
            ->where('sales.created_at', '>=', Carbon::now()->subDays(30))
            ->select(
                'goods_and_services.name',
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.total) as revenue')
            )
            ->groupBy('goods_and_services.id', 'goods_and_services.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();
        
        // Top customers (last 30 days)
        $topCustomers = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.organization_id', $organizationId)
            ->where('sales.created_at', '>=', Carbon::now()->subDays(30))
            ->select(
                'customers.name',
                DB::raw('COUNT(sales.id) as sales_count'),
                DB::raw('SUM(sales.total_amount) as revenue')
            )
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();
        
        // Recent sales
        $recentSales = Sale::where('organization_id', $organizationId)
            ->with(['customer', 'cashier'])
            ->latest()
            ->limit(5)
            ->get();
        
        // Pending invoices
        $pendingInvoices = Invoice::where('organization_id', $organizationId)
            ->where('status', '!=', 'paid')
            ->whereRaw('total_amount > paid_amount')
            ->with('customer')
            ->latest()
            ->limit(5)
            ->get();
        
        // Low stock products
        $lowStockProducts = GoodsAndService::where('organization_id', $organizationId)
            ->where('type', 'product')
            ->where('track_stock', true)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->orderBy('current_stock')
            ->limit(5)
            ->get();
        
        return Inertia::render('Dashboard', [
            'user' => $request->user(),
            'availableCards' => $availableCards,
            'orgCards' => $orgCards,
            'stats' => [
                'total_accounts' => $totalAccounts,
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_balance' => $netBalance,
                'this_month_revenue' => $thisMonthRevenue,
                'last_month_revenue' => $lastMonthRevenue,
                'this_month_expenses' => $thisMonthExpenses,
                'last_month_expenses' => $lastMonthExpenses,
                'revenue_trend' => $revenueTrend,
                'expense_trend' => $expenseTrend,
                'top_products' => $topProducts,
                'top_customers' => $topCustomers,
                'recent_sales' => $recentSales,
                'pending_invoices' => $pendingInvoices,
                'low_stock_products' => $lowStockProducts,
            ],
        ]);
    }
}
