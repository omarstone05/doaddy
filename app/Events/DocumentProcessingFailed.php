<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentProcessingFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $jobId;
    public int $userId;
    public string $error;

    public function __construct(string $jobId, int $userId, string $error)
    {
        $this->jobId = $jobId;
        $this->userId = $userId;
        $this->error = $error;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('user.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'document.processing.failed';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->jobId,
            'error' => $this->error,
        ];
    }
}

