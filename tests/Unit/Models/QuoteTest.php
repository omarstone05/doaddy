<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\GoodsAndService;
use Illuminate\Support\Str;

class QuoteTest extends TestCase
{
    /** @test */
    public function it_generates_quote_number_automatically(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $quote = Quote::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'quote_date' => now(),
            'expiry_date' => now()->addDays(30),
            'subtotal' => 1000,
            'total_amount' => 1000,
            'status' => 'draft',
        ]);

        $this->assertNotNull($quote->quote_number);
        $this->assertStringStartsWith('QUOTE-', $quote->quote_number);
    }

    /** @test */
    public function it_generates_unique_quote_numbers_for_same_day(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $quote1 = Quote::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'quote_date' => now(),
            'subtotal' => 1000,
            'total_amount' => 1000,
            'status' => 'draft',
        ]);

        $quote2 = Quote::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'quote_date' => now(),
            'subtotal' => 2000,
            'total_amount' => 2000,
            'status' => 'draft',
        ]);

        $this->assertNotEquals($quote1->quote_number, $quote2->quote_number);
    }

    /** @test */
    public function it_calculates_net_amount_correctly(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $quote = Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'total_amount' => 1160,
            'tax_amount' => 160,
            'discount_amount' => 100,
        ]);

        // Net amount = total - tax - discount = 1160 - 160 - 100 = 900
        $this->assertEquals(900, $quote->net_amount);
    }

    /** @test */
    public function it_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $quote = Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertInstanceOf(Customer::class, $quote->customer);
        $this->assertEquals($customer->id, $quote->customer->id);
    }

    /** @test */
    public function it_has_many_items(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $quote = Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        QuoteItem::create([
            'id' => (string) Str::uuid(),
            'quote_id' => $quote->id,
            'name' => 'Test Item',
            'quantity' => 2,
            'unit_price' => 100,
            'total' => 200,
        ]);

        QuoteItem::create([
            'id' => (string) Str::uuid(),
            'quote_id' => $quote->id,
            'name' => 'Another Item',
            'quantity' => 1,
            'unit_price' => 500,
            'total' => 500,
        ]);

        $this->assertCount(2, $quote->items);
    }

    /** @test */
    public function it_has_many_invoices(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $quote = Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        Invoice::factory()->count(2)->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'quote_id' => $quote->id,
        ]);

        $this->assertCount(2, $quote->invoices);
    }

    /** @test */
    public function it_sets_default_currency_from_organization(): void
    {
        $this->testOrganization->update(['currency' => 'USD']);

        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $quote = Quote::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'quote_date' => now(),
            'subtotal' => 1000,
            'total_amount' => 1000,
            'status' => 'draft',
        ]);

        $this->assertEquals('USD', $quote->currency);
    }

    /** @test */
    public function it_tracks_follow_up_information(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $quote = Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'follow_up_count' => 3,
            'last_follow_up_at' => now(),
            'last_follow_up_method' => 'email',
            'last_follow_up_notes' => 'Sent reminder about quote expiry',
        ]);

        $this->assertEquals(3, $quote->follow_up_count);
        $this->assertEquals('email', $quote->last_follow_up_method);
        $this->assertEquals('Sent reminder about quote expiry', $quote->last_follow_up_notes);
    }

    /** @test */
    public function it_casts_dates_correctly(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $quote = Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'quote_date' => '2024-01-15',
            'expiry_date' => '2024-02-15',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $quote->quote_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $quote->expiry_date);
    }

    /** @test */
    public function it_casts_amounts_as_decimal(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $quote = Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'subtotal' => 1000.50,
            'tax_amount' => 160.08,
            'total_amount' => 1160.58,
        ]);

        $this->assertEquals('1000.50', $quote->subtotal);
        $this->assertEquals('160.08', $quote->tax_amount);
        $this->assertEquals('1160.58', $quote->total_amount);
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

        Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        Quote::factory()->create([
            'organization_id' => $otherOrg->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $testOrgQuotes = Quote::where('organization_id', $this->testOrganization->id)->get();
        $otherOrgQuotes = Quote::where('organization_id', $otherOrg->id)->get();

        $this->assertCount(1, $testOrgQuotes);
        $this->assertCount(1, $otherOrgQuotes);
    }

    /** @test */
    public function it_handles_different_statuses(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $draftQuote = Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
        ]);

        $sentQuote = Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
        ]);

        $acceptedQuote = Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'status' => 'accepted',
        ]);

        $this->assertEquals('draft', $draftQuote->status);
        $this->assertEquals('sent', $sentQuote->status);
        $this->assertEquals('accepted', $acceptedQuote->status);
    }

    /** @test */
    public function it_stores_notes_and_terms(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $quote = Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'notes' => 'Special pricing for bulk order',
            'terms' => 'Payment due within 30 days of acceptance',
        ]);

        $this->assertEquals('Special pricing for bulk order', $quote->notes);
        $this->assertEquals('Payment due within 30 days of acceptance', $quote->terms);
    }
}
