<?php

namespace App\Services\Addy\Actions;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\MoneyAccount;
use Illuminate\Support\Str;

class RecordInvoicePaymentAction extends BaseAction
{
    public function validate(): bool
    {
        return $this->resolveInvoice() !== null;
    }

    public function preview(): array
    {
        $invoice = $this->resolveInvoice(true);

        if (!$invoice) {
            throw new \Exception('Invoice not found. Please provide a valid invoice number.');
        }

        $amount = $this->prepareAmount($invoice);

        if ($amount <= 0) {
            throw new \Exception('Invoice is already fully paid.');
        }

        return [
            'title' => 'Record Invoice Payment',
            'description' => "Apply a payment of " . number_format($amount, 2) .
                " to invoice {$invoice->invoice_number}.",
            'items' => [[
                'invoice' => $invoice->invoice_number,
                'customer' => $invoice->customer->name ?? 'Unknown customer',
                'amount' => $amount,
                'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                'balance' => $invoice->balance,
            ]],
            'impact' => 'high',
            'warnings' => [],
        ];
    }

    public function execute(): array
    {
        $invoice = $this->resolveInvoice(true);

        if (!$invoice) {
            throw new \Exception('Invoice not found. Please double-check the invoice number.');
        }

        $amount = $this->prepareAmount($invoice);

        if ($amount <= 0) {
            throw new \Exception('Invoice is already fully paid.');
        }

        $paymentDate = $this->parameters['payment_date'] ?? now()->toDateString();
        $method = $this->parameters['payment_method'] ?? 'bank_transfer';
        $reference = $this->parameters['payment_reference'] ?? null;
        $accountId = $this->parameters['money_account_id'] ?? $this->getDefaultAccountId();

        if (!$accountId) {
            throw new \Exception('No money account available to record the payment.');
        }

        $payment = Payment::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->organization->id,
            'customer_id' => $invoice->customer_id,
            'amount' => $amount,
            'currency' => $this->organization->currency ?? 'ZMW',
            'payment_date' => $paymentDate,
            'payment_method' => $method,
            'payment_reference' => $reference,
            'money_account_id' => $accountId,
            'notes' => $this->parameters['notes'] ?? null,
        ]);

        PaymentAllocation::create([
            'id' => (string) Str::uuid(),
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
        ]);

        if ($invoice->balance <= 0) {
            $invoice->update(['paid_at' => now()]);
        }

        return [
            'success' => true,
            'message' => "Invoice {$invoice->invoice_number} marked as paid.",
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'paid_amount' => $amount,
            'remaining_balance' => $invoice->fresh()->balance,
        ];
    }

    protected function resolveInvoice(bool $withRelations = false): ?Invoice
    {
        $query = Invoice::where('organization_id', $this->organization->id);

        if ($withRelations) {
            $query->with('customer');
        }

        if (!empty($this->parameters['invoice_id'])) {
            return $query->where('id', $this->parameters['invoice_id'])->first();
        }

        if (!empty($this->parameters['invoice_number'])) {
            return $query->where('invoice_number', $this->parameters['invoice_number'])->first();
        }

        return null;
    }

    protected function prepareAmount(Invoice $invoice): float
    {
        $requested = isset($this->parameters['amount'])
            ? (float) $this->parameters['amount']
            : null;

        $balance = max(0, $invoice->balance);

        if ($requested === null) {
            return $balance;
        }

        if ($requested > $balance) {
            return $balance;
        }

        return $requested;
    }

    protected function getDefaultAccountId(): ?string
    {
        if (!empty($this->parameters['money_account_id'])) {
            return $this->parameters['money_account_id'];
        }

        $account = MoneyAccount::where('organization_id', $this->organization->id)
            ->where('is_active', true)
            ->first();

        if ($account) {
            return $account->id;
        }

        try {
            $account = MoneyAccount::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $this->organization->id,
                'name' => 'Default Account',
                'type' => 'bank',
                'currency' => $this->organization->currency ?? 'ZMW',
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create default money account', [
                'organization_id' => $this->organization->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        return $account?->id;
    }
}
