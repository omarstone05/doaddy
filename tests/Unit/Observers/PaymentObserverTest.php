<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\MoneyAccount;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use App\Jobs\RegenerateAddyInsights;

class PaymentObserverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Cache::flush();
    }

    /** @test */
    public function it_clears_sales_agent_cache_on_create(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Set cache
        $cacheKey = "addy_agent_SalesAgent_{$this->testOrganization->id}";
        Cache::put($cacheKey, ['test' => 'data'], 3600);

        Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $account->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);

        // Cache should be cleared
        $this->assertNull(Cache::get($cacheKey));
    }

    /** @test */
    public function it_dispatches_insight_regeneration_on_create(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $account->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
        ]);

        Queue::assertPushed(RegenerateAddyInsights::class);
    }

    /** @test */
    public function it_clears_cache_on_update(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $account->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);

        Queue::fake(); // Reset

        // Set cache
        $cacheKey = "addy_agent_SalesAgent_{$this->testOrganization->id}";
        Cache::put($cacheKey, ['test' => 'data'], 3600);

        $payment->update(['notes' => 'Updated notes']);

        // Cache should be cleared
        $this->assertNull(Cache::get($cacheKey));
    }

    /** @test */
    public function it_clears_cache_on_delete(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $payment = Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $account->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);

        Queue::fake(); // Reset

        // Set cache
        $cacheKey = "addy_agent_SalesAgent_{$this->testOrganization->id}";
        Cache::put($cacheKey, ['test' => 'data'], 3600);

        $payment->delete();

        // Cache should be cleared
        $this->assertNull(Cache::get($cacheKey));
    }

    /** @test */
    public function it_clears_organization_cache(): void
    {
        $customer = Customer::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Set organization cache
        $orgCacheKey = "addy_org_{$this->testOrganization->id}";
        Cache::put($orgCacheKey, ['test' => 'org_data'], 3600);

        Payment::create([
            'organization_id' => $this->testOrganization->id,
            'customer_id' => $customer->id,
            'money_account_id' => $account->id,
            'amount' => 1000,
            'currency' => 'ZMW',
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);

        // Organization cache should be cleared
        $this->assertNull(Cache::get($orgCacheKey));
    }
}
