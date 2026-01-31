<?php

namespace Tests\Unit\Sales;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationInvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_links_quotes_and_invoices_to_customers_and_tracks_amounts(): void
    {
        $customer = Customer::factory()->create(['name' => 'Quote Buyer']);

        $quote = Quote::factory()->create([
            'customer_id' => $customer->id,
            'organization_id' => $customer->organization_id,
            'subtotal' => 500,
            'tax_amount' => 80,
            'total_amount' => 580,
        ]);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'organization_id' => $customer->organization_id,
            'subtotal' => 500,
            'tax_amount' => 80,
            'total_amount' => 580,
            'status' => 'sent',
        ]);

        $this->assertEquals($customer->id, $quote->customer->id);
        $this->assertEquals($customer->id, $invoice->customer->id);
        $this->assertEquals(580.00, (float) $invoice->total_amount);
        $this->assertEquals('sent', $invoice->status);
    }
}
