<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\MoneyAccount;
use App\Services\Addy\ActionExecutionService;

class RecordInvoicePaymentActionTest extends TestCase
{
    /** @test */
    public function it_records_payment_and_marks_invoice_paid(): void
    {
        $this->authenticate();

        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'total_amount' => 500,
            'paid_amount' => 0,
            'status' => 'sent',
        ]);

        MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'is_active' => true,
        ]);

        $service = new ActionExecutionService($this->testOrganization, $this->testUser);
        $action = $service->prepareAction('record_invoice_payment', [
            'invoice_id' => $invoice->id,
            'amount' => 200,
        ]);

        $service->confirmAction($action);
        $service->executeAction($action);

        $invoice->refresh();

        $this->assertEquals(200, $invoice->paid_amount);
        $this->assertEquals('sent', $invoice->status);

        // pay remaining balance
        $action = $service->prepareAction('record_invoice_payment', [
            'invoice_id' => $invoice->id,
        ]);

        $service->confirmAction($action);
        $service->executeAction($action);

        $invoice->refresh();

        $this->assertEquals(500, $invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
    }
}
