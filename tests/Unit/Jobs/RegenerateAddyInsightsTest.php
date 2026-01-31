<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\RegenerateAddyInsights;
use App\Services\Addy\AddyCoreService;
use Illuminate\Support\Facades\Log;
use Mockery;

class RegenerateAddyInsightsTest extends TestCase
{
    /** @test */
    public function it_regenerates_insights_for_organization(): void
    {
        Log::spy();

        $job = new RegenerateAddyInsights($this->testOrganization);
        $job->handle();

        Log::shouldHaveReceived('debug')
            ->with(Mockery::pattern('/Regenerated Addy insights/'));
    }

    /** @test */
    public function it_logs_error_on_failure(): void
    {
        Log::spy();

        // Create a mock that will throw an exception
        $this->mock(AddyCoreService::class, function ($mock) {
            $mock->shouldReceive('regenerateInsights')
                ->andThrow(new \Exception('Test error'));
        });

        // The job should catch the exception and log it
        $job = new RegenerateAddyInsights($this->testOrganization);

        // Handle should not throw (it catches exceptions)
        $job->handle();

        // Should have logged an error
        Log::shouldHaveReceived('error');
    }

    /** @test */
    public function it_serializes_organization_correctly(): void
    {
        $job = new RegenerateAddyInsights($this->testOrganization);

        // Serialize and unserialize to simulate queue handling
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(RegenerateAddyInsights::class, $unserialized);
    }

    /** @test */
    public function it_is_queueable(): void
    {
        $job = new RegenerateAddyInsights($this->testOrganization);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** @test */
    public function it_can_be_dispatched(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        RegenerateAddyInsights::dispatch($this->testOrganization);

        \Illuminate\Support\Facades\Queue::assertPushed(RegenerateAddyInsights::class, function ($job) {
            return true;
        });
    }

    /** @test */
    public function it_can_be_dispatched_with_delay(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        RegenerateAddyInsights::dispatch($this->testOrganization)->delay(now()->addSeconds(5));

        \Illuminate\Support\Facades\Queue::assertPushed(RegenerateAddyInsights::class, function ($job) {
            return $job->delay !== null;
        });
    }
}
