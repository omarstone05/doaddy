<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\MoneyMovement;
use App\Models\MoneyAccount;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use App\Jobs\RegenerateAddyInsights;

class MoneyMovementObserverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Cache::flush();
    }

    /** @test */
    public function it_clears_money_agent_cache_on_create(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Set cache
        $cacheKey = "addy_agent_MoneyAgent_{$this->testOrganization->id}";
        Cache::put($cacheKey, ['test' => 'data'], 3600);

        MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
        ]);

        // Cache should be cleared
        $this->assertNull(Cache::get($cacheKey));
    }

    /** @test */
    public function it_dispatches_insight_regeneration_job_on_create(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
        ]);

        Queue::assertPushed(RegenerateAddyInsights::class);
    }

    /** @test */
    public function it_clears_cache_on_update(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $movement = MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
            'status' => 'pending',
        ]);

        Queue::fake(); // Reset

        // Set cache
        $cacheKey = "addy_agent_MoneyAgent_{$this->testOrganization->id}";
        Cache::put($cacheKey, ['test' => 'data'], 3600);

        $movement->update(['status' => 'approved']);

        // Cache should be cleared
        $this->assertNull(Cache::get($cacheKey));
    }

    /** @test */
    public function it_clears_cache_on_delete(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $movement = MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
        ]);

        Queue::fake(); // Reset

        // Set cache
        $cacheKey = "addy_agent_MoneyAgent_{$this->testOrganization->id}";
        Cache::put($cacheKey, ['test' => 'data'], 3600);

        $movement->delete();

        // Cache should be cleared
        $this->assertNull(Cache::get($cacheKey));
    }

    /** @test */
    public function it_clears_organization_cache_on_changes(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Set organization-level cache
        $orgCacheKey = "addy_org_{$this->testOrganization->id}";
        Cache::put($orgCacheKey, ['test' => 'org_data'], 3600);

        MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
        ]);

        // Organization cache should be cleared
        $this->assertNull(Cache::get($orgCacheKey));
    }

    /** @test */
    public function it_delays_insight_regeneration_job(): void
    {
        $account = MoneyAccount::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'to_account_id' => $account->id,
        ]);

        Queue::assertPushed(RegenerateAddyInsights::class, function ($job) {
            // Check that job has a delay
            return $job->delay !== null;
        });
    }
}
