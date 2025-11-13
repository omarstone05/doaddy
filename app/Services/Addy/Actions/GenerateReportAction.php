<?php

namespace App\Services\Addy\Actions;

use App\Models\Invoice;
use App\Models\MoneyMovement;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GenerateReportAction extends BaseAction
{
    public function validate(): bool
    {
        return true;
    }

    public function preview(): array
    {
        $report = $this->buildReport();

        return [
            'title' => $report['title'],
            'description' => "Generate {$report['title']} for {$report['range']['label']}.",
            'items' => $report['highlights'],
            'impact' => $report['impact'],
            'warnings' => $report['warnings'],
        ];
    }

    public function execute(): array
    {
        $report = $this->buildReport();

        return [
            'success' => true,
            'message' => "{$report['title']} ready for {$report['range']['label']}.",
            'report' => $report,
        ];
    }

    protected function buildReport(): array
    {
        $type = $this->parameters['type'] ?? 'general';
        $range = $this->resolveDateRange($this->parameters['period'] ?? 'last_30_days');

        switch ($type) {
            case 'cash_flow':
            case 'cash':
                $data = $this->buildCashFlowReport($range['start'], $range['end']);
                break;
            case 'expenses':
                $data = $this->buildExpenseReport($range['start'], $range['end']);
                break;
            case 'sales':
                $data = $this->buildSalesReport($range['start'], $range['end']);
                break;
            case 'budget':
                $data = $this->buildBudgetSnapshot($range['start'], $range['end']);
                break;
            default:
                $data = $this->buildGeneralReport($range['start'], $range['end']);
                break;
        }

        return array_merge($data, [
            'type' => $type,
            'range' => $range,
            'impact' => $data['impact'] ?? 'medium',
            'warnings' => $data['warnings'] ?? [],
        ]);
    }

    protected function buildCashFlowReport(Carbon $start, Carbon $end): array
    {
        $movements = MoneyMovement::where('organization_id', $this->organization->id)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', 'approved')
            ->get();

        $incomeTotal = (float) $movements->where('flow_type', 'income')->sum('amount');
        $expenseTotal = (float) $movements->where('flow_type', 'expense')->sum('amount');
        $net = $incomeTotal - $expenseTotal;

        $weeklyBreakdown = $movements->groupBy(function (MoneyMovement $movement) {
            return Carbon::parse($movement->transaction_date)->startOfWeek()->format('Y-m-d');
        })->map(function (Collection $items, string $weekStart) {
            $income = (float) $items->where('flow_type', 'income')->sum('amount');
            $expenses = (float) $items->where('flow_type', 'expense')->sum('amount');

            return [
                'week_start' => $weekStart,
                'income' => $income,
                'expenses' => $expenses,
                'net' => $income - $expenses,
            ];
        })->values()->all();

        $warnings = [];
        if ($expenseTotal > $incomeTotal) {
            $warnings[] = sprintf(
                'Expenses exceed income by %s in this period.',
                $this->formatCurrency(abs($net))
            );
        }

        return [
            'title' => 'Cash Flow Report',
            'highlights' => [
                ['label' => 'Total Income', 'value' => $this->formatCurrency($incomeTotal)],
                ['label' => 'Total Expenses', 'value' => $this->formatCurrency($expenseTotal)],
                ['label' => 'Net Cash Flow', 'value' => $this->formatCurrency($net)],
            ],
            'data' => [
                'income_total' => $incomeTotal,
                'expense_total' => $expenseTotal,
                'net_cash_flow' => $net,
                'weekly' => $weeklyBreakdown,
            ],
            'impact' => abs($net) > 10000 ? 'high' : 'medium',
            'warnings' => $warnings,
            'range' => $this->formatRangeLabel($start, $end),
        ];
    }

    protected function buildExpenseReport(Carbon $start, Carbon $end): array
    {
        $expenses = MoneyMovement::where('organization_id', $this->organization->id)
            ->where('flow_type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $total = (float) $expenses->sum('amount');

        $byCategory = $expenses->groupBy(function (MoneyMovement $movement) {
            return $movement->category ?: 'Uncategorized';
        })->map(fn (Collection $items) => (float) $items->sum('amount'))
            ->sortDesc();

        $topCategories = $byCategory->take(5)->map(function ($value, $category) use ($total) {
            return [
                'category' => $category,
                'amount' => $value,
                'percent' => $total > 0 ? round(($value / $total) * 100, 1) : 0,
            ];
        })->values()->all();

        $dailyAverage = $this->calculateDailyAverage($total, $start, $end);

        $warnings = [];
        if (!empty($topCategories) && $topCategories[0]['percent'] >= 40) {
            $warnings[] = "{$topCategories[0]['category']} represents {$topCategories[0]['percent']}% of your spend.";
        }

        return [
            'title' => 'Expense Summary',
            'highlights' => [
                ['label' => 'Total Expenses', 'value' => $this->formatCurrency($total)],
                ['label' => 'Daily Average', 'value' => $this->formatCurrency($dailyAverage)],
                ['label' => 'Top Category', 'value' => $topCategories[0]['category'] ?? 'N/A'],
            ],
            'data' => [
                'top_categories' => $topCategories,
                'daily_average' => $dailyAverage,
                'category_breakdown' => $byCategory,
            ],
            'impact' => $total > 20000 ? 'high' : 'medium',
            'warnings' => $warnings,
            'range' => $this->formatRangeLabel($start, $end),
        ];
    }

    protected function buildSalesReport(Carbon $start, Carbon $end): array
    {
        $invoices = Invoice::where('organization_id', $this->organization->id)
            ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
            ->with('customer')
            ->get();

        $payments = Payment::where('organization_id', $this->organization->id)
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $billed = (float) $invoices->sum('total_amount');
        $collected = (float) $payments->sum('amount');
        $outstanding = (float) $invoices->where('status', '!=', 'paid')->sum(fn ($invoice) => $invoice->balance);

        $warnings = [];
        if ($outstanding > 0 && $collected < $billed * 0.7) {
            $warnings[] = 'Collections are lagging behind invoices. Follow up on receivables.';
        }

        return [
            'title' => 'Sales & Receivables Report',
            'highlights' => [
                ['label' => 'Total Invoiced', 'value' => $this->formatCurrency($billed)],
                ['label' => 'Collected', 'value' => $this->formatCurrency($collected)],
                ['label' => 'Outstanding', 'value' => $this->formatCurrency($outstanding)],
            ],
            'data' => [
                'invoice_count' => $invoices->count(),
                'payments' => $payments->map(fn ($payment) => [
                    'date' => $payment->payment_date,
                    'amount' => (float) $payment->amount,
                ])->toArray(),
                'customers' => $invoices->groupBy(fn ($invoice) => $invoice->customer->name ?? 'Unknown')
                    ->map(fn (Collection $items) => (float) $items->sum('total_amount'))
                    ->sortDesc()
                    ->take(5),
            ],
            'impact' => $billed > 30000 ? 'high' : 'medium',
            'warnings' => $warnings,
            'range' => $this->formatRangeLabel($start, $end),
        ];
    }

    protected function buildBudgetSnapshot(Carbon $start, Carbon $end): array
    {
        $cashReport = $this->buildCashFlowReport($start, $end);
        $expenseReport = $this->buildExpenseReport($start, $end);

        return [
            'title' => 'Budget Snapshot',
            'highlights' => array_merge(
                array_slice($cashReport['highlights'], 0, 2),
                [$expenseReport['highlights'][0]]
            ),
            'data' => [
                'cash_flow' => $cashReport['data'],
                'expenses' => $expenseReport['data'],
            ],
            'impact' => 'medium',
            'warnings' => array_merge($cashReport['warnings'], $expenseReport['warnings']),
            'range' => $this->formatRangeLabel($start, $end),
        ];
    }

    protected function buildGeneralReport(Carbon $start, Carbon $end): array
    {
        $cash = $this->buildCashFlowReport($start, $end);
        $expenses = $this->buildExpenseReport($start, $end);

        return [
            'title' => 'Business Health Report',
            'highlights' => array_merge(
                $cash['highlights'],
                [$expenses['highlights'][1]] // Daily average spend
            ),
            'data' => [
                'cash_flow' => $cash['data'],
                'expenses' => $expenses['data'],
            ],
            'impact' => $cash['impact'],
            'warnings' => array_merge($cash['warnings'], $expenses['warnings']),
            'range' => $this->formatRangeLabel($start, $end),
        ];
    }

    protected function resolveDateRange(string $period): array
    {
        $now = now();
        $start = (clone $now)->subDays(30)->startOfDay();
        $end = (clone $now)->endOfDay();

        if (preg_match('/last_(\d+)_days/', $period, $matches)) {
            $start = (clone $now)->subDays((int) $matches[1])->startOfDay();
        } elseif (preg_match('/last_(\d+)_weeks/', $period, $matches)) {
            $start = (clone $now)->subWeeks((int) $matches[1])->startOfDay();
        } elseif (preg_match('/last_(\d+)_months/', $period, $matches)) {
            $start = (clone $now)->subMonths((int) $matches[1])->startOfDay();
        } else {
            switch ($period) {
                case 'last_week':
                    $start = (clone $now)->subWeek()->startOfWeek();
                    $end = (clone $now)->subWeek()->endOfWeek();
                    break;
                case 'this_week':
                    $start = (clone $now)->startOfWeek();
                    $end = (clone $now)->endOfWeek();
                    break;
                case 'last_month':
                    $start = (clone $now)->subMonth()->startOfMonth();
                    $end = (clone $now)->subMonth()->endOfMonth();
                    break;
                case 'this_month':
                    $start = (clone $now)->startOfMonth();
                    $end = (clone $now)->endOfMonth();
                    break;
                case 'last_quarter':
                    $start = (clone $now)->subQuarter()->startOfQuarter();
                    $end = (clone $now)->subQuarter()->endOfQuarter();
                    break;
                case 'this_quarter':
                    $start = (clone $now)->startOfQuarter();
                    $end = (clone $now)->endOfQuarter();
                    break;
                case 'year_to_date':
                    $start = (clone $now)->startOfYear();
                    break;
                case 'last_year':
                    $start = (clone $now)->subYear()->startOfYear();
                    $end = (clone $now)->subYear()->endOfYear();
                    break;
                case 'this_year':
                    $start = (clone $now)->startOfYear();
                    $end = (clone $now)->endOfYear();
                    break;
            }
        }

        return [
            'start' => $start,
            'end' => $end,
            'label' => $this->formatRangeLabel($start, $end),
        ];
    }

    protected function formatCurrency(float $amount): string
    {
        $currency = $this->organization->currency ?? 'ZMW';
        return sprintf('%s %s', $currency, number_format($amount, 2));
    }

    protected function formatRangeLabel(Carbon $start, Carbon $end): string
    {
        return $start->format('M j, Y') . ' - ' . $end->format('M j, Y');
    }

    protected function calculateDailyAverage(float $total, Carbon $start, Carbon $end): float
    {
        $days = max(1, $start->diffInDays($end) + 1);
        return $total / $days;
    }
}
