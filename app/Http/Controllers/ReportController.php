<?php

namespace App\Http\Controllers;

use App\Modules\Retail\Models\Sale;
use App\Models\MoneyMovement;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\GoodsAndService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Reports/Index');
    }

    public function sales(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now()->toDateString());

        $query = Sale::where('organization_id', $organizationId)
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);

        // Group by product
        $salesByProduct = DB::table('sales')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->join('goods_and_services', 'sale_items.goods_service_id', '=', 'goods_and_services.id')
            ->where('sales.organization_id', $organizationId)
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
            ->select(
                'goods_and_services.name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total) as total_revenue')
            )
            ->groupBy('goods_and_services.id', 'goods_and_services.name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // Group by customer
        $salesByCustomer = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.organization_id', $organizationId)
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
            ->select(
                'customers.name',
                DB::raw('COUNT(sales.id) as total_sales'),
                DB::raw('SUM(sales.total_amount) as total_revenue')
            )
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // Daily sales
        $dailySales = DB::table('sales')
            ->where('organization_id', $organizationId)
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->select(
                DB::raw('DATE(sale_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalSales = $query->count();
        $totalRevenue = $query->sum('total_amount');
        $averageSale = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        return Inertia::render('Reports/Sales', [
            'totalSales' => $totalSales,
            'totalRevenue' => $totalRevenue,
            'averageSale' => $averageSale,
            'salesByProduct' => $salesByProduct,
            'salesByCustomer' => $salesByCustomer,
            'dailySales' => $dailySales,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function revenue(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now()->toDateString());

        // Revenue from sales
        $salesRevenue = Sale::where('organization_id', $organizationId)
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->sum('total_amount');

        // Revenue from payments
        $paymentsRevenue = Payment::where('organization_id', $organizationId)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->sum('amount');

        // Revenue from money movements (income)
        $incomeMovements = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'income')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->sum('amount');

        // Revenue breakdown by source
        $revenueBySource = [
            ['source' => 'Sales', 'amount' => $salesRevenue],
            ['source' => 'Payments', 'amount' => $paymentsRevenue],
            ['source' => 'Other Income', 'amount' => $incomeMovements - $paymentsRevenue],
        ];

        // Daily revenue
        $dailyRevenue = DB::table('sales')
            ->where('organization_id', $organizationId)
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->select(
                DB::raw('DATE(sale_date) as date'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return Inertia::render('Reports/Revenue', [
            'totalRevenue' => $salesRevenue + $paymentsRevenue + ($incomeMovements - $paymentsRevenue),
            'salesRevenue' => $salesRevenue,
            'paymentsRevenue' => $paymentsRevenue,
            'otherIncome' => $incomeMovements - $paymentsRevenue,
            'revenueBySource' => $revenueBySource,
            'dailyRevenue' => $dailyRevenue,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function expenses(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now()->toDateString());

        $expenses = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->get();

        $totalExpenses = $expenses->sum('amount');

        // Expenses by category
        $expensesByCategory = DB::table('money_movements')
            ->where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->select(
                'category',
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        // Daily expenses
        $dailyExpenses = DB::table('money_movements')
            ->where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return Inertia::render('Reports/Expenses', [
            'totalExpenses' => $totalExpenses,
            'expensesByCategory' => $expensesByCategory,
            'dailyExpenses' => $dailyExpenses,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function profitLoss(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now()->toDateString());

        // Revenue
        $revenue = Sale::where('organization_id', $organizationId)
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->sum('total_amount');

        // Expenses
        $expenses = MoneyMovement::where('organization_id', $organizationId)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->sum('amount');

        // Profit
        $profit = $revenue - $expenses;
        $profitMargin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        return Inertia::render('Reports/ProfitLoss', [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'profit' => $profit,
            'profitMargin' => $profitMargin,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }
}

