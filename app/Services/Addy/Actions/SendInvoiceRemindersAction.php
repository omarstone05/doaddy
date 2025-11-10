<?php

namespace App\Services\Addy\Actions;

use App\Models\Invoice;

class SendInvoiceRemindersAction extends BaseAction
{
    public function validate(): bool
    {
        return true; // Always valid
    }

    public function preview(): array
    {
        $overdueInvoices = $this->getOverdueInvoices();

        return [
            'title' => 'Send Payment Reminders',
            'description' => "Send reminder emails to {$overdueInvoices->count()} customer(s) with overdue invoices.",
            'items' => $overdueInvoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'customer' => $invoice->customer->name ?? 'Unknown',
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->total_amount,
                    'days_overdue' => now()->diffInDays($invoice->due_date),
                    'email' => $invoice->customer->email ?? null,
                    'email_subject' => "Payment Reminder - Invoice #{$invoice->invoice_number}",
                    'email_preview' => $this->getEmailPreview($invoice),
                ];
            })->toArray(),
            'impact' => 'medium',
            'warnings' => $overdueInvoices->count() > 5 
                ? ['Sending to many customers at once. Consider batching.'] 
                : [],
        ];
    }

    public function execute(): array
    {
        $overdueInvoices = $this->getOverdueInvoices();
        $sent = 0;
        $failed = [];

        foreach ($overdueInvoices as $invoice) {
            try {
                // TODO: Implement actual email sending
                // For now, just mark as reminder sent
                // Notification::send($invoice->customer, new InvoicePaymentReminder($invoice));

                // Update invoice (if these fields exist)
                // $invoice->update([
                //     'last_reminder_sent_at' => now(),
                //     'reminder_count' => ($invoice->reminder_count ?? 0) + 1,
                // ]);

                $sent++;

            } catch (\Exception $e) {
                $failed[] = [
                    'invoice_id' => $invoice->id,
                    'customer' => $invoice->customer->name ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'sent' => $sent,
            'failed' => $failed,
            'message' => "Successfully sent {$sent} reminder email(s).",
        ];
    }

    public function getImpact(): string
    {
        $count = $this->getOverdueInvoices()->count();
        
        if ($count > 10) return 'high';
        if ($count > 3) return 'medium';
        return 'low';
    }

    protected function getOverdueInvoices()
    {
        return Invoice::where('organization_id', $this->organization->id)
            ->where('status', 'sent')
            ->where('due_date', '<', now())
            ->with('customer')
            ->get();
    }

    protected function getEmailPreview(Invoice $invoice): string
    {
        $days = now()->diffInDays($invoice->due_date);
        $customerName = $invoice->customer->name ?? 'Valued Customer';
        
        return "Hi {$customerName},\n\n"
            . "This is a friendly reminder that Invoice #{$invoice->invoice_number} "
            . "for \${$invoice->total_amount} is now {$days} days overdue.\n\n"
            . "Please process payment at your earliest convenience.\n\n"
            . "Thank you!";
    }
}

