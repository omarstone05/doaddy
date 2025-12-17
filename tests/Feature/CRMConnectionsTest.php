<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use App\Modules\CRM\Models\Contact;
use App\Modules\CRM\Models\Quote as CRMQuote;
use Tests\TestCase;

class CRMConnectionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Manually load CRM migrations for testing
        $this->artisan('migrate', [
            '--path' => 'app/Modules/CRM/Database/Migrations',
        ]);
        
        $this->authenticate();
    }

    /** @test */
    public function crm_contact_connects_to_existing_customer()
    {
        // Create existing customer
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);

        // Create CRM contact linked to customer
        $contact = Contact::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'full_name' => 'Test Customer',
            'phone_primary' => '1234567890',
            'email_primary' => 'test@example.com',
            'contact_type' => 'person',
            'owner_id' => $this->testUser->id,
        ]);

        // Test relationship: CRM Contact → Customer
        $this->assertNotNull($contact->customer);
        $this->assertEquals($customer->id, $contact->customer->id);
        $this->assertEquals('Test Customer', $contact->customer->name);

        // Test relationship: Customer → CRM Contact
        $this->assertNotNull($customer->crmContact);
        $this->assertEquals($contact->id, $customer->crmContact->id);
    }

    /** @test */
    public function crm_quote_connects_to_existing_quote()
    {
        // Create customer and contact
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $contact = Contact::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'full_name' => 'Test Contact',
            'phone_primary' => '1234567890',
            'contact_type' => 'person',
            'owner_id' => $this->testUser->id,
        ]);

        // Create existing quote
        $existingQuote = Quote::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'quote_number' => 'QUOTE-20250125-0001',
            'subtotal' => 1000.00,
            'tax_amount' => 160.00,
            'total_amount' => 1160.00,
        ]);

        // Create CRM quote linked to existing quote
        $crmQuote = CRMQuote::create([
            'organization_id' => $this->testOrganization->id,
            'contact_id' => $contact->id,
            'quote_number' => 'QUO-2025-000001',
            'quote_name' => 'Test CRM Quote',
            'existing_quote_id' => $existingQuote->id,
            'subtotal' => 1000.00,
            'tax_amount' => 160.00,
            'total_amount' => 1160.00,
            'quote_date' => now(),
            'expiry_date' => now()->addDays(30),
            'owner_id' => $this->testUser->id,
            'prepared_by' => $this->testUser->id,
        ]);

        // Test relationship: CRM Quote → Existing Quote
        $this->assertNotNull($crmQuote->existingQuote);
        $this->assertEquals($existingQuote->id, $crmQuote->existingQuote->id);

        // Test relationship: Existing Quote → CRM Quote
        $this->assertNotNull($existingQuote->crmQuote);
        $this->assertEquals($crmQuote->id, $existingQuote->crmQuote->id);
    }

    /** @test */
    public function crm_quote_connects_to_existing_invoice()
    {
        // Create customer and contact
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $contact = Contact::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'full_name' => 'Test Contact',
            'phone_primary' => '1234567890',
            'contact_type' => 'person',
            'owner_id' => $this->testUser->id,
        ]);

        // Create existing invoice
        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-20250125-0001',
            'subtotal' => 1000.00,
            'tax_amount' => 160.00,
            'total_amount' => 1160.00,
        ]);

        // Create CRM quote linked to invoice (converted)
        $crmQuote = CRMQuote::create([
            'organization_id' => $this->testOrganization->id,
            'contact_id' => $contact->id,
            'quote_number' => 'QUO-2025-000002',
            'quote_name' => 'Converted CRM Quote',
            'invoice_id' => $invoice->id,
            'converted_to_invoice' => true,
            'converted_at' => now(),
            'subtotal' => 1000.00,
            'tax_amount' => 160.00,
            'total_amount' => 1160.00,
            'quote_date' => now(),
            'expiry_date' => now()->addDays(30),
            'owner_id' => $this->testUser->id,
            'prepared_by' => $this->testUser->id,
        ]);

        // Test relationship: CRM Quote → Invoice
        $this->assertNotNull($crmQuote->invoice);
        $this->assertEquals($invoice->id, $crmQuote->invoice->id);

        // Test relationship: Invoice → CRM Quote
        $this->assertNotNull($invoice->crmQuote);
        $this->assertEquals($crmQuote->id, $invoice->crmQuote->id);
    }

    /** @test */
    public function crm_contact_to_customer_connection_works_bidirectionally()
    {
        // Create customer first
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'name' => 'Bidirectional Test',
        ]);

        // Create contact linked to customer
        $contact = Contact::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'full_name' => 'Bidirectional Test',
            'phone_primary' => '1234567890',
            'contact_type' => 'person',
            'owner_id' => $this->testUser->id,
        ]);

        // Test forward relationship
        $this->assertEquals($customer->id, $contact->customer->id);

        // Test reverse relationship
        $this->assertEquals($contact->id, $customer->crmContact->id);
    }
}

