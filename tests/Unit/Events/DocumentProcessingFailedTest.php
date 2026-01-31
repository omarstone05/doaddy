<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Events\DocumentProcessingFailed;
use Illuminate\Broadcasting\Channel;

class DocumentProcessingFailedTest extends TestCase
{
    /** @test */
    public function it_stores_job_id_user_id_and_error(): void
    {
        $jobId = 'job-123-456';
        $userId = 1;
        $error = 'File not found';

        $event = new DocumentProcessingFailed($jobId, $userId, $error);

        $this->assertEquals($jobId, $event->jobId);
        $this->assertEquals($userId, $event->userId);
        $this->assertEquals($error, $event->error);
    }

    /** @test */
    public function it_broadcasts_on_user_channel(): void
    {
        $userId = 42;
        $event = new DocumentProcessingFailed('job-id', $userId, 'error');

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertEquals('user.42', $channel->name);
    }

    /** @test */
    public function it_has_correct_broadcast_name(): void
    {
        $event = new DocumentProcessingFailed('job-id', 1, 'error');

        $this->assertEquals('document.processing.failed', $event->broadcastAs());
    }

    /** @test */
    public function it_broadcasts_correct_data(): void
    {
        $jobId = 'job-abc-123';
        $error = 'Document parsing failed: Invalid format';

        $event = new DocumentProcessingFailed($jobId, 1, $error);

        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('job_id', $broadcastData);
        $this->assertArrayHasKey('error', $broadcastData);
        $this->assertEquals($jobId, $broadcastData['job_id']);
        $this->assertEquals($error, $broadcastData['error']);
    }

    /** @test */
    public function it_does_not_include_user_id_in_broadcast_data(): void
    {
        $event = new DocumentProcessingFailed('job-id', 42, 'error');

        $broadcastData = $event->broadcastWith();

        $this->assertArrayNotHasKey('user_id', $broadcastData);
    }

    /** @test */
    public function it_handles_long_error_messages(): void
    {
        $longError = str_repeat('Error details: ', 100);

        $event = new DocumentProcessingFailed('job-id', 1, $longError);

        $this->assertEquals($longError, $event->error);
        $this->assertEquals($longError, $event->broadcastWith()['error']);
    }

    /** @test */
    public function it_handles_empty_error_message(): void
    {
        $event = new DocumentProcessingFailed('job-id', 1, '');

        $this->assertEquals('', $event->error);
        $this->assertEquals('', $event->broadcastWith()['error']);
    }

    /** @test */
    public function it_handles_special_characters_in_error(): void
    {
        $errorWithSpecialChars = "Error: Invalid JSON at line 5\n\t\"key\": 'value'";

        $event = new DocumentProcessingFailed('job-id', 1, $errorWithSpecialChars);

        $this->assertEquals($errorWithSpecialChars, $event->error);
    }
}
