<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Services\Addy\AddyCoreService;
use App\Models\AddyState;
use App\Models\AddyInsight;
use App\Models\MoneyMovement;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\GoodsAndService;

class AddyCoreServiceTest extends TestCase
{
    protected AddyCoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AddyCoreService($this->testOrganization);
    }

    /** @test */
    public function it_runs_decision_loop_successfully(): void
    {
        // Seed some test data
        MoneyMovement::factory()->count(5)->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        Invoice::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        // Run decision loop
        $this->service->runDecisionLoop();

        // Verify state was created
        $state = AddyState::where('organization_id', $this->testOrganization->id)->first();
        $this->assertNotNull($state);
        $this->assertNotNull($state->focus_area);
        $this->assertNotNull($state->context);
    }

    /** @test */
    public function it_generates_insights_from_agent_data(): void
    {
        // Create data that should trigger insights
        MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'flow_type' => 'expense',
            'amount' => 10000, // Large expense
        ]);

        Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'sent',
            'due_date' => now()->subDays(10), // Overdue
            'total_amount' => 5000,
        ]);

        $this->service->runDecisionLoop();

        // Verify insights were generated
        $insights = AddyInsight::where('organization_id', $this->testOrganization->id)
            ->where('status', 'active')
            ->get();

        $this->assertGreaterThan(0, $insights->count());
    }

    /** @test */
    public function it_generates_cross_section_insights(): void
    {
        // Create data across multiple sections
        MoneyMovement::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'flow_type' => 'expense',
            'amount' => 5000,
        ]);

        Invoice::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'paid',
            'total_amount' => 10000,
        ]);

        GoodsAndService::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'current_stock' => 0, // Out of stock
        ]);

        $this->service->runDecisionLoop();

        // Check for cross-section insights
        $insights = AddyInsight::where('organization_id', $this->testOrganization->id)
            ->where('status', 'active')
            ->get();

        // Should have insights from multiple categories
        $categories = $insights->pluck('category')->unique();
        $this->assertGreaterThan(1, $categories->count());
    }

    /** @test */
    public function it_updates_state_correctly(): void
    {
        $this->service->runDecisionLoop();

        $state = $this->service->getState();
        
        $this->assertNotNull($state);
        $this->assertIsString($state->focus_area);
        $this->assertIsNumeric($state->urgency); // Can be string from DB or float
        $this->assertIsString($state->context);
        $this->assertIsString($state->mood);
        $this->assertIsArray($state->priorities);
    }

    /** @test */
    public function it_returns_current_thought(): void
    {
        $this->service->runDecisionLoop();

        $thought = $this->service->getCurrentThought();

        $this->assertIsArray($thought);
        $this->assertArrayHasKey('state', $thought);
        $this->assertArrayHasKey('top_insight', $thought);
        $this->assertArrayHasKey('focus_area', $thought['state']);
        $this->assertArrayHasKey('urgency', $thought['state']);
        $this->assertArrayHasKey('context', $thought['state']);
        $this->assertArrayHasKey('mood', $thought['state']);
    }

    /** @test */
    public function it_returns_active_insights(): void
    {
        // Create some insights
        AddyInsight::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'active',
        ]);

        AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'dismissed',
        ]);

        $insights = $this->service->getActiveInsights();

        $this->assertCount(3, $insights);
        $this->assertTrue($insights->every(fn($insight) => $insight->status === 'active'));
    }
}

