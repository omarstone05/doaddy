<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Events\DocumentProcessed;
use Illuminate\Broadcasting\Channel;

class DocumentProcessedTest extends TestCase
{
    /** @test */
    public function it_stores_job_id_user_id_and_result(): void
    {
        $jobId = 'job-123-456';
        $userId = 1;
        $result = ['status' => 'completed', 'data' => ['key' => 'value']];

        $event = new DocumentProcessed($jobId, $userId, $result);

        $this->assertEquals($jobId, $event->jobId);
        $this->assertEquals($userId, $event->userId);
        $this->assertEquals($result, $event->result);
    }

    /** @test */
    public function it_broadcasts_on_user_channel(): void
    {
        $userId = 42;
        $event = new DocumentProcessed('job-id', $userId, []);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertEquals('user.42', $channel->name);
    }

    /** @test */
    public function it_has_correct_broadcast_name(): void
    {
        $event = new DocumentProcessed('job-id', 1, []);

        $this->assertEquals('document.processed', $event->broadcastAs());
    }

    /** @test */
    public function it_broadcasts_correct_data(): void
    {
        $jobId = 'job-abc-123';
        $result = ['processed' => true, 'items_count' => 5];

        $event = new DocumentProcessed($jobId, 1, $result);

        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('job_id', $broadcastData);
        $this->assertArrayHasKey('result', $broadcastData);
        $this->assertEquals($jobId, $broadcastData['job_id']);
        $this->assertEquals($result, $broadcastData['result']);
    }

    /** @test */
    public function it_does_not_include_user_id_in_broadcast_data(): void
    {
        $event = new DocumentProcessed('job-id', 42, ['data' => 'test']);

        $broadcastData = $event->broadcastWith();

        $this->assertArrayNotHasKey('user_id', $broadcastData);
    }

    /** @test */
    public function it_handles_empty_result_array(): void
    {
        $event = new DocumentProcessed('job-id', 1, []);

        $this->assertEquals([], $event->result);
        $this->assertEquals([], $event->broadcastWith()['result']);
    }

    /** @test */
    public function it_handles_complex_result_data(): void
    {
        $complexResult = [
            'status' => 'completed',
            'documents' => [
                ['id' => 1, 'name' => 'doc1.pdf'],
                ['id' => 2, 'name' => 'doc2.pdf'],
            ],
            'metadata' => [
                'processed_at' => '2024-01-15',
                'processor' => 'ocr',
            ],
        ];

        $event = new DocumentProcessed('job-id', 1, $complexResult);

        $this->assertEquals($complexResult, $event->result);
        $this->assertEquals($complexResult, $event->broadcastWith()['result']);
    }
}
