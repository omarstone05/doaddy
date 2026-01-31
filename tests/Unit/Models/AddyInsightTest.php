<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AddyInsight;
use App\Models\AddyState;
use App\Models\Organization;
use Carbon\Carbon;

class AddyInsightTest extends TestCase
{
    /** @test */
    public function it_can_create_insight_with_factory(): void
    {
        $insight = AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertDatabaseHas('addy_insights', [
            'id' => $insight->id,
            'organization_id' => $this->testOrganization->id,
        ]);
    }

    /** @test */
    public function it_belongs_to_organization(): void
    {
        $insight = AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $this->assertInstanceOf(Organization::class, $insight->organization);
        $this->assertEquals($this->testOrganization->id, $insight->organization->id);
    }

    /** @test */
    public function it_belongs_to_state(): void
    {
        $state = AddyState::factory()->create([
            'organization_id' => $this->testOrganization->id,
        ]);

        $insight = AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'addy_state_id' => $state->id,
        ]);

        $this->assertInstanceOf(AddyState::class, $insight->state);
        $this->assertEquals($state->id, $insight->state->id);
    }

    /** @test */
    public function it_casts_is_actionable_to_boolean(): void
    {
        $insight = AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'is_actionable' => true,
        ]);

        $this->assertIsBool($insight->is_actionable);
        $this->assertTrue($insight->is_actionable);
    }

    /** @test */
    public function it_casts_suggested_actions_to_array(): void
    {
        $actions = [
            ['type' => 'send_reminder', 'label' => 'Send Reminder'],
            ['type' => 'create_invoice', 'label' => 'Create Invoice'],
        ];

        $insight = AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'suggested_actions' => $actions,
        ]);

        $this->assertIsArray($insight->suggested_actions);
        $this->assertCount(2, $insight->suggested_actions);
        $this->assertEquals('send_reminder', $insight->suggested_actions[0]['type']);
    }

    /** @test */
    public function it_casts_datetime_fields(): void
    {
        $now = now();

        $insight = AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'dismissed_at' => $now,
            'completed_at' => $now,
            'expires_at' => $now->copy()->addDays(7),
        ]);

        $this->assertInstanceOf(Carbon::class, $insight->dismissed_at);
        $this->assertInstanceOf(Carbon::class, $insight->completed_at);
        $this->assertInstanceOf(Carbon::class, $insight->expires_at);
    }

    /** @test */
    public function active_returns_only_active_insights(): void
    {
        AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'active',
            'title' => 'Active Insight',
        ]);

        AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'dismissed',
            'title' => 'Dismissed Insight',
        ]);

        AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'completed',
            'title' => 'Completed Insight',
        ]);

        $activeInsights = AddyInsight::active($this->testOrganization->id)->get();

        $this->assertCount(1, $activeInsights);
        $this->assertEquals('Active Insight', $activeInsights->first()->title);
    }

    /** @test */
    public function active_excludes_expired_insights(): void
    {
        AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'active',
            'title' => 'Not Expired',
            'expires_at' => now()->addDays(7),
        ]);

        AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'active',
            'title' => 'Expired',
            'expires_at' => now()->subDay(),
        ]);

        $activeInsights = AddyInsight::active($this->testOrganization->id)->get();

        $this->assertCount(1, $activeInsights);
        $this->assertEquals('Not Expired', $activeInsights->first()->title);
    }

    /** @test */
    public function active_includes_insights_without_expiration(): void
    {
        AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'active',
            'title' => 'No Expiration',
            'expires_at' => null,
        ]);

        $activeInsights = AddyInsight::active($this->testOrganization->id)->get();

        $this->assertCount(1, $activeInsights);
        $this->assertEquals('No Expiration', $activeInsights->first()->title);
    }

    /** @test */
    public function active_orders_by_priority_then_created_at(): void
    {
        AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'active',
            'title' => 'Low Priority',
            'priority' => 0.3,
            'created_at' => now()->subMinutes(5),
        ]);

        AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'active',
            'title' => 'High Priority',
            'priority' => 0.9,
            'created_at' => now()->subMinutes(10),
        ]);

        AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'active',
            'title' => 'High Priority Recent',
            'priority' => 0.9,
            'created_at' => now(),
        ]);

        $activeInsights = AddyInsight::active($this->testOrganization->id)->get();

        $this->assertEquals('High Priority Recent', $activeInsights[0]->title);
        $this->assertEquals('High Priority', $activeInsights[1]->title);
        $this->assertEquals('Low Priority', $activeInsights[2]->title);
    }

    /** @test */
    public function active_filters_by_organization(): void
    {
        $otherOrg = Organization::factory()->create();

        AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'active',
            'title' => 'Test Org Insight',
        ]);

        AddyInsight::factory()->create([
            'organization_id' => $otherOrg->id,
            'status' => 'active',
            'title' => 'Other Org Insight',
        ]);

        $activeInsights = AddyInsight::active($this->testOrganization->id)->get();

        $this->assertCount(1, $activeInsights);
        $this->assertEquals('Test Org Insight', $activeInsights->first()->title);
    }

    /** @test */
    public function dismiss_updates_status_and_timestamp(): void
    {
        $insight = AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'active',
            'dismissed_at' => null,
        ]);

        $this->assertEquals('active', $insight->status);
        $this->assertNull($insight->dismissed_at);

        $insight->dismiss();

        $insight->refresh();

        $this->assertEquals('dismissed', $insight->status);
        $this->assertNotNull($insight->dismissed_at);
        $this->assertInstanceOf(Carbon::class, $insight->dismissed_at);
    }

    /** @test */
    public function complete_updates_status_and_timestamp(): void
    {
        $insight = AddyInsight::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'status' => 'active',
            'completed_at' => null,
        ]);

        $this->assertEquals('active', $insight->status);
        $this->assertNull($insight->completed_at);

        $insight->complete();

        $insight->refresh();

        $this->assertEquals('completed', $insight->status);
        $this->assertNotNull($insight->completed_at);
        $this->assertInstanceOf(Carbon::class, $insight->completed_at);
    }

    /** @test */
    public function it_has_correct_insight_types(): void
    {
        $types = ['alert', 'suggestion', 'achievement', 'info'];

        foreach ($types as $type) {
            $insight = AddyInsight::factory()->create([
                'organization_id' => $this->testOrganization->id,
                'type' => $type,
            ]);

            $this->assertEquals($type, $insight->type);
        }
    }

    /** @test */
    public function it_has_correct_categories(): void
    {
        $categories = ['money', 'sales', 'people', 'inventory', 'cross-section'];

        foreach ($categories as $category) {
            $insight = AddyInsight::factory()->create([
                'organization_id' => $this->testOrganization->id,
                'category' => $category,
            ]);

            $this->assertEquals($category, $insight->category);
        }
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $insight = new AddyInsight();
        $fillable = $insight->getFillable();

        $expected = [
            'organization_id',
            'addy_state_id',
            'type',
            'category',
            'title',
            'description',
            'priority',
            'is_actionable',
            'suggested_actions',
            'action_url',
            'status',
            'dismissed_at',
            'completed_at',
            'expires_at',
        ];

        foreach ($expected as $field) {
            $this->assertTrue(in_array($field, $fillable), "Field $field should be fillable");
        }
    }
}
