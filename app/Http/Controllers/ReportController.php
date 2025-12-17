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
    /**
     * Get date range based on period
     */
    protected function getDateRange(Request $request): array
    {
        $period = $request->input('period', 'month');
        $now = Carbon::now();
        
        switch ($period) {
            case 'week':
                return [
                    'from' => $now->copy()->startOfWeek()->toDateString(),
                    'to' => $now->copy()->endOfWeek()->toDateString(),
                ];
            case 'year':
                return [
                    'from' => $now->copy()->startOfYear()->toDateString(),
                    'to' => $now->copy()->endOfYear()->toDateString(),
                ];
            case 'custom':
                return [
                    'from' => $request->input('date_from', $now->copy()->startOfMonth()->toDateString()),
                    'to' => $request->input('date_to', $now->toDateString()),
                ];
            case 'month':
            default:
                return [
                    'from' => $now->copy()->startOfMonth()->toDateString(),
                    'to' => $now->copy()->endOfMonth()->toDateString(),
                ];
        }
    }

    public function index()
    {
        return Inertia::render('Reports/Index');
    }

    public function sales(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $period = $request->input('period', 'month');
        $dateRange = $this->getDateRange($request);
        $dateFrom = $dateRange['from'];
        $dateTo = $dateRange['to'];

        // Calculate totals with fresh queries
        $totalSales = Sale::where('organization_id', $organizationId)
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->count();

        $totalRevenue = Sale::where('organization_id', $organizationId)
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->sum('total_amount');

        $averageSale = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

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

        return Inertia::render('Reports/Sales', [
            'totalSales' => $totalSales,
            'totalRevenue' => $totalRevenue,
            'averageSale' => $averageSale,
            'salesByProduct' => $salesByProduct,
            'salesByCustomer' => $salesByCustomer,
            'dailySales' => $dailySales,
            'period' => $period,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function revenue(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $period = $request->input('period', 'month');
        $dateRange = $this->getDateRange($request);
        $dateFrom = $dateRange['from'];
        $dateTo = $dateRange['to'];

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
            ['source' => 'Other Income', 'amount' => max(0, $incomeMovements - $paymentsRevenue)],
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
            'totalRevenue' => $salesRevenue + $paymentsRevenue + max(0, $incomeMovements - $paymentsRevenue),
            'salesRevenue' => $salesRevenue,
            'paymentsRevenue' => $paymentsRevenue,
            'otherIncome' => max(0, $incomeMovements - $paymentsRevenue),
            'revenueBySource' => $revenueBySource,
            'dailyRevenue' => $dailyRevenue,
            'period' => $period,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function expenses(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $period = $request->input('period', 'month');
        $dateRange = $this->getDateRange($request);
        $dateFrom = $dateRange['from'];
        $dateTo = $dateRange['to'];

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
            'period' => $period,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function profitLoss(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $period = $request->input('period', 'month');
        $dateRange = $this->getDateRange($request);
        $dateFrom = $dateRange['from'];
        $dateTo = $dateRange['to'];

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
            'period' => $period,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function liabilities(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $period = $request->input('period', 'month');
        $dateRange = $this->getDateRange($request);
        $dateFrom = $dateRange['from'];
        $dateTo = $dateRange['to'];

        // Get all unpaid/partially paid bills
        $bills = \App\Models\Bill::where('organization_id', $organizationId)
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->where('status', '!=', 'cancelled')
            ->with('vendor')
            ->get();

        $totalLiabilities = $bills->sum('amount_due');
        $overdueAmount = $bills->filter(fn($bill) => $bill->due_date && $bill->due_date->isPast())
            ->sum('amount_due');
        $upcomingAmount = $bills->filter(fn($bill) => $bill->due_date && $bill->due_date->isFuture())
            ->sum('amount_due');

        // Bills by vendor
        $billsByVendor = DB::table('bills')
            ->join('vendors', 'bills.vendor_id', '=', 'vendors.id')
            ->where('bills.organization_id', $organizationId)
            ->whereIn('bills.payment_status', ['unpaid', 'partially_paid'])
            ->where('bills.status', '!=', 'cancelled')
            ->select(
                'vendors.name as vendor_name',
                DB::raw('SUM(bills.amount_due) as total_due'),
                DB::raw('COUNT(bills.id) as bill_count')
            )
            ->groupBy('vendors.id', 'vendors.name')
            ->orderByDesc('total_due')
            ->get();

        // Bills by category
        $billsByCategory = DB::table('bills')
            ->where('organization_id', $organizationId)
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->where('status', '!=', 'cancelled')
            ->select(
                'category',
                DB::raw('SUM(amount_due) as total_due'),
                DB::raw('COUNT(*) as bill_count')
            )
            ->groupBy('category')
            ->orderByDesc('total_due')
            ->get();

        // Timeline data (upcoming bills by week)
        $timelineData = [];
        for ($i = 0; $i < 12; $i++) {
            $weekStart = Carbon::now()->addWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->addWeeks($i)->endOfWeek();
            
            $weekBills = $bills->filter(function ($bill) use ($weekStart, $weekEnd) {
                return $bill->due_date && $bill->due_date->between($weekStart, $weekEnd);
            });

            $timelineData[] = [
                'week' => $weekStart->format('M d'),
                'amount' => $weekBills->sum('amount_due'),
                'bill_count' => $weekBills->count(),
            ];
        }

        // Liabilities for 30, 60, 90 days
        $now = Carbon::now();
        $day30 = $now->copy()->addDays(30);
        $day60 = $now->copy()->addDays(60);
        $day90 = $now->copy()->addDays(90);

        $bills30Days = $bills->filter(function ($bill) use ($now, $day30) {
            return $bill->due_date && $bill->due_date->between($now, $day30);
        });
        $liabilities30Days = $bills30Days->sum('amount_due');

        $bills60Days = $bills->filter(function ($bill) use ($now, $day60) {
            return $bill->due_date && $bill->due_date->between($now, $day60);
        });
        $liabilities60Days = $bills60Days->sum('amount_due');

        $bills90Days = $bills->filter(function ($bill) use ($now, $day90) {
            return $bill->due_date && $bill->due_date->between($now, $day90);
        });
        $liabilities90Days = $bills90Days->sum('amount_due');

        return Inertia::render('Reports/Liabilities', [
            'totalLiabilities' => $totalLiabilities,
            'overdueAmount' => $overdueAmount,
            'upcomingAmount' => $upcomingAmount,
            'bills' => $bills->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'vendor_name' => $bill->vendor->name ?? 'Unknown',
                    'amount_due' => $bill->amount_due,
                    'due_date' => $bill->due_date ? $bill->due_date->toDateString() : null,
                    'due_date_formatted' => $bill->due_date ? $bill->due_date->format('M d, Y') : null,
                    'is_overdue' => $bill->due_date && $bill->due_date->isPast(),
                    'payment_status' => $bill->payment_status,
                    'category' => $bill->category,
                ];
            }),
            'billsByVendor' => $billsByVendor,
            'billsByCategory' => $billsByCategory,
            'timelineData' => $timelineData,
            'liabilities30Days' => $liabilities30Days,
            'liabilities60Days' => $liabilities60Days,
            'liabilities90Days' => $liabilities90Days,
            'period' => $period,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function projectedIncome(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $period = $request->input('period', 'month');
        $dateRange = $this->getDateRange($request);
        $dateFrom = $dateRange['from'];
        $dateTo = $dateRange['to'];

        // Get pending invoices
        $invoices = \App\Models\Invoice::where('organization_id', $organizationId)
            ->whereIn('status', ['sent', 'overdue'])
            ->whereRaw('total_amount > paid_amount')
            ->with('customer')
            ->get();

        // Get pending quotations
        $quotations = \App\Models\Quotation::where('organization_id', $organizationId)
            ->whereIn('status', ['sent', 'viewed'])
            ->where('valid_until', '>=', now())
            ->with(['customer', 'prospect'])
            ->get();

        $totalProjectedIncome = $invoices->sum('amount_due') + $quotations->sum('total');
        $invoiceProjected = $invoices->sum('amount_due');
        $quotationProjected = $quotations->sum('total');

        // Projected income by customer
        $incomeByCustomer = DB::table('invoices')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->where('invoices.organization_id', $organizationId)
            ->whereIn('invoices.status', ['sent', 'overdue'])
            ->whereRaw('invoices.total_amount > invoices.paid_amount')
            ->select(
                'customers.name as customer_name',
                DB::raw('SUM(invoices.total_amount - invoices.paid_amount) as total_due'),
                DB::raw('COUNT(invoices.id) as invoice_count')
            )
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_due')
            ->get();

        // Timeline data (projected income by week)
        $timelineData = [];
        for ($i = 0; $i < 12; $i++) {
            $weekStart = Carbon::now()->addWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->addWeeks($i)->endOfWeek();
            
            $weekInvoices = $invoices->filter(function ($invoice) use ($weekStart, $weekEnd) {
                return $invoice->due_date && $invoice->due_date->between($weekStart, $weekEnd);
            });

            $weekQuotations = $quotations->filter(function ($quotation) use ($weekStart, $weekEnd) {
                return $quotation->valid_until && $quotation->valid_until->between($weekStart, $weekEnd);
            });

            $timelineData[] = [
                'week' => $weekStart->format('M d'),
                'amount' => $weekInvoices->sum('amount_due') + $weekQuotations->sum('total'),
                'invoice_amount' => $weekInvoices->sum('amount_due'),
                'quotation_amount' => $weekQuotations->sum('total'),
                'invoice_count' => $weekInvoices->count(),
                'quotation_count' => $weekQuotations->count(),
            ];
        }

        // Projected income for 30, 60, 90 days
        $now = Carbon::now();
        $day30 = $now->copy()->addDays(30);
        $day60 = $now->copy()->addDays(60);
        $day90 = $now->copy()->addDays(90);

        $invoices30Days = $invoices->filter(function ($invoice) use ($now, $day30) {
            return $invoice->due_date && $invoice->due_date->between($now, $day30);
        });
        $quotations30Days = $quotations->filter(function ($quotation) use ($now, $day30) {
            return $quotation->valid_until && $quotation->valid_until->between($now, $day30);
        });
        $projected30Days = $invoices30Days->sum('amount_due') + $quotations30Days->sum('total');

        $invoices60Days = $invoices->filter(function ($invoice) use ($now, $day60) {
            return $invoice->due_date && $invoice->due_date->between($now, $day60);
        });
        $quotations60Days = $quotations->filter(function ($quotation) use ($now, $day60) {
            return $quotation->valid_until && $quotation->valid_until->between($now, $day60);
        });
        $projected60Days = $invoices60Days->sum('amount_due') + $quotations60Days->sum('total');

        $invoices90Days = $invoices->filter(function ($invoice) use ($now, $day90) {
            return $invoice->due_date && $invoice->due_date->between($now, $day90);
        });
        $quotations90Days = $quotations->filter(function ($quotation) use ($now, $day90) {
            return $quotation->valid_until && $quotation->valid_until->between($now, $day90);
        });
        $projected90Days = $invoices90Days->sum('amount_due') + $quotations90Days->sum('total');

        return Inertia::render('Reports/ProjectedIncome', [
            'totalProjectedIncome' => $totalProjectedIncome,
            'invoiceProjected' => $invoiceProjected,
            'quotationProjected' => $quotationProjected,
            'invoices' => $invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name ?? 'Unknown',
                    'amount_due' => $invoice->amount_due,
                    'due_date' => $invoice->due_date ? $invoice->due_date->toDateString() : null,
                    'due_date_formatted' => $invoice->due_date ? $invoice->due_date->format('M d, Y') : null,
                    'is_overdue' => $invoice->due_date && $invoice->due_date->isPast(),
                    'status' => $invoice->status,
                ];
            }),
            'quotations' => $quotations->map(function ($quotation) {
                return [
                    'id' => $quotation->id,
                    'quotation_number' => $quotation->quotation_number,
                    'title' => $quotation->title,
                    'customer_name' => $quotation->customer->name ?? $quotation->prospect->name ?? 'Unknown',
                    'total' => $quotation->total,
                    'valid_until' => $quotation->valid_until ? $quotation->valid_until->toDateString() : null,
                    'valid_until_formatted' => $quotation->valid_until ? $quotation->valid_until->format('M d, Y') : null,
                    'status' => $quotation->status,
                ];
            }),
            'incomeByCustomer' => $incomeByCustomer,
            'timelineData' => $timelineData,
            'projected30Days' => $projected30Days,
            'projected60Days' => $projected60Days,
            'projected90Days' => $projected90Days,
            'period' => $period,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }
}
