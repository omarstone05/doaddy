<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DashboardCard;
use App\Models\OrgDashboardCard;
use App\Models\MoneyAccount;
use App\Models\MoneyMovement;
use App\Modules\Retail\Models\Sale;
use App\Models\Invoice;
use App\Models\GoodsAndService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                'end' => $now->copy()->endOfDay(),
            ],
        };
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $organizationId = $user->organization_id;
        $timeframe = $request->get('timeframe', 'today');
        $dateRange = $this->getDateRange($timeframe);
        $previousRange = $this->getPreviousRange($timeframe);
        
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
        
        // Recent sales
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
            $recentSales = [];
        }
        
        return response()->json([
            'stats' => [
                'total_accounts' => $totalAccounts,
                'total_revenue' => (float) $totalRevenue,
                'total_expenses' => (float) $totalExpenses,
                'net_balance' => (float) $netBalance,
                'previous_revenue' => (float) $previousRevenue,
                'previous_expenses' => (float) $previousExpenses,
                'revenue_trend' => $revenueTrend,
                'expense_trend' => $expenseTrend,
                'recent_sales' => $recentSales,
            ],
        ]);
    }
}
