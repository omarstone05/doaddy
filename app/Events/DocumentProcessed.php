<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $jobId;
    public int $userId;
    public array $result;

    public function __construct(string $jobId, int $userId, array $result)
    {
        $this->jobId = $jobId;
        $this->userId = $userId;
        $this->result = $result;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('user.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'document.processed';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->jobId,
            'result' => $this->result,
        ];
    }
}

