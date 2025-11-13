<?php

namespace App\Services\Addy\Agents;

use App\Models\Organization;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Payment;
use App\Traits\Cacheable;
use Illuminate\Support\Facades\DB;

class SalesAgent
{
    use Cacheable;

    protected Organization $organization;
    protected int $cacheTtl = 300; // 5 minutes

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }

    protected function getOrganizationId(): int|string
    {
        return $this->organization->id;
    }

    public function perceive(): array
    {
        return $this->remember('perception', function () {
            return $this->doPerceive();
        });
    }

    protected function doPerceive(): array
    {
        return [
            'customer_stats' => $this->getCustomerStats(),
            'invoice_health' => $this->getInvoiceHealth(),
            'sales_performance' => $this->getSalesPerformance(),
            'quote_conversion' => $this->getQuoteConversion(),
            'payment_trends' => $this->getPaymentTrends(),
        ];
    }

    protected function getCustomerStats(): array
    {
        $totalCustomers = Customer::where('organization_id', $this->organization->id)->count();
        
        $newThisMonth = Customer::where('organization_id', $this->organization->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        // Since customers table doesn't have a status column, all customers are considered active
        return [
            'total' => $totalCustomers,
            'active' => $totalCustomers, // All customers are active (no status field)
            'new_this_month' => $newThisMonth,
            'inactive' => 0, // No inactive customers since there's no status field
        ];
    }

    protected function getInvoiceHealth(): array
    {
        // Check for overdue: status is 'overdue' OR (status is 'sent' and due_date < now())
        $overdueInvoices = Invoice::where('organization_id', $this->organization->id)
            ->where(function($query) {
                $query->where('status', 'overdue')
                      ->orWhere(function($q) {
                          $q->where('status', 'sent')
                            ->where('due_date', '<', now());
                      });
            })
            ->get();

        $pendingInvoices = Invoice::where('organization_id', $this->organization->id)
            ->where('status', 'sent')
            ->where('due_date', '>=', now())
            ->get();

        $overdueAmount = $overdueInvoices->sum('total_amount');
        $pendingAmount = $pendingInvoices->sum('total_amount');

        return [
            'overdue_count' => $overdueInvoices->count(),
            'overdue_amount' => $overdueAmount,
            'pending_count' => $pendingInvoices->count(),
            'pending_amount' => $pendingAmount,
            'total_outstanding' => $overdueAmount + $pendingAmount,
        ];
    }

    protected function getSalesPerformance(): array
    {
        $thisMonth = $this->getMonthlySales(now());
        $lastMonth = $this->getMonthlySales(now()->subMonth());

        $change = $lastMonth > 0 
            ? (($thisMonth - $lastMonth) / $lastMonth) * 100 
            : 0;

        return [
            'current_month' => $thisMonth,
            'last_month' => $lastMonth,
            'change_percentage' => round($change, 2),
            'trend' => $change > 5 ? 'increasing' : ($change < -5 ? 'decreasing' : 'stable'),
        ];
    }

    protected function getMonthlySales($date): float
    {
        return Invoice::where('organization_id', $this->organization->id)
            ->where('status', 'paid')
            ->whereBetween('created_at', [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth()
            ])
            ->sum('total_amount');
    }

    protected function getQuoteConversion(): array
    {
        $thisMonthQuotes = Quote::where('organization_id', $this->organization->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->get();

        $converted = $thisMonthQuotes->where('status', 'accepted')->count();
        $total = $thisMonthQuotes->count();

        $conversionRate = $total > 0 ? ($converted / $total) * 100 : 0;

        return [
            'total_quotes' => $total,
            'converted' => $converted,
            'pending' => $thisMonthQuotes->where('status', 'sent')->count(),
            'rejected' => $thisMonthQuotes->where('status', 'rejected')->count(),
            'conversion_rate' => round($conversionRate, 2),
        ];
    }

    protected function getPaymentTrends(): array
    {
        $thisMonthPayments = Payment::where('organization_id', $this->organization->id)
            ->whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->get();

        $avgDaysToPayment = $this->calculateAverageDaysToPayment();

        return [
            'total_received' => $thisMonthPayments->sum('amount'),
            'payment_count' => $thisMonthPayments->count(),
            'avg_days_to_payment' => $avgDaysToPayment,
        ];
    }

    protected function calculateAverageDaysToPayment(): float
    {
        // Get payments that have allocations to invoices
        $recentPayments = Payment::where('organization_id', $this->organization->id)
            ->whereHas('allocations', function($query) {
                $query->whereNotNull('invoice_id');
            })
            ->whereBetween('payment_date', [now()->subMonth(), now()])
            ->with(['allocations.invoice'])
            ->get();

        if ($recentPayments->isEmpty()) {
            return 0;
        }

        $totalDays = 0;
        $count = 0;

        foreach ($recentPayments as $payment) {
            // A payment can have multiple allocations to different invoices
            foreach ($payment->allocations as $allocation) {
                if ($allocation->invoice) {
                    $days = $allocation->invoice->created_at->diffInDays($payment->payment_date);
                    $totalDays += $days;
                    $count++;
                }
            }
        }

        return $count > 0 ? round($totalDays / $count, 1) : 0;
    }

    public function analyze(): array
    {
        $perception = $this->perceive();
        $insights = [];

        // Overdue invoices alert
        if ($perception['invoice_health']['overdue_count'] > 0) {
            $insights[] = [
                'type' => 'alert',
                'category' => 'sales',
                'title' => 'Overdue Invoices Detected',
                'description' => "You have {$perception['invoice_health']['overdue_count']} overdue invoices totaling " . 
                    number_format($perception['invoice_health']['overdue_amount'], 2) . ". This affects your cash flow.",
                'priority' => 0.85,
                'is_actionable' => true,
                'suggested_actions' => [
                    'Send payment reminders to customers',
                    'Review payment terms',
                    'Consider offering early payment discounts',
                ],
                'action_url' => '/invoices',
            ];
        }

        // Pending invoices observation
        if ($perception['invoice_health']['pending_count'] > 0) {
            $insights[] = [
                'type' => 'observation',
                'category' => 'sales',
                'title' => 'Outstanding Invoices',
                'description' => "{$perception['invoice_health']['pending_count']} invoices pending payment, totaling " . 
                    number_format($perception['invoice_health']['pending_amount'], 2) . ".",
                'priority' => 0.5,
                'is_actionable' => true,
                'suggested_actions' => [
                    'Monitor upcoming due dates',
                    'Follow up before due date',
                ],
                'action_url' => '/invoices',
            ];
        }

        // Sales performance insight
        if ($perception['sales_performance']['trend'] === 'decreasing' && 
            $perception['sales_performance']['change_percentage'] < -10) {
            $insights[] = [
                'type' => 'alert',
                'category' => 'sales',
                'title' => 'Sales Decline Detected',
                'description' => "Sales are down " . abs($perception['sales_performance']['change_percentage']) . 
                    "% compared to last month.",
                'priority' => 0.8,
                'is_actionable' => true,
                'suggested_actions' => [
                    'Review customer engagement',
                    'Check product/service quality',
                    'Analyze market conditions',
                    'Consider promotional campaigns',
                ],
                'action_url' => '/reports/sales',
            ];
        } elseif ($perception['sales_performance']['trend'] === 'increasing' && 
                  $perception['sales_performance']['change_percentage'] > 20) {
            $insights[] = [
                'type' => 'achievement',
                'category' => 'sales',
                'title' => 'Strong Sales Growth!',
                'description' => "Excellent work! Sales are up " . 
                    $perception['sales_performance']['change_percentage'] . "% from last month.",
                'priority' => 0.6,
                'is_actionable' => false,
                'suggested_actions' => [
                    'Analyze what drove the growth',
                    'Scale successful strategies',
                ],
                'action_url' => '/reports/sales',
            ];
        }

        // Quote conversion insight
        if ($perception['quote_conversion']['total_quotes'] > 5 && 
            $perception['quote_conversion']['conversion_rate'] < 30) {
            $insights[] = [
                'type' => 'suggestion',
                'category' => 'sales',
                'title' => 'Low Quote Conversion Rate',
                'description' => "Only " . $perception['quote_conversion']['conversion_rate'] . 
                    "% of quotes are being accepted.",
                'priority' => 0.65,
                'is_actionable' => true,
                'suggested_actions' => [
                    'Review pricing strategy',
                    'Improve quote presentation',
                    'Follow up with rejected quotes',
                    'Understand customer objections',
                ],
                'action_url' => '/quotes',
            ];
        }

        // New customers achievement
        if ($perception['customer_stats']['new_this_month'] > 5) {
            $insights[] = [
                'type' => 'achievement',
                'category' => 'sales',
                'title' => 'New Customer Growth',
                'description' => "Great job! You've acquired {$perception['customer_stats']['new_this_month']} new customers this month.",
                'priority' => 0.5,
                'is_actionable' => false,
                'suggested_actions' => [
                    'Maintain customer acquisition momentum',
                    'Focus on customer retention',
                ],
                'action_url' => '/customers',
            ];
        }

        // Slow payment trend
        if ($perception['payment_trends']['avg_days_to_payment'] > 45) {
            $insights[] = [
                'type' => 'suggestion',
                'category' => 'sales',
                'title' => 'Slow Payment Collection',
                'description' => "Average time to receive payment is " . 
                    $perception['payment_trends']['avg_days_to_payment'] . " days.",
                'priority' => 0.7,
                'is_actionable' => true,
                'suggested_actions' => [
                    'Review payment terms',
                    'Offer early payment incentives',
                    'Implement automated reminders',
                    'Consider requiring deposits',
                ],
                'action_url' => '/payments',
            ];
        }

        return $insights;
    }
}

