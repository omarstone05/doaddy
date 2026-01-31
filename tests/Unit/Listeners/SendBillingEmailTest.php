<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;
use App\Listeners\SendBillingEmail;

class SendBillingEmailTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated(): void
    {
        $listener = new SendBillingEmail();

        $this->assertInstanceOf(SendBillingEmail::class, $listener);
    }

    /** @test */
    public function it_handles_event_without_errors(): void
    {
        $listener = new SendBillingEmail();

        $event = new \stdClass();
        $event->data = ['test' => 'data'];

        // Should not throw exception - currently a stub implementation
        $listener->handle($event);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_empty_event(): void
    {
        $listener = new SendBillingEmail();

        $event = new \stdClass();

        // Should not throw exception
        $listener->handle($event);

        $this->assertTrue(true);
    }
}
