<?php

namespace Tests\Unit\Factories;

use Tests\TestCase;
use App\Models\SubscriptionPlan;

class SubscriptionPlanFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_subscription_plan_with_default_values(): void
    {
        $plan = SubscriptionPlan::factory()->make();

        $this->assertNotEmpty($plan->name);
        $this->assertNotEmpty($plan->slug);
        $this->assertNotNull($plan->price);
        $this->assertNotNull($plan->billing_period);
        $this->assertTrue($plan->is_active);
    }

    /** @test */
    public function it_creates_subscription_plan_in_database(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'slug' => $plan->slug,
        ]);
    }

    /** @test */
    public function it_creates_inactive_plan_using_state(): void
    {
        $plan = SubscriptionPlan::factory()->inactive()->create();

        $this->assertFalse($plan->is_active);
    }

    /** @test */
    public function it_creates_free_plan_using_state(): void
    {
        $plan = SubscriptionPlan::factory()->free()->create();

        $this->assertEquals('Free', $plan->name);
        $this->assertEquals('free', $plan->slug);
        $this->assertEquals(0, $plan->price);
        $this->assertEquals(0, $plan->trial_days);
    }

    /** @test */
    public function it_creates_monthly_plan_using_state(): void
    {
        $plan = SubscriptionPlan::factory()->monthly()->create();

        $this->assertEquals('monthly', $plan->billing_period);
    }

    /** @test */
    public function it_creates_yearly_plan_using_state(): void
    {
        $plan = SubscriptionPlan::factory()->yearly()->create();

        $this->assertEquals('yearly', $plan->billing_period);
    }

    /** @test */
    public function it_creates_plan_with_features_array(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $this->assertIsArray($plan->features);
        $this->assertArrayHasKey('max_invoices', $plan->features);
        $this->assertArrayHasKey('max_customers', $plan->features);
    }

    /** @test */
    public function it_creates_multiple_plans(): void
    {
        $plans = SubscriptionPlan::factory()->count(5)->create();

        $this->assertCount(5, $plans);
    }

    /** @test */
    public function it_generates_unique_slugs(): void
    {
        $plan1 = SubscriptionPlan::factory()->create();
        $plan2 = SubscriptionPlan::factory()->create();

        $this->assertNotEquals($plan1->slug, $plan2->slug);
    }

    /** @test */
    public function it_creates_plan_with_valid_currency(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $this->assertContains($plan->currency, ['ZMW', 'USD', 'EUR']);
    }

    /** @test */
    public function it_creates_plan_with_trial_days(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $this->assertContains($plan->trial_days, [0, 7, 14, 30]);
    }

    /** @test */
    public function it_creates_plan_with_max_users(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $this->assertContains($plan->max_users, [1, 5, 10, 25, -1]);
    }

    /** @test */
    public function it_creates_plan_with_formatted_price(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price' => 99.99,
            'currency' => 'USD',
        ]);

        $this->assertEquals('99.99 USD', $plan->formatted_price);
    }
}
