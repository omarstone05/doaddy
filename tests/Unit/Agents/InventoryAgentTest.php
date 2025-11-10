<?php

namespace Tests\Unit\Agents;

use Tests\TestCase;
use App\Services\Addy\Agents\InventoryAgent;
use App\Models\GoodsAndService;

class InventoryAgentTest extends TestCase
{
    protected InventoryAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = new InventoryAgent($this->testOrganization);
    }

    /** @test */
    public function it_perceives_stock_levels_correctly(): void
    {
        // Healthy stock (above reorder level)
        GoodsAndService::factory()->count(20)->create([
            'organization_id' => $this->testOrganization->id,
            'current_stock' => 50,
            'minimum_stock' => 20,
            'track_stock' => true,
        ]);

        // Low stock (at or below reorder level)
        GoodsAndService::factory()->count(5)->create([
            'organization_id' => $this->testOrganization->id,
            'current_stock' => 15,
            'minimum_stock' => 20,
            'track_stock' => true,
        ]);

        // Out of stock
        GoodsAndService::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
            'current_stock' => 0,
            'minimum_stock' => 20,
            'track_stock' => true,
        ]);

        $perception = $this->agent->perceive();

        $this->assertEquals(28, $perception['stock_levels']['total_products']);
        $this->assertEquals(20, $perception['stock_levels']['healthy']);
        $this->assertEquals(5, $perception['stock_levels']['low_stock']);
        $this->assertEquals(3, $perception['stock_levels']['out_of_stock']);
    }

    /** @test */
    public function it_detects_low_stock_items(): void
    {
        GoodsAndService::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
            'current_stock' => 5,
            'minimum_stock' => 10,
            'track_stock' => true,
        ]);

        $perception = $this->agent->perceive();

        $this->assertCount(3, $perception['low_stock_items']);
    }

    /** @test */
    public function it_detects_out_of_stock_items(): void
    {
        GoodsAndService::factory()->count(5)->create([
            'organization_id' => $this->testOrganization->id,
            'current_stock' => 0,
            'track_stock' => true,
        ]);

        $perception = $this->agent->perceive();

        $this->assertCount(5, $perception['out_of_stock']);
    }

    /** @test */
    public function it_only_perceives_own_organization_data(): void
    {
        $otherOrg = $this->createOtherOrganization();

        // Create product in other org
        GoodsAndService::factory()->create([
            'organization_id' => $otherOrg->id,
            'current_stock' => 100,
            'track_stock' => true,
        ]);

        // Create product in test org
        GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'current_stock' => 50,
            'track_stock' => true,
        ]);

        $perception = $this->agent->perceive();

        // Should only see test org's products
        $this->assertEquals(1, $perception['stock_levels']['total_products']);
    }
}

