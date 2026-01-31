<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;
use App\Listeners\SendSubscriptionRenewalEmail;
use App\Services\Admin\EmailService;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Mockery;
use Mockery\MockInterface;

class SendSubscriptionRenewalEmailTest extends TestCase
{
    protected MockInterface $emailService;
    protected SendSubscriptionRenewalEmail $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailService = Mockery::mock(EmailService::class);
        $this->listener = new SendSubscriptionRenewalEmail($this->emailService);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $emailService = Mockery::mock(EmailService::class);
        $listener = new SendSubscriptionRenewalEmail($emailService);

        $this->assertInstanceOf(SendSubscriptionRenewalEmail::class, $listener);
    }

    /** @test */
    public function it_handles_non_subscription_event_gracefully(): void
    {
        $this->emailService
            ->shouldNotReceive('sendFromTemplate')
            ->shouldNotReceive('send');

        $event = new \stdClass();
        $event->subscription = 'not a subscription object';

        // Should not throw exception
        $this->listener->handle($event);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_event_without_subscription_property(): void
    {
        $this->emailService
            ->shouldNotReceive('sendFromTemplate')
            ->shouldNotReceive('send');

        $event = new \stdClass();

        // Should not throw exception
        $this->listener->handle($event);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_null_subscription_gracefully(): void
    {
        $this->emailService
            ->shouldNotReceive('sendFromTemplate')
            ->shouldNotReceive('send');

        $event = new \stdClass();
        $event->subscription = null;

        // Should not throw exception
        $this->listener->handle($event);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_sends_renewal_email_for_valid_subscription(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Premium Plan',
        ]);

        $subscription = Subscription::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 99.99,
            'billing_period' => 'monthly',
            'ends_at' => now()->addMonth(),
        ]);

        $this->emailService
            ->shouldReceive('sendFromTemplate')
            ->once()
            ->andReturn(true);

        $event = new \stdClass();
        $event->subscription = $subscription;

        $this->listener->handle($event);
    }

    /** @test */
    public function it_falls_back_to_custom_email_when_template_not_found(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Basic Plan',
        ]);

        $subscription = Subscription::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 49.99,
            'billing_period' => 'yearly',
            'ends_at' => now()->addYear(),
        ]);

        $this->emailService
            ->shouldReceive('sendFromTemplate')
            ->once()
            ->andThrow(new \Exception('Template not found'));

        $this->emailService
            ->shouldReceive('send')
            ->once()
            ->andReturn(true);

        $event = new \stdClass();
        $event->subscription = $subscription;

        $this->listener->handle($event);
    }

    /** @test */
    public function it_logs_error_when_email_sending_fails(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::factory()->create([
            'organization_id' => $this->testOrganization->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => now()->addMonth(),
        ]);

        $this->emailService
            ->shouldReceive('sendFromTemplate')
            ->once()
            ->andThrow(new \Exception('SMTP connection failed'));

        $this->emailService
            ->shouldReceive('send')
            ->once()
            ->andThrow(new \Exception('SMTP connection failed'));

        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($subscription) {
                return $message === 'Failed to send subscription renewal email' &&
                       $context['subscription_id'] === $subscription->id;
            });

        $event = new \stdClass();
        $event->subscription = $subscription;

        $this->listener->handle($event);
    }

    /** @test */
    public function it_injects_email_service_dependency(): void
    {
        $emailService = Mockery::mock(EmailService::class);
        $listener = new SendSubscriptionRenewalEmail($emailService);

        $reflection = new \ReflectionClass($listener);
        $property = $reflection->getProperty('emailService');
        $property->setAccessible(true);

        $this->assertSame($emailService, $property->getValue($listener));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
