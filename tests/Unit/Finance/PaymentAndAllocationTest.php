<?php

namespace Tests\Unit\Finance;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAndAllocationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function payment_generates_numbers_and_receipt_and_supports_unallocated_amount(): void
    {
        $customer = Customer::factory()->create();

        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'organization_id' => $customer->organization_id,
            'amount' => 250,
        ]);

        $this->assertNotNull($payment->payment_number);
        $this->assertNotNull($payment->receipts()->first());
        $this->assertEquals(250.00, (float) $payment->amount);
        $this->assertEquals(250.00, (float) $payment->unallocated_amount);
    }

    /** @test */
    public function allocations_update_invoice_paid_amount_and_status(): void
    {
        $customer = Customer::factory()->create();

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'organization_id' => $customer->organization_id,
            'total_amount' => 150,
            'paid_amount' => 0,
            'status' => 'sent',
        ]);

        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'organization_id' => $customer->organization_id,
            'amount' => 150,
        ]);

        // First allocation
        PaymentAllocation::factory()->create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 50,
        ]);

        $invoice->refresh();
        $this->assertEquals(50.00, (float) $invoice->paid_amount);
        $this->assertEquals('sent', $invoice->status);

        // Second allocation to fully pay
        PaymentAllocation::factory()->create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 100,
        ]);

        $invoice->refresh();
        $this->assertEquals(150.00, (float) $invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);

        // Deleting an allocation rolls back paid amount
        $allocation = PaymentAllocation::where('invoice_id', $invoice->id)->first();
        $allocation->delete();

        $invoice->refresh();
        $this->assertEquals(100.00, (float) $invoice->paid_amount);
        $this->assertEquals('sent', $invoice->status);
    }
}
