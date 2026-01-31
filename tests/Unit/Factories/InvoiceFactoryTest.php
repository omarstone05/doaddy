<?php

namespace Tests\Unit\Factories;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\Customer;

class InvoiceFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_invoice_with_default_values(): void
    {
        $invoice = Invoice::factory()->make([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertNotEmpty($invoice->invoice_number);
        $this->assertNotNull($invoice->invoice_date);
        $this->assertNotNull($invoice->due_date);
        $this->assertNotNull($invoice->subtotal);
        $this->assertNotNull($invoice->total_amount);
        $this->assertContains($invoice->status, ['draft', 'sent', 'paid', 'overdue', 'cancelled']);
    }

    /** @test */
    public function it_creates_invoice_in_database(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);
    }

    /** @test */
    public function it_creates_paid_invoice_using_state(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->paid()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals($invoice->total_amount, $invoice->paid_amount);
    }

    /** @test */
    public function it_creates_overdue_invoice_using_state(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->overdue()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertTrue($invoice->due_date->isPast());
    }

    /** @test */
    public function it_generates_unique_invoice_numbers(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice1 = Invoice::factory()->make([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $invoice2 = Invoice::factory()->make([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertNotEquals($invoice1->invoice_number, $invoice2->invoice_number);
    }

    /** @test */
    public function it_calculates_totals_correctly(): void
    {
        $invoice = Invoice::factory()->make([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Tax is 16% of subtotal
        $expectedTax = $invoice->subtotal * 0.16;
        $expectedTotal = $invoice->subtotal + $expectedTax;

        $this->assertEquals(round($expectedTax, 2), round($invoice->tax_amount, 2));
        $this->assertEquals(round($expectedTotal, 2), round($invoice->total_amount, 2));
    }

    /** @test */
    public function it_creates_multiple_invoices(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoices = Invoice::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertCount(3, $invoices);
        $this->assertCount(3, Invoice::where('customer_id', $customer->id)->get());
    }

    /** @test */
    public function it_creates_invoice_with_custom_organization(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertEquals($this->testOrganization->id, $invoice->organization_id);
    }

    /** @test */
    public function it_creates_invoice_with_zero_discount(): void
    {
        $invoice = Invoice::factory()->make([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertEquals(0, $invoice->discount_amount);
    }

    /** @test */
    public function it_creates_invoice_with_zero_paid_amount(): void
    {
        $invoice = Invoice::factory()->make([
            'organization_id' => $this->testOrganization->id,
            'status' => 'draft',
        ]);

        $this->assertEquals(0, $invoice->paid_amount);
    }
}
