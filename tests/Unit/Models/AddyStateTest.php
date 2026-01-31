<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AddyState;
use App\Models\AddyInsight;
use App\Models\Organization;
use Carbon\Carbon;

class AddyStateTest extends TestCase
{
    /** @test */
    public function it_can_create_state_with_factory(): void
    {
        $state = AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertDatabaseHas('addy_states', [
            'id' => $state->id,
            'organization_id' => $this->testOrganization->id,
        ]);
    }

    /** @test */
    public function it_belongs_to_organization(): void
    {
        $state = AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertInstanceOf(Organization::class, $state->organization);
        $this->assertEquals($this->testOrganization->id, $state->organization->id);
    }

    /** @test */
    public function it_has_many_insights(): void
    {
        $state = AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        AddyInsight::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
            'addy_state_id' => $state->id,
        ]);

        $this->assertCount(3, $state->insights);
        $this->assertInstanceOf(AddyInsight::class, $state->insights->first());
    }

    /** @test */
    public function it_casts_perception_data_to_array(): void
    {
        $perceptionData = [
            'money' => ['cash_balance' => 10000, 'pending_invoices' => 5],
            'sales' => ['this_month' => 25000, 'last_month' => 22000],
        ];

        $state = AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'perception_data' => $perceptionData,
        ]);

        $this->assertIsArray($state->perception_data);
        $this->assertEquals(10000, $state->perception_data['money']['cash_balance']);
        $this->assertEquals(25000, $state->perception_data['sales']['this_month']);
    }

    /** @test */
    public function it_casts_priorities_to_array(): void
    {
        $priorities = [
            ['area' => 'money', 'priority' => 0.9, 'reason' => 'Low cash'],
            ['area' => 'sales', 'priority' => 0.7, 'reason' => 'Invoices pending'],
        ];

        $state = AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'priorities' => $priorities,
        ]);

        $this->assertIsArray($state->priorities);
        $this->assertCount(2, $state->priorities);
        $this->assertEquals('money', $state->priorities[0]['area']);
    }

    /** @test */
    public function it_casts_last_thought_cycle_to_datetime(): void
    {
        $state = AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'last_thought_cycle' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $state->last_thought_cycle);
    }

    /** @test */
    public function current_returns_latest_state_by_thought_cycle(): void
    {
        AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'focus_area' => 'Money',
            'last_thought_cycle' => now()->subDays(2),
        ]);

        $latestState = AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'focus_area' => 'Sales',
            'last_thought_cycle' => now(),
        ]);

        AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'focus_area' => 'People',
            'last_thought_cycle' => now()->subDay(),
        ]);

        $current = AddyState::current($this->testOrganization->id);

        $this->assertNotNull($current);
        $this->assertEquals($latestState->id, $current->id);
        $this->assertEquals('Sales', $current->focus_area);
    }

    /** @test */
    public function current_returns_null_when_no_states_exist(): void
    {
        $current = AddyState::current($this->testOrganization->id);

        $this->assertNull($current);
    }

    /** @test */
    public function current_filters_by_organization(): void
    {
        $otherOrg = Organization::factory()->create();

        AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'focus_area' => 'Money',
            'last_thought_cycle' => now(),
        ]);

        AddyState::factory()->create([
            'organization_id' => $otherOrg->id,
            'focus_area' => 'Sales',
            'last_thought_cycle' => now()->addHour(),
        ]);

        $current = AddyState::current($this->testOrganization->id);

        $this->assertNotNull($current);
        $this->assertEquals('Money', $current->focus_area);
    }

    /** @test */
    public function needs_thought_cycle_returns_true_when_no_last_cycle(): void
    {
        $state = AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'last_thought_cycle' => null,
        ]);

        $this->assertTrue($state->needsThoughtCycle());
    }

    /** @test */
    public function needs_thought_cycle_returns_true_when_over_24_hours(): void
    {
        $state = AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'last_thought_cycle' => now()->subHours(25),
        ]);

        $this->assertTrue($state->needsThoughtCycle());
    }

    /** @test */
    public function needs_thought_cycle_returns_false_when_under_24_hours(): void
    {
        $state = AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'last_thought_cycle' => now()->subHours(23),
        ]);

        $this->assertFalse($state->needsThoughtCycle());
    }

    /** @test */
    public function needs_thought_cycle_returns_true_at_exactly_24_hours(): void
    {
        $state = AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'last_thought_cycle' => now()->subHours(24),
        ]);

        $this->assertTrue($state->needsThoughtCycle());
    }

    /** @test */
    public function it_has_correct_focus_areas(): void
    {
        $focusAreas = ['Money', 'Sales', 'People', 'Inventory', 'Overview'];

        foreach ($focusAreas as $area) {
            $state = AddyState::factory()->create([
                'organization_id' => $this->testOrganization->id,
                'focus_area' => $area,
            ]);

            $this->assertEquals($area, $state->focus_area);
        }
    }

    /** @test */
    public function it_has_correct_moods(): void
    {
        $moods = ['neutral', 'concerned', 'optimistic', 'alert'];

        foreach ($moods as $mood) {
            $state = AddyState::factory()->create([
                'organization_id' => $this->testOrganization->id,
                'mood' => $mood,
            ]);

            $this->assertEquals($mood, $state->mood);
        }
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $state = new AddyState();
        $fillable = $state->getFillable();

        $expected = [
            'organization_id',
            'focus_area',
            'urgency',
            'context',
            'mood',
            'perception_data',
            'priorities',
            'last_thought_cycle',
        ];

        foreach ($expected as $field) {
            $this->assertTrue(in_array($field, $fillable), "Field $field should be fillable");
        }
    }

    /** @test */
    public function it_stores_decimal_urgency(): void
    {
        $state = AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'urgency' => 0.75,
        ]);

        $this->assertEquals(0.75, (float) $state->urgency);
    }
}
