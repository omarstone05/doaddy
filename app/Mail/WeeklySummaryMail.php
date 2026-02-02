<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class WeeklySummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Organization $organization;
    public array $summary;
    public string $weekStartDate;
    public string $weekEndDate;
    public string $currency;

    public function __construct(User $user, Organization $organization)
    {
        $this->user = $user;
        $this->organization = $organization;
        $this->currency = $organization->currency ?? 'ZMW';
        
        // Calculate week dates
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(7);
        $this->weekStartDate = $startDate->format('M d');
        $this->weekEndDate = $endDate->format('M d, Y');
        
        // Build summary data
        $this->summary = $this->buildSummary($startDate, $endDate);
    }

    protected function buildSummary(Carbon $startDate, Carbon $endDate): array
    {
        $orgId = $this->organization->id;

        // Invoice metrics
        $invoicesCreated = Invoice::where('organization_id', $orgId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $invoicesSent = Invoice::where('organization_id', $orgId)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->whereIn('status', ['sent', 'paid'])
            ->count();

        $invoicesPaid = Invoice::where('organization_id', $orgId)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->count();

        $revenueReceived = Invoice::where('organization_id', $orgId)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('paid_amount');

        $invoicedAmount = Invoice::where('organization_id', $orgId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');

        // Overdue invoices
        $overdueInvoices = Invoice::where('organization_id', $orgId)
            ->whereIn('status', ['sent', 'overdue'])
            ->where('due_date', '<', now())
            ->count();

        $overdueAmount = Invoice::where('organization_id', $orgId)
            ->whereIn('status', ['sent', 'overdue'])
            ->where('due_date', '<', now())
            ->sum(DB::raw('total_amount - paid_amount'));

        // Upcoming due invoices (next 7 days)
        $upcomingDueInvoices = Invoice::where('organization_id', $orgId)
            ->whereIn('status', ['sent', 'draft'])
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->count();

        // Customer metrics
        $newCustomers = Customer::where('organization_id', $orgId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalCustomers = Customer::where('organization_id', $orgId)->count();

        // Bills/Expenses (if available)
        $billsPaid = 0;
        $expensesAmount = 0;
        if (class_exists(Bill::class)) {
            try {
                $billsPaid = Bill::where('organization_id', $orgId)
                    ->whereBetween('paid_at', [$startDate, $endDate])
                    ->where('status', 'paid')
                    ->count();

                $expensesAmount = Bill::where('organization_id', $orgId)
                    ->whereBetween('paid_at', [$startDate, $endDate])
                    ->where('status', 'paid')
                    ->sum('total_amount');
            } catch (\Exception $e) {
                // Bills table might not exist
            }
        }

        // Top customers this week by revenue
        $topCustomers = Invoice::where('organization_id', $orgId)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->with('customer:id,name')
            ->select('customer_id', DB::raw('SUM(paid_amount) as total_paid'))
            ->groupBy('customer_id')
            ->orderByDesc('total_paid')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->customer->name ?? 'Unknown',
                    'amount' => number_format($item->total_paid, 2),
                ];
            })
            ->toArray();

        return [
            // Revenue & Invoicing
            'revenue_received' => number_format($revenueReceived, 2),
            'revenue_received_raw' => $revenueReceived,
            'invoiced_amount' => number_format($invoicedAmount, 2),
            'invoices_created' => $invoicesCreated,
            'invoices_sent' => $invoicesSent,
            'invoices_paid' => $invoicesPaid,
            
            // Outstanding
            'overdue_invoices' => $overdueInvoices,
            'overdue_amount' => number_format($overdueAmount, 2),
            'upcoming_due_invoices' => $upcomingDueInvoices,
            
            // Expenses
            'bills_paid' => $billsPaid,
            'expenses_amount' => number_format($expensesAmount, 2),
            
            // Net
            'net_cash_flow' => number_format($revenueReceived - $expensesAmount, 2),
            'net_positive' => ($revenueReceived - $expensesAmount) >= 0,
            
            // Customers
            'new_customers' => $newCustomers,
            'total_customers' => $totalCustomers,
            'top_customers' => $topCustomers,
            
            // Activity indicators
            'has_activity' => ($invoicesCreated > 0 || $revenueReceived > 0 || $newCustomers > 0),
        ];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Weekly Summary: {$this->organization->name} ({$this->weekStartDate} - {$this->weekEndDate})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-summary',
        );
    }
}
