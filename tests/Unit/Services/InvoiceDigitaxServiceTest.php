<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Modules\SmartInvoice\Models\DigitaxCredential;
use App\Modules\SmartInvoice\Services\InvoiceDigitaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceDigitaxServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceDigitaxService $service;
    protected Organization $organization;
    protected Customer $customer;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceDigitaxService();
        
        // Create test organization with unique slug
        $this->organization = Organization::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Organization',
            'slug' => 'test-org-' . Str::random(8),
            'currency' => 'ZMW',
            'country' => 'ZM',
        ]);

        // Create test customer
        $this->customer = Customer::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->organization->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
        ]);

        // Create test invoice
        $this->invoice = Invoice::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->organization->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-20260131-0001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 1000.00,
            'tax_amount' => 160.00,
            'discount_amount' => 0,
            'total_amount' => 1160.00,
            'status' => 'sent',
        ]);

        // Create invoice items
        InvoiceItem::create([
            'id' => (string) Str::uuid(),
            'invoice_id' => $this->invoice->id,
            'name' => 'Test Product',
            'description' => 'A test product',
            'quantity' => 2,
            'unit_price' => 500.00,
            'total' => 1000.00,
        ]);
    }

    /** @test */
    public function it_returns_disabled_when_no_credential(): void
    {
        $this->service->forOrganization($this->organization);

        $this->assertFalse($this->service->isEnabled());
        $this->assertFalse($this->service->isConfigured());
    }

    /** @test */
    public function it_returns_enabled_when_credential_exists_and_active(): void
    {
        // Create active credential
        DigitaxCredential::create([
            'organization_id' => $this->organization->id,
            'api_key' => 'test-serial',
            'api_secret' => 'test-tpin',
            'digitax_api_key' => 'api_key_test123',
            'environment' => 'sandbox',
            'is_active' => true,
            'test_result' => ['success' => true],
        ]);

        $this->service->forOrganization($this->organization);

        $this->assertTrue($this->service->isEnabled());
    }

    /** @test */
    public function it_fails_submission_when_not_enabled(): void
    {
        $this->service->forOrganization($this->organization);

        $result = $this->service->submitInvoice($this->invoice);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not enabled', $result['message']);
    }

    /** @test */
    public function it_prevents_duplicate_submission(): void
    {
        // Create credential
        DigitaxCredential::create([
            'organization_id' => $this->organization->id,
            'api_key' => 'test-serial',
            'api_secret' => 'test-tpin',
            'digitax_api_key' => 'api_key_test123',
            'environment' => 'sandbox',
            'is_active' => true,
        ]);

        // Mark invoice as already submitted
        $this->invoice->update([
            'digitax_sale_id' => 'existing-sale-id',
            'digitax_queue_status' => 'complete',
        ]);

        $this->service->forOrganization($this->organization);

        $result = $this->service->submitInvoice($this->invoice);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already been submitted', $result['message']);
        $this->assertEquals('existing-sale-id', $result['sale_id']);
    }

    /** @test */
    public function it_checks_if_invoice_is_submitted(): void
    {
        $this->assertFalse($this->invoice->isDigitaxSubmitted());

        $this->invoice->update(['digitax_sale_id' => 'test-sale-id']);

        $this->assertTrue($this->invoice->isDigitaxSubmitted());
    }

    /** @test */
    public function it_checks_if_invoice_has_receipt(): void
    {
        $this->assertFalse($this->invoice->hasDigitaxReceipt());

        $this->invoice->update([
            'digitax_queue_status' => 'complete',
            'digitax_receipt_url' => 'https://zra.gov.zm/verify/123',
        ]);

        $this->assertTrue($this->invoice->hasDigitaxReceipt());
    }

    /** @test */
    public function it_checks_if_invoice_is_processing(): void
    {
        $this->assertFalse($this->invoice->isDigitaxProcessing());

        $this->invoice->update(['digitax_queue_status' => 'queued']);
        $this->assertTrue($this->invoice->isDigitaxProcessing());

        $this->invoice->update(['digitax_queue_status' => 'processing']);
        $this->assertTrue($this->invoice->isDigitaxProcessing());

        $this->invoice->update(['digitax_queue_status' => 'complete']);
        $this->assertFalse($this->invoice->isDigitaxProcessing());
    }

    /** @test */
    public function it_checks_if_invoice_failed(): void
    {
        $this->assertFalse($this->invoice->isDigitaxFailed());

        $this->invoice->update(['digitax_queue_status' => 'failed']);

        $this->assertTrue($this->invoice->isDigitaxFailed());
    }

    /** @test */
    public function it_returns_null_for_status_when_not_submitted(): void
    {
        $this->service->forOrganization($this->organization);

        // Note: checkStatus requires a credential, so we test the response format
        $result = $this->service->checkStatus($this->invoice);

        $this->assertEquals('not_submitted', $result['status']);
        $this->assertFalse($result['complete']);
        $this->assertNull($result['receipt_url']);
    }

    /** @test */
    public function it_marks_invoice_as_failed(): void
    {
        $this->service->forOrganization($this->organization);

        $this->service->markAsFailed($this->invoice, 'Max retries exceeded');

        $this->invoice->refresh();

        $this->assertEquals('failed', $this->invoice->digitax_queue_status);
        $this->assertNotNull($this->invoice->digitax_error);
        $this->assertEquals('Max retries exceeded', $this->invoice->digitax_error['reason']);
    }

    /** @test */
    public function it_processes_completion_correctly(): void
    {
        $this->service->forOrganization($this->organization);

        $saleDetails = [
            'receipt_url' => 'https://zra.gov.zm/verify/ABC123',
            'receipt_number' => 'ZRA-2026-001',
            'serial_number' => 'SN-12345',
            'receipt_signature' => 'abc123signature',
        ];

        $this->service->processCompletion($this->invoice, $saleDetails);

        $this->invoice->refresh();

        $this->assertEquals('complete', $this->invoice->digitax_queue_status);
        $this->assertEquals('https://zra.gov.zm/verify/ABC123', $this->invoice->digitax_receipt_url);
        $this->assertEquals('ZRA-2026-001', $this->invoice->digitax_receipt_number);
        $this->assertEquals('SN-12345', $this->invoice->digitax_serial_number);
        $this->assertNotNull($this->invoice->digitax_completed_at);
    }

    /** @test */
    public function invoice_has_correct_digitax_field_casts(): void
    {
        $this->invoice->update([
            'digitax_response' => ['test' => 'data'],
            'digitax_error' => ['error' => 'test error'],
            'digitax_submitted_at' => now(),
            'digitax_completed_at' => now(),
            'digitax_retry_count' => 5,
        ]);

        $this->invoice->refresh();

        $this->assertIsArray($this->invoice->digitax_response);
        $this->assertIsArray($this->invoice->digitax_error);
        $this->assertInstanceOf(\Carbon\Carbon::class, $this->invoice->digitax_submitted_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $this->invoice->digitax_completed_at);
        $this->assertIsInt($this->invoice->digitax_retry_count);
    }
}
