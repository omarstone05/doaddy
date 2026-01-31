<?php

namespace Tests\Unit\Factories;

use Tests\TestCase;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;

class SubscriptionFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_subscription_with_default_values(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::factory()->make([
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertNotNull($subscription->status);
        $this->assertNotNull($subscription->amount);
        $this->assertNotNull($subscription->starts_at);
        $this->assertNotNull($subscription->ends_at);
    }

    /** @test */
    public function it_creates_subscription_in_database(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
        ]);
    }

    /** @test */
    public function it_creates_active_subscription_using_state(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::factory()->active()->create([
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertEquals('active', $subscription->status);
        $this->assertNull($subscription->cancelled_at);
        $this->assertTrue($subscription->ends_at->isFuture());
    }

    /** @test */
    public function it_creates_cancelled_subscription_using_state(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::factory()->cancelled()->create([
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertEquals('cancelled', $subscription->status);
        $this->assertNotNull($subscription->cancelled_at);
        $this->assertNotNull($subscription->cancellation_reason);
    }

    /** @test */
    public function it_creates_expired_subscription_using_state(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::factory()->expired()->create([
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertEquals('expired', $subscription->status);
        $this->assertTrue($subscription->ends_at->isPast());
    }

    /** @test */
    public function it_creates_trial_subscription_using_state(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::factory()->onTrial()->create([
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertEquals('active', $subscription->status);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertTrue($subscription->trial_ends_at->isFuture());
    }

    /** @test */
    public function it_creates_monthly_subscription_using_state(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::factory()->monthly()->create([
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertEquals('monthly', $subscription->billing_period);
    }

    /** @test */
    public function it_creates_yearly_subscription_using_state(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::factory()->yearly()->create([
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertEquals('yearly', $subscription->billing_period);
    }

    /** @test */
    public function it_creates_multiple_subscriptions(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $subscriptions = Subscription::factory()->count(3)->create([
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertCount(3, $subscriptions);
    }

    /** @test */
    public function it_subscription_belongs_to_organization(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertEquals($this->testOrganization->id, $subscription->organization->id);
    }

    /** @test */
    public function it_subscription_belongs_to_plan(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Test Plan',
        ]);

        $subscription = Subscription::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertEquals('Test Plan', $subscription->plan->name);
    }
}
