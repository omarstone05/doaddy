<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerQuotationInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    /**
     * Make a request with organization context set (required by Customer/Quotation/Invoice controllers).
     */
    protected function withOrganization(): self
    {
        $this->withSession(['current_organization_id' => $this->testOrganization->id]);
        return $this;
    }

    // ==================== Customer Creation Tests ====================

    /** @test */
    public function customer_can_be_created_with_required_fields(): void
    {
        $response = $this->withOrganization()->post(route('customers.store'), [
            'type' => 'business',
            'name' => 'Acme Corp',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $customer = Customer::where('name', 'Acme Corp')->first();
        $this->assertNotNull($customer);
        $this->assertEquals($this->testOrganization->id, $customer->organization_id);
        $this->assertEquals('business', $customer->type);
        $this->assertEquals('net_30', $customer->payment_terms);
        $this->assertEquals('ZMW', $customer->currency);
    }

    /** @test */
    public function customer_creation_handles_empty_strings_for_optional_fields(): void
    {
        $response = $this->withOrganization()->post(route('customers.store'), [
            'type' => 'individual',
            'name' => 'John Doe',
            'payment_terms' => 'immediate',
            'currency' => 'ZMW',
            'customer_persona_id' => '',
            'credit_limit' => 0,
            'custom_payment_days' => '',
            'email' => '',
            'phone' => '',
            'website' => '',
            'tax_id' => '',
            'billing_address' => '',
            'shipping_address' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $customer = Customer::where('name', 'John Doe')->first();
        $this->assertNotNull($customer);
        $this->assertNull($customer->customer_persona_id);
        // credit_limit may be 0 when empty string is converted (DB default for NOT NULL)
        $this->assertTrue($customer->credit_limit === null || $customer->credit_limit == 0);
        $this->assertNull($customer->custom_payment_days);
    }

    /** @test */
    public function customer_creation_fails_without_required_fields(): void
    {
        $response = $this->withOrganization()->post(route('customers.store'), [
            'type' => 'business',
            'name' => '',
            'payment_terms' => 'net_30',
            'currency' => 'ZMW',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('customers', [
            'organization_id' => $this->testOrganization->id,
        ]);
    }

    /** @test */
    public function customer_index_is_accessible(): void
    {
        $response = $this->withOrganization()->get(route('customers.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function customer_show_displays_customer(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'name' => 'Test Customer',
        ]);

        $response = $this->withOrganization()->get(route('customers.show', $customer));

        $response->assertStatus(200);
    }

    // ==================== Quotation Tests ====================

    /** @test */
    public function quotation_can_be_created_with_customer(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $response = $this->withOrganization()->post(route('quotations.store'), [
            'customer_id' => $customer->id,
            'quote_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
            'items' => [
                [
                    'name' => 'Test Item',
                    'description' => 'Test description',
                    'quantity' => 2,
                    'unit_price' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $quotation = Quotation::where('customer_id', $customer->id)->first();
        $this->assertNotNull($quotation);
        $this->assertEquals($this->testOrganization->id, $quotation->organization_id);
        $this->assertCount(1, $quotation->items);
        $this->assertEquals('Test Item', $quotation->items->first()->name);
    }

    /** @test */
    public function quotation_convert_to_invoice_creates_invoice_with_uuid(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Create quotation via controller to avoid product_id FK issues in SQLite
        $storeResponse = $this->withOrganization()->post(route('quotations.store'), [
            'customer_id' => $customer->id,
            'quote_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
            'items' => [
                [
                    'name' => 'Line Item',
                    'description' => null,
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ]);

        $storeResponse->assertSessionHasNoErrors();
        $quotation = Quotation::where('customer_id', $customer->id)->first();
        $this->assertNotNull($quotation);

        $response = $this->post(route('quotations.convert', $quotation));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $quotation->refresh();
        $this->assertNotNull($quotation->converted_to_invoice_id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $quotation->converted_to_invoice_id
        );

        $invoice = Invoice::find($quotation->converted_to_invoice_id);
        $this->assertNotNull($invoice);
        $this->assertEquals($customer->id, $invoice->customer_id);
        $this->assertEquals(500, (float) $invoice->total_amount);
        $this->assertCount(1, $invoice->items);
    }

    /** @test */
    public function quotation_convert_fails_when_already_converted(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $quotation = Quotation::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'converted_to_invoice_id' => $invoice->id,
        ]);

        $response = $this->withOrganization()->post(route('quotations.convert', $quotation));

        $response->assertRedirect(route('quotations.show', $quotation));
        $response->assertSessionHas('info');
    }

    /** @test */
    public function quotation_index_is_accessible(): void
    {
        $response = $this->withOrganization()->get(route('quotations.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function quotation_show_displays_quotation(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $quotation = Quotation::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withOrganization()->get(route('quotations.show', $quotation));

        $response->assertStatus(200);
    }

    // ==================== Invoice Tests ====================

    /** @test */
    public function invoice_can_be_created(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $response = $this->withOrganization()->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => [
                [
                    'name' => 'Invoice Item',
                    'description' => 'Item description',
                    'quantity' => 3,
                    'unit_price' => 150,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);
        $invoice = Invoice::where('customer_id', $customer->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(450, (float) $invoice->subtotal);
        $this->assertCount(1, $invoice->items);
    }

    /** @test */
    public function invoice_index_is_accessible(): void
    {
        $response = $this->withOrganization()->get(route('invoices.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function invoice_show_displays_invoice(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withOrganization()->get(route('invoices.show', $invoice));

        $response->assertStatus(200);
    }

    /** @test */
    public function invoice_creation_fails_without_customer(): void
    {
        $response = $this->withOrganization()->post(route('invoices.store'), [
            'customer_id' => '',
            'invoice_date' => now()->toDateString(),
            'items' => [
                [
                    'name' => 'Item',
                    'quantity' => 1,
                    'unit_price' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('customer_id');
    }
}
