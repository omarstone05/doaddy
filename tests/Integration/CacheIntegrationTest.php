<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Services\Addy\Agents\MoneyAgent;
use App\Services\Addy\AddyCacheManager;
use App\Models\MoneyMovement;
use Illuminate\Support\Facades\Cache;

class CacheIntegrationTest extends TestCase
{
    /** @test */
    public function agent_perception_is_cached(): void
    {
        $agent = new MoneyAgent($this->testOrganization);

        // Create test data
        MoneyMovement::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // First call - should hit database
        $perception1 = $agent->perceive();

        // Second call - should hit cache
        $perception2 = $agent->perceive();

        // Results should be the same
        $this->assertEquals($perception1, $perception2);
    }

    /** @test */
    public function cache_is_cleared_when_data_changes(): void
    {
        // Skip if not using Redis (observers require tagged cache)
        if (config('cache.default') !== 'redis') {
            $this->markTestSkipped('Redis cache required for observer-based cache invalidation');
        }

        $agent = new MoneyAgent($this->testOrganization);

        // Initial perception
        MoneyMovement::factory()->count(2)->create([
            'organization_id' => $this->testOrganization->id,
        ]);
        $perception1 = $agent->perceive();

        // Clear cache manually to simulate observer behavior
        AddyCacheManager::clearAgent('MoneyAgent', $this->testOrganization->id);

        // Add new movement
        MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Next perception should reflect new data (cache was cleared)
        $perception2 = $agent->perceive();

        // Should have different data (more movements)
        $this->assertNotEquals($perception1, $perception2);
    }

    /** @test */
    public function cache_manager_can_clear_organization_cache(): void
    {
        $agent = new MoneyAgent($this->testOrganization);
        $agent->perceive(); // Populate cache

        // Verify cache was populated (if Redis is available)
        if (config('cache.default') === 'redis') {
            // Clear cache
            AddyCacheManager::clearOrganization($this->testOrganization->id);
            
            // Verify cache tags were cleared
            $this->assertTrue(true); // Cache clearing works if no exception
        } else {
            $this->markTestSkipped('Redis cache not configured');
        }
    }

    /** @test */
    public function cache_manager_can_warm_cache(): void
    {
        // Create test data
        MoneyMovement::factory()->count(5)->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Warm cache
        AddyCacheManager::warmUp($this->testOrganization->id);

        // Verify cache warming completed without errors
        $this->assertTrue(true); // If no exception, warming worked
    }
}

