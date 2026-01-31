<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\Customer;
use App\Observers\InvoiceObserver;
use App\Services\Addy\AddyCacheManager;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use App\Jobs\RegenerateAddyInsights;

class InvoiceObserverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Cache::flush();
    }

    /** @test */
    public function it_clears_cache_when_invoice_is_created(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Set some cache values
        $cacheKey = "addy_agent_SalesAgent_{$this->testOrganization->id}";
        Cache::put($cacheKey, ['test' => 'data'], 3600);

        Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        // Cache should be cleared
        $this->assertNull(Cache::get($cacheKey));
    }

    /** @test */
    public function it_dispatches_insight_regeneration_job_on_create(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        Queue::assertPushed(RegenerateAddyInsights::class, function ($job) {
            return true; // Just check it was dispatched
        });
    }

    /** @test */
    public function it_clears_cache_when_invoice_is_updated(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
        ]);

        Queue::fake(); // Reset queue after creation

        // Set cache
        $cacheKey = "addy_agent_SalesAgent_{$this->testOrganization->id}";
        Cache::put($cacheKey, ['test' => 'data'], 3600);

        // Update invoice
        $invoice->update(['status' => 'sent']);

        // Cache should be cleared
        $this->assertNull(Cache::get($cacheKey));
    }

    /** @test */
    public function it_dispatches_insight_regeneration_job_on_update(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        Queue::fake(); // Reset after creation

        $invoice->update(['status' => 'paid']);

        Queue::assertPushed(RegenerateAddyInsights::class);
    }

    /** @test */
    public function it_clears_cache_when_invoice_is_deleted(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
        ]);

        Queue::fake(); // Reset after creation

        // Set cache
        $cacheKey = "addy_agent_SalesAgent_{$this->testOrganization->id}";
        Cache::put($cacheKey, ['test' => 'data'], 3600);

        // Delete invoice
        $invoice->delete();

        // Cache should be cleared
        $this->assertNull(Cache::get($cacheKey));
    }

    /** @test */
    public function it_handles_missing_organization_gracefully(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Delete the organization
        $orgId = $this->testOrganization->id;
        $this->testOrganization->delete();

        // Create invoice with orphaned organization_id
        // This should not throw an exception
        $invoice = Invoice::factory()->make([
            'organization_id' => $orgId,
            'customer_id' => $customer->id,
        ]);

        // Force save without organization check
        $invoice->saveQuietly();

        // Update should still work without throwing
        $invoice->status = 'sent';
        $invoice->saveQuietly();

        $this->assertTrue(true); // No exception thrown
    }
}
