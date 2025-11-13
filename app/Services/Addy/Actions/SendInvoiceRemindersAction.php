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
            'description' => $overdueInvoices->isEmpty()
                ? 'No overdue invoices meet the criteria.'
                : "Send reminder emails to {$overdueInvoices->count()} customer(s) with overdue invoices.",
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
        $channel = $this->parameters['channel'] ?? 'email';
        $note = $this->parameters['note'] ?? null;

        foreach ($overdueInvoices as $invoice) {
            try {
                $invoice->forceFill([
                    'last_reminder_sent_at' => now(),
                    'reminder_count' => ($invoice->reminder_count ?? 0) + 1,
                    'last_reminder_channel' => $channel,
                    'last_reminder_notes' => $note,
                    'status' => $invoice->is_overdue ? 'overdue' : $invoice->status,
                ])->save();

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
            'message' => $sent === 0
                ? 'No invoices met the reminder criteria.'
                : "Logged {$sent} reminder(s) via {$channel}. Notifications can be sent from the Reminders queue.",
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
        $limit = (int) ($this->parameters['limit'] ?? 10);

        $query = Invoice::with('customer')
            ->where('organization_id', $this->organization->id)
            ->whereIn('status', ['sent', 'overdue'])
            ->where('due_date', '<', now());

        if (!empty($this->parameters['invoice_number'])) {
            $query->where('invoice_number', 'like', '%' . strtoupper($this->parameters['invoice_number']) . '%');
        }

        if (!empty($this->parameters['customer_name'])) {
            $name = trim($this->parameters['customer_name']);
            $query->whereHas('customer', function ($q) use ($name) {
                $q->where('name', 'like', '%' . $name . '%');
            });
        }

        if (!empty($this->parameters['min_days_overdue'])) {
            $days = (int) $this->parameters['min_days_overdue'];
            $query->where('due_date', '<=', now()->subDays($days));
        }

        return $query->orderBy('due_date')->limit(max(1, $limit))->get();
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
