<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;
use App\Listeners\SendPaymentConfirmationEmail;
use App\Services\Admin\EmailService;
use App\Models\Payment;
use Mockery;
use Mockery\MockInterface;

class SendPaymentConfirmationEmailTest extends TestCase
{
    protected MockInterface $emailService;
    protected SendPaymentConfirmationEmail $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailService = Mockery::mock(EmailService::class);
        $this->listener = new SendPaymentConfirmationEmail($this->emailService);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $emailService = Mockery::mock(EmailService::class);
        $listener = new SendPaymentConfirmationEmail($emailService);

        $this->assertInstanceOf(SendPaymentConfirmationEmail::class, $listener);
    }

    /** @test */
    public function it_handles_non_payment_event_gracefully(): void
    {
        $this->emailService
            ->shouldNotReceive('sendFromTemplate')
            ->shouldNotReceive('send');

        $event = new \stdClass();
        $event->payment = 'not a payment object';

        // Should not throw exception
        $this->listener->handle($event);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_event_without_payment_property(): void
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
    public function it_handles_null_event_gracefully(): void
    {
        $this->emailService
            ->shouldNotReceive('sendFromTemplate')
            ->shouldNotReceive('send');

        $event = new \stdClass();
        $event->payment = null;

        // Should not throw exception
        $this->listener->handle($event);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_does_not_crash_with_empty_event(): void
    {
        $this->emailService
            ->shouldNotReceive('sendFromTemplate')
            ->shouldNotReceive('send');

        // Empty stdClass
        $event = new \stdClass();

        // Should not throw exception
        $this->listener->handle($event);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_injects_email_service_dependency(): void
    {
        $emailService = Mockery::mock(EmailService::class);
        $listener = new SendPaymentConfirmationEmail($emailService);

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
