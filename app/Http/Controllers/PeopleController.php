<?php

namespace App\Http\Controllers;

use App\Http\Controllers\CustomerVendorController;
use App\Models\AddyInsight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PeopleController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        // Use CustomerVendorController logic for the overview
        $customerVendorController = new CustomerVendorController();
        
        // Get projected income from pending invoices
        $projectedIncome = $this->getProjectedIncome($organizationId);

        // Get upcoming liabilities
        $upcomingLiabilities = $this->getUpcomingLiabilities($organizationId);

        // Get customer metrics
        $customerMetrics = $this->getCustomerMetrics($organizationId);

        // Get quotation metrics
        $quotationMetrics = $this->getQuotationMetrics($organizationId);

        // Get customer personas with stats
        $customerPersonas = $this->getCustomerPersonas($organizationId);

        // Get projected income timeline
        $incomeTimeline = $this->getIncomeTimeline($organizationId);

        // Get upcoming bills by vendor
        $upcomingBills = $this->getUpcomingBillsByVendor($organizationId);
        
        // Get projected bill timeline
        $billTimeline = $this->getBillTimeline($organizationId);
        
        // Get People-specific insights
        $insights = AddyInsight::active($organizationId)
            ->where('category', 'people')
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

        return Inertia::render('People/Index', [
            'projectedIncome' => $projectedIncome,
            'upcomingLiabilities' => $upcomingLiabilities,
            'customerMetrics' => $customerMetrics,
            'quotationMetrics' => $quotationMetrics,
            'customerPersonas' => $customerPersonas,
            'incomeTimeline' => $incomeTimeline,
            'upcomingBills' => $upcomingBills,
            'billTimeline' => $billTimeline,
            'insights' => $insights,
        ]);
    }

    // Copy methods from CustomerVendorController
    private function getProjectedIncome(string $organizationId): array
    {
        $pendingInvoices = \App\Models\Invoice::where('organization_id', $organizationId)
            ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
            ->get();

        $total = $pendingInvoices->sum('amount_due');

        // Group by timeframe
        $thisWeek = $pendingInvoices
            ->filter(fn($invoice) => $invoice->due_date->between(now(), now()->addWeek()))
            ->sum('amount_due');

        $thisMonth = $pendingInvoices
            ->filter(fn($invoice) => $invoice->due_date->between(now(), now()->addMonth()))
            ->sum('amount_due');

        $thisQuarter = $pendingInvoices
            ->filter(fn($invoice) => $invoice->due_date->between(now(), now()->addMonths(3)))
            ->sum('amount_due');

        $overdue = $pendingInvoices
            ->filter(fn($invoice) => $invoice->is_overdue ?? $invoice->due_date->isPast())
            ->sum('amount_due');

        return [
            'total' => $total,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth,
            'this_quarter' => $thisQuarter,
            'overdue' => $overdue,
            'invoice_count' => $pendingInvoices->count(),
        ];
    }

    private function getUpcomingLiabilities(string $organizationId): array
    {
        $unpaidBills = \App\Models\Bill::where('organization_id', $organizationId)
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->where('status', '!=', 'cancelled')
            ->get();

        $total = $unpaidBills->sum('amount_due');
        $overdue = $unpaidBills->filter(fn($bill) => ($bill->is_overdue ?? $bill->due_date->isPast()))->sum('amount_due');
        
        $dueSoon = $unpaidBills
            ->filter(fn($bill) => $bill->due_date->between(now(), now()->addWeek()))
            ->sum('amount_due');

        return [
            'total' => $total,
            'overdue' => $overdue,
            'due_soon' => $dueSoon,
            'vendor_count' => $unpaidBills->pluck('vendor_id')->unique()->count(),
            'bill_count' => $unpaidBills->count(),
        ];
    }

    private function getCustomerMetrics(string $organizationId): array
    {
        $customers = \App\Models\Customer::where('organization_id', $organizationId);

        $totalCustomers = $customers->count();
        $activeCustomers = $customers->where('status', 'active')->count();
        
        $newThisMonth = \App\Models\Customer::where('organization_id', $organizationId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalLifetimeValue = $customers->sum('lifetime_value');
        $outstandingBalance = $customers->sum('outstanding_balance');

        return [
            'total' => $totalCustomers,
            'active' => $activeCustomers,
            'new_this_month' => $newThisMonth,
            'lifetime_value' => $totalLifetimeValue,
            'outstanding_balance' => $outstandingBalance,
        ];
    }

    private function getQuotationMetrics(string $organizationId): array
    {
        $quotations = \App\Models\Quotation::where('organization_id', $organizationId);

        $pending = $quotations->whereIn('status', ['sent', 'viewed'])->count();
        $totalValue = $quotations->whereIn('status', ['sent', 'viewed'])->sum('total');
        
        $acceptedThisMonth = \App\Models\Quotation::where('organization_id', $organizationId)
            ->where('status', 'accepted')
            ->whereMonth('responded_at', now()->month)
            ->whereYear('responded_at', now()->year)
            ->count();

        $sentThisMonth = \App\Models\Quotation::where('organization_id', $organizationId)
            ->whereMonth('sent_at', now()->month)
            ->whereYear('sent_at', now()->year)
            ->count();

        $conversionRate = $sentThisMonth > 0 
            ? round(($acceptedThisMonth / $sentThisMonth) * 100, 1)
            : 0;

        return [
            'pending' => $pending,
            'total_value' => $totalValue,
            'conversion_rate' => $conversionRate,
            'accepted_this_month' => $acceptedThisMonth,
        ];
    }

    private function getCustomerPersonas(string $organizationId): array
    {
        return \App\Models\CustomerPersona::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->withCount('customers')
            ->get()
            ->map(function ($persona) {
                return [
                    'id' => $persona->id,
                    'name' => $persona->name,
                    'description' => $persona->description,
                    'industry' => $persona->industry,
                    'size' => $persona->size,
                    'payment_behavior' => $persona->payment_behavior,
                    'color' => $persona->color,
                    'icon' => $persona->icon,
                    'customer_count' => $persona->customers_count,
                    'total_revenue' => $persona->total_revenue ?? 0,
                ];
            })
            ->toArray();
    }

    private function getIncomeTimeline(string $organizationId): array
    {
        $invoices = \App\Models\Invoice::where('organization_id', $organizationId)
            ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
            ->orderBy('due_date', 'asc')
            ->get();

        // Group by week for the next 12 weeks
        $timeline = [];
        for ($i = 0; $i < 12; $i++) {
            $weekStart = now()->addWeeks($i)->startOfWeek();
            $weekEnd = now()->addWeeks($i)->endOfWeek();
            
            $weekInvoices = $invoices->filter(function ($invoice) use ($weekStart, $weekEnd) {
                return $invoice->due_date->between($weekStart, $weekEnd);
            });

            $timeline[] = [
                'week' => $weekStart->format('M d'),
                'amount' => $weekInvoices->sum('amount_due'),
                'invoice_count' => $weekInvoices->count(),
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
            ];
        }

        return $timeline;
    }

    private function getUpcomingBillsByVendor(string $organizationId): array
    {
        $bills = \App\Models\Bill::where('organization_id', $organizationId)
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->where('status', '!=', 'cancelled')
            ->with('vendor')
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();

        return $bills->map(function ($bill) {
            return [
                'id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'vendor_name' => $bill->vendor->name ?? 'Unknown',
                'vendor_id' => $bill->vendor_id,
                'amount_due' => $bill->amount_due ?? ($bill->total - $bill->paid_amount),
                'due_date' => $bill->due_date->toDateString(),
                'due_date_formatted' => $bill->due_date->format('M d, Y'),
                'days_until_due' => $bill->due_date->diffInDays(now(), false),
                'is_overdue' => $bill->due_date->isPast(),
                'status' => $bill->status,
                'payment_status' => $bill->payment_status,
                'currency' => $bill->currency ?? 'USD',
            ];
        })->toArray();
    }

    private function getBillTimeline(string $organizationId): array
    {
        $bills = \App\Models\Bill::where('organization_id', $organizationId)
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->where('status', '!=', 'cancelled')
            ->orderBy('due_date', 'asc')
            ->get();

        // Group by week for the next 12 weeks
        $timeline = [];
        for ($i = 0; $i < 12; $i++) {
            $weekStart = now()->addWeeks($i)->startOfWeek();
            $weekEnd = now()->addWeeks($i)->endOfWeek();
            
            $weekBills = $bills->filter(function ($bill) use ($weekStart, $weekEnd) {
                return $bill->due_date->between($weekStart, $weekEnd);
            });

            $timeline[] = [
                'week' => $weekStart->format('M d'),
                'amount' => $weekBills->sum('amount_due'),
                'bill_count' => $weekBills->count(),
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
            ];
        }

        return $timeline;
    }
}

