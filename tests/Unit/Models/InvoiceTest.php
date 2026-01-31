<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\GoodsAndService;
use App\Models\PaymentAllocation;
use Illuminate\Support\Str;

class InvoiceTest extends TestCase
{
    /** @test */
    public function it_generates_invoice_number_automatically(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 1000,
            'tax_amount' => 160,
            'discount_amount' => 0,
            'total_amount' => 1160,
            'status' => 'draft',
        ]);

        $this->assertNotNull($invoice->invoice_number);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
    }

    /** @test */
    public function it_generates_unique_invoice_numbers_for_same_day(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice1 = Invoice::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 1000,
            'total_amount' => 1000,
            'status' => 'draft',
        ]);

        $invoice2 = Invoice::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 2000,
            'total_amount' => 2000,
            'status' => 'draft',
        ]);

        $this->assertNotEquals($invoice1->invoice_number, $invoice2->invoice_number);
    }

    /** @test */
    public function it_calculates_balance_correctly(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'total_amount' => 1000,
            'paid_amount' => 300,
        ]);

        $this->assertEquals(700, $invoice->balance);
        $this->assertEquals(700, $invoice->amount_due);
    }

    /** @test */
    public function it_correctly_identifies_overdue_invoices(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $overdueInvoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'due_date' => now()->subDays(5),
            'status' => 'sent',
        ]);

        $currentInvoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'due_date' => now()->addDays(5),
            'status' => 'sent',
        ]);

        $paidInvoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'due_date' => now()->subDays(5),
            'status' => 'paid',
        ]);

        $this->assertTrue($overdueInvoice->is_overdue);
        $this->assertFalse($currentInvoice->is_overdue);
        $this->assertFalse($paidInvoice->is_overdue);
    }

    /** @test */
    public function it_calculates_totals_correctly(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'subtotal' => 0,
            'tax_amount' => 160,
            'discount_amount' => 100,
            'total_amount' => 0,
        ]);

        // Create invoice items
        InvoiceItem::create([
            'id' => (string) Str::uuid(),
            'invoice_id' => $invoice->id,
            'name' => 'Item 1',
            'quantity' => 2,
            'unit_price' => 500,
            'total' => 1000,
        ]);

        InvoiceItem::create([
            'id' => (string) Str::uuid(),
            'invoice_id' => $invoice->id,
            'name' => 'Item 2',
            'quantity' => 1,
            'unit_price' => 500,
            'total' => 500,
        ]);

        $invoice->calculateTotals();

        // Subtotal: 1000 + 500 = 1500
        // After discount: 1500 - 100 = 1400
        // After tax: 1400 + 160 = 1560
        $this->assertEquals(1500, $invoice->subtotal);
        $this->assertEquals(1560, $invoice->total_amount);
    }

    /** @test */
    public function it_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertInstanceOf(Customer::class, $invoice->customer);
        $this->assertEquals($customer->id, $invoice->customer->id);
    }

    /** @test */
    public function it_has_many_items(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        InvoiceItem::create([
            'id' => (string) Str::uuid(),
            'invoice_id' => $invoice->id,
            'name' => 'Test Item',
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $this->assertCount(1, $invoice->items);
        $this->assertInstanceOf(InvoiceItem::class, $invoice->items->first());
    }

    /** @test */
    public function it_can_belong_to_quote(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $quote = Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'quote_id' => $quote->id,
        ]);

        $this->assertInstanceOf(Quote::class, $invoice->quote);
        $this->assertEquals($quote->id, $invoice->quote->id);
    }

    /** @test */
    public function it_sets_default_currency_from_organization(): void
    {
        $this->testOrganization->update(['currency' => 'USD']);

        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 1000,
            'total_amount' => 1000,
            'status' => 'draft',
        ]);

        $this->assertEquals('USD', $invoice->currency);
    }

    /** @test */
    public function it_respects_organization_isolation(): void
    {
        $otherOrg = $this->createOtherOrganization();

        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $otherCustomer = Customer::factory()->create([
            'organization_id' => $otherOrg->id,
        ]);

        Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        Invoice::factory()->create([
            'organization_id' => $otherOrg->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $testOrgInvoices = Invoice::where('organization_id', $this->testOrganization->id)->get();
        $otherOrgInvoices = Invoice::where('organization_id', $otherOrg->id)->get();

        $this->assertCount(1, $testOrgInvoices);
        $this->assertCount(1, $otherOrgInvoices);
    }

    /** @test */
    public function it_casts_dates_correctly(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'invoice_date' => '2024-01-15',
            'due_date' => '2024-02-15',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $invoice->invoice_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $invoice->due_date);
    }

    /** @test */
    public function it_casts_amounts_as_decimal(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'subtotal' => 1000.50,
            'tax_amount' => 160.08,
            'total_amount' => 1160.58,
        ]);

        $this->assertEquals('1000.50', $invoice->subtotal);
        $this->assertEquals('160.08', $invoice->tax_amount);
        $this->assertEquals('1160.58', $invoice->total_amount);
    }

    /** @test */
    public function it_handles_recurring_invoice_fields(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'is_recurring' => true,
            'recurrence_frequency' => 'monthly',
            'recurrence_day' => 15,
            'next_invoice_date' => now()->addMonth(),
        ]);

        $this->assertTrue($invoice->is_recurring);
        $this->assertEquals('monthly', $invoice->recurrence_frequency);
        $this->assertEquals(15, $invoice->recurrence_day);
    }

    /** @test */
    public function it_can_have_parent_invoice(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $parentInvoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'is_recurring' => true,
        ]);

        $childInvoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'parent_invoice_id' => $parentInvoice->id,
        ]);

        $this->assertInstanceOf(Invoice::class, $childInvoice->parentInvoice);
        $this->assertEquals($parentInvoice->id, $childInvoice->parentInvoice->id);
    }
}
