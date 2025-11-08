<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring';
    protected $description = 'Generate recurring invoices based on their schedule';

    public function handle()
    {
        $this->info('Generating recurring invoices...');

        $invoices = Invoice::where('is_recurring', true)
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('next_invoice_date')
            ->where('next_invoice_date', '<=', now()->toDateString())
            ->where(function ($query) {
                $query->whereNull('recurrence_end_date')
                      ->orWhere('recurrence_end_date', '>=', now()->toDateString());
            })
            ->with(['items', 'customer'])
            ->get();

        $generated = 0;

        foreach ($invoices as $parentInvoice) {
            DB::beginTransaction();
            try {
                // Calculate next invoice date
                $nextDate = $this->calculateNextInvoiceDate(
                    $parentInvoice->next_invoice_date,
                    $parentInvoice->recurrence_frequency,
                    $parentInvoice->recurrence_day
                );

                // Check if we should stop (end date reached)
                if ($parentInvoice->recurrence_end_date && $nextDate > $parentInvoice->recurrence_end_date) {
                    $parentInvoice->update(['is_recurring' => false]);
                    DB::commit();
                    continue;
                }

                // Create new invoice
                $newInvoice = Invoice::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $parentInvoice->organization_id,
                    'customer_id' => $parentInvoice->customer_id,
                    'invoice_date' => $parentInvoice->next_invoice_date,
                    'due_date' => $this->calculateDueDate($parentInvoice->next_invoice_date, $parentInvoice->due_date),
                    'subtotal' => $parentInvoice->subtotal,
                    'tax_amount' => $parentInvoice->tax_amount,
                    'discount_amount' => $parentInvoice->discount_amount,
                    'total_amount' => $parentInvoice->total_amount,
                    'status' => 'sent',
                    'notes' => $parentInvoice->notes,
                    'terms' => $parentInvoice->terms,
                    'is_recurring' => true,
                    'recurrence_frequency' => $parentInvoice->recurrence_frequency,
                    'recurrence_day' => $parentInvoice->recurrence_day,
                    'next_invoice_date' => $nextDate,
                    'recurrence_end_date' => $parentInvoice->recurrence_end_date,
                    'parent_invoice_id' => $parentInvoice->id,
                ]);

                // Copy invoice items
                foreach ($parentInvoice->items as $item) {
                    \App\Models\InvoiceItem::create([
                        'id' => (string) Str::uuid(),
                        'invoice_id' => $newInvoice->id,
                        'goods_service_id' => $item->goods_service_id,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total' => $item->total,
                        'display_order' => $item->display_order,
                    ]);
                }

                // Update parent invoice next date
                $parentInvoice->update(['next_invoice_date' => $nextDate]);

                DB::commit();
                $generated++;

                $this->info("Generated invoice {$newInvoice->invoice_number} from {$parentInvoice->invoice_number}");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to generate invoice for {$parentInvoice->invoice_number}: " . $e->getMessage());
            }
        }

        $this->info("Generated {$generated} recurring invoices.");
        return 0;
    }

    protected function calculateNextInvoiceDate($startDate, $frequency, $day): string
    {
        $date = \Carbon\Carbon::parse($startDate);
        
        switch ($frequency) {
            case 'weekly':
                return $date->addWeek()->toDateString();
            case 'monthly':
                if ($day) {
                    return $date->addMonth()->day($day)->toDateString();
                }
                return $date->addMonth()->toDateString();
            case 'quarterly':
                return $date->addMonths(3)->toDateString();
            case 'annually':
                return $date->addYear()->toDateString();
            default:
                return $date->toDateString();
        }
    }

    protected function calculateDueDate($invoiceDate, $originalDueDate): string
    {
        $invoice = \Carbon\Carbon::parse($invoiceDate);
        $original = \Carbon\Carbon::parse($originalDueDate);
        $diffDays = $invoice->diffInDays($original);
        
        return $invoice->copy()->addDays($diffDays)->toDateString();
    }
}

