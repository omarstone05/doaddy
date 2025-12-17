<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Organization;
use App\Models\Print\PrintJob;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;

/**
 * Seeder to link existing print jobs with quotations and invoices
 * 
 * This seeder creates relationships between:
 * - Print Jobs → Quotations (for quoted/approved jobs)
 * - Print Jobs → Invoices (for completed jobs)
 * - Quotations → Invoices (when quotation is converted)
 * 
 * Run after PrintShopDemoSeeder to populate relationships
 */
class PrintShopRelationshipsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔗 Linking Print Jobs to Quotations and Invoices...');
        
        $organization = Organization::where('slug', 'zambia-print-solutions')->first();
        
        if (!$organization) {
            $this->command->warn('   ⚠️  Zambia Print Solutions organization not found. Run PrintShopDemoSeeder first.');
            return;
        }

        $printJobs = PrintJob::where('organization_id', $organization->id)
            ->with(['customer', 'printMaterial'])
            ->get();

        if ($printJobs->isEmpty()) {
            $this->command->warn('   ⚠️  No print jobs found. Run PrintShopDemoSeeder first.');
            return;
        }

        $linked = 0;

        foreach ($printJobs as $job) {
            // Skip if already linked
            if ($job->quotation_id || $job->invoice_id) {
                continue;
            }

            // For completed jobs, create invoices (paid)
            if ($job->status === 'completed' && $job->customer_id) {
                $invoice = $this->createInvoiceFromJob($job, 'paid');
                if ($invoice) {
                    $job->update(['invoice_id' => $invoice->id]);
                    $linked++;
                    $this->command->info("   ✓ Linked job {$job->job_number} to invoice {$invoice->invoice_number} (paid)");
                }
            }
            // For in-progress jobs, create invoices (sent - work in progress)
            elseif ($job->status === 'in_progress' && $job->customer_id) {
                $invoice = $this->createInvoiceFromJob($job, 'sent');
                if ($invoice) {
                    $job->update(['invoice_id' => $invoice->id]);
                    $linked++;
                    $this->command->info("   ✓ Linked job {$job->job_number} to invoice {$invoice->invoice_number} (sent)");
                }
            }
            // For approved jobs, create quotations (draft - ready to send)
            elseif ($job->status === 'approved' && $job->customer_id) {
                $quotation = $this->createQuotationFromJob($job, 'draft');
                if ($quotation) {
                    $job->update([
                        'quotation_id' => $quotation->id,
                    ]);
                    $linked++;
                    $this->command->info("   ✓ Linked job {$job->job_number} to quotation {$quotation->quotation_number} (draft)");
                }
            }
            // For quoted jobs, create quotations (sent)
            elseif ($job->status === 'quoted' && $job->customer_id) {
                $quotation = $this->createQuotationFromJob($job, 'sent');
                if ($quotation) {
                    $job->update(['quotation_id' => $quotation->id]);
                    $linked++;
                    $this->command->info("   ✓ Linked job {$job->job_number} to quotation {$quotation->quotation_number} (sent)");
                }
            }
        }

        // Also create some quotations that convert to invoices (full workflow)
        $this->createQuotationToInvoiceWorkflows($organization);

        $this->command->info("✅ Linked {$linked} print jobs to quotations/invoices");
    }

    private function createQuotationFromJob(PrintJob $job, string $status = 'draft'): ?Quotation
    {
        try {
            $quotation = Quotation::create([
                'organization_id' => $job->organization_id,
                'customer_id' => $job->customer_id,
                'print_job_id' => $job->id,
                'created_by' => $job->created_by,
                'title' => "Print Job: {$job->job_number}",
                'description' => $job->notes ?? "Print job for {$job->printMaterial->name} - {$job->width}m x {$job->height}m x {$job->quantity}",
                'status' => $status,
                'issue_date' => $job->quoted_at ?? now(),
                'valid_until' => ($job->quoted_at ?? now())->addDays(30),
                'sent_at' => $status === 'sent' ? ($job->quoted_at ?? now()) : null,
                'subtotal' => $job->total_price,
                'tax_percentage' => 16.00,
                'tax_amount' => $job->total_price * 0.16,
                'total' => $job->grand_total,
                'currency' => 'ZMW',
                'payment_terms' => 'Net 30 days from invoice date',
                'terms_and_conditions' => "1. Prices valid for 30 days\n2. 50% deposit required to commence work\n3. Balance due on completion\n4. Installation included for Lusaka area",
            ]);

            // Create quotation item
            QuotationItem::create([
                'quotation_id' => $quotation->id,
                'order' => 1,
                'name' => "{$job->printMaterial->name} - {$job->width}m x {$job->height}m x {$job->quantity}",
                'description' => "Print job: {$job->job_number}",
                'quantity' => $job->total_area,
                'unit' => 'sqm',
                'unit_price' => $job->price_per_sqm,
                'total' => $job->total_price,
            ]);

            // Add additional costs as separate items
            $order = 2;
            if ($job->setup_cost > 0) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'order' => $order++,
                    'name' => 'Setup Fee',
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'unit_price' => $job->setup_cost,
                    'total' => $job->setup_cost,
                ]);
            }
            if ($job->finishing_cost > 0) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'order' => $order++,
                    'name' => 'Finishing',
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'unit_price' => $job->finishing_cost,
                    'total' => $job->finishing_cost,
                ]);
            }
            if ($job->delivery_cost > 0) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'order' => $order++,
                    'name' => 'Delivery',
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'unit_price' => $job->delivery_cost,
                    'total' => $job->delivery_cost,
                ]);
            }
            if ($job->other_costs > 0) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'order' => $order++,
                    'name' => 'Other Costs',
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'unit_price' => $job->other_costs,
                    'total' => $job->other_costs,
                ]);
            }

            return $quotation;
        } catch (\Exception $e) {
            $this->command->error("   ✗ Failed to create quotation for job {$job->job_number}: " . $e->getMessage());
            return null;
        }
    }

    private function createInvoiceFromJob(PrintJob $job, string $status = 'paid'): ?Invoice
    {
        try {
            $invoice = Invoice::create([
                'id' => Str::uuid(),
                'organization_id' => $job->organization_id,
                'customer_id' => $job->customer_id,
                'print_job_id' => $job->id,
                'invoice_date' => $job->completed_at ?? now()->subDays(5),
                'due_date' => ($job->completed_at ?? now()->subDays(5))->addDays(30),
                'subtotal' => $job->total_price,
                'tax_amount' => $job->total_price * 0.16,
                'total_amount' => $job->grand_total,
                'paid_amount' => $status === 'paid' ? $job->grand_total : 0,
                'paid_at' => $status === 'paid' ? ($job->completed_at ?? now()->subDays(2)) : null,
                'status' => $status,
                'notes' => "Invoice for print job: {$job->job_number}",
            ]);

            // Create invoice item
            InvoiceItem::create([
                'id' => Str::uuid(),
                'invoice_id' => $invoice->id,
                'name' => "{$job->printMaterial->name} - {$job->width}m x {$job->height}m x {$job->quantity}",
                'description' => "Print job: {$job->job_number}",
                'quantity' => $job->total_area,
                'unit_price' => $job->price_per_sqm,
                'total' => $job->total_price,
                'display_order' => 1,
            ]);

            // Add additional costs as separate items
            $displayOrder = 2;
            if ($job->setup_cost > 0) {
                InvoiceItem::create([
                    'id' => Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'name' => 'Setup Fee',
                    'quantity' => 1,
                    'unit_price' => $job->setup_cost,
                    'total' => $job->setup_cost,
                    'display_order' => $displayOrder++,
                ]);
            }
            if ($job->finishing_cost > 0) {
                InvoiceItem::create([
                    'id' => Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'name' => 'Finishing',
                    'quantity' => 1,
                    'unit_price' => $job->finishing_cost,
                    'total' => $job->finishing_cost,
                    'display_order' => $displayOrder++,
                ]);
            }
            if ($job->delivery_cost > 0) {
                InvoiceItem::create([
                    'id' => Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'name' => 'Delivery',
                    'quantity' => 1,
                    'unit_price' => $job->delivery_cost,
                    'total' => $job->delivery_cost,
                    'display_order' => $displayOrder++,
                ]);
            }
            if ($job->other_costs > 0) {
                InvoiceItem::create([
                    'id' => Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'name' => 'Other Costs',
                    'quantity' => 1,
                    'unit_price' => $job->other_costs,
                    'total' => $job->other_costs,
                    'display_order' => $displayOrder++,
                ]);
            }

            return $invoice;
        } catch (\Exception $e) {
            $this->command->error("   ✗ Failed to create invoice for job {$job->job_number}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create full workflow: Quotation → Invoice (converted)
     */
    private function createQuotationToInvoiceWorkflows(Organization $organization): void
    {
        // Find a quoted job that we can create a full workflow for
        $quotedJob = PrintJob::where('organization_id', $organization->id)
            ->where('status', 'quoted')
            ->whereNull('quotation_id')
            ->whereNull('invoice_id')
            ->whereNotNull('customer_id')
            ->first();

        if (!$quotedJob) {
            return;
        }

        try {
            // Create quotation
            $quotation = $this->createQuotationFromJob($quotedJob, 'sent');
            if (!$quotation) {
                return;
            }

            // Mark quotation as accepted
            $quotation->update([
                'status' => 'accepted',
                'responded_at' => now()->subDays(2),
            ]);

            // Create invoice from quotation
            $invoice = Invoice::create([
                'id' => Str::uuid(),
                'organization_id' => $organization->id,
                'customer_id' => $quotedJob->customer_id,
                'print_job_id' => $quotedJob->id,
                'invoice_date' => now()->subDays(2),
                'due_date' => now()->addDays(28),
                'subtotal' => $quotation->subtotal,
                'tax_amount' => $quotation->tax_amount,
                'total_amount' => $quotation->total,
                'status' => 'sent',
                'notes' => "Invoice converted from quotation {$quotation->quotation_number} for print job {$quotedJob->job_number}",
            ]);

            // Copy quotation items to invoice items
            foreach ($quotation->items as $index => $item) {
                InvoiceItem::create([
                    'id' => Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total,
                    'display_order' => $index + 1,
                ]);
            }

            // Link everything together
            $quotation->update([
                'converted_to_invoice_id' => $invoice->id,
                'converted_at' => now()->subDays(2),
            ]);

            $quotedJob->update([
                'quotation_id' => $quotation->id,
                'invoice_id' => $invoice->id,
                'status' => 'approved',
                'approved_at' => now()->subDays(2),
            ]);

            $this->command->info("   ✓ Created full workflow: Job {$quotedJob->job_number} → Quotation {$quotation->quotation_number} → Invoice {$invoice->invoice_number}");
        } catch (\Exception $e) {
            $this->command->error("   ✗ Failed to create quotation→invoice workflow: " . $e->getMessage());
        }
    }
}

