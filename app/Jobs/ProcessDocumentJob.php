<?php

namespace App\Jobs;

use App\Agents\DocumentProcessingAgent;
use App\Models\DocumentProcessingJob as ProcessingJobModel;
use App\Services\Addy\DocumentProcessorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $jobId;
    public string $filePath;
    public string $organizationId;
    public int $userId;
    public array $metadata;

    /**
     * Number of times to attempt the job
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying
     */
    public int $backoff = 60;

    /**
     * Create a new job instance
     */
    public function __construct(
        string $jobId,
        string $filePath,
        string $organizationId,
        int $userId,
        array $metadata = []
    ) {
        $this->jobId = $jobId;
        $this->filePath = $filePath;
        $this->organizationId = $organizationId;
        $this->userId = $userId;
        $this->metadata = $metadata;
    }

    /**
     * Execute the job
     */
    public function handle(DocumentProcessorService $processor): void
    {
        Log::info('ProcessDocumentJob started', [
            'job_id' => $this->jobId,
            'file_path' => $this->filePath,
            'organization_id' => $this->organizationId,
        ]);

        try {
            // Create agent
            $agent = new DocumentProcessingAgent(
                $processor,
                $this->organizationId,
                $this->userId
            );

            // Process document
            $result = $agent->process($this->filePath, $this->metadata);

            Log::info('ProcessDocumentJob completed', [
                'job_id' => $this->jobId,
                'document_type' => $result['document_type'] ?? 'unknown',
                'confidence' => $result['confidence'] ?? 0,
                'requires_review' => $result['requires_review'] ?? false,
            ]);

            // Broadcast completion event
            broadcast(new \App\Events\DocumentProcessed(
                $this->jobId,
                $this->userId,
                $result
            ));

        } catch (\Exception $e) {
            Log::error('ProcessDocumentJob failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update job record as failed
            ProcessingJobModel::find($this->jobId)?->update([
                'status' => 'failed',
                'status_message' => $e->getMessage(),
                'error' => [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
                'completed_at' => now(),
            ]);

            // Broadcast failure event
            broadcast(new \App\Events\DocumentProcessingFailed(
                $this->jobId,
                $this->userId,
                $e->getMessage()
            ));

            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessDocumentJob permanently failed', [
            'job_id' => $this->jobId,
            'error' => $exception->getMessage(),
        ]);

        // Update job record
        ProcessingJobModel::find($this->jobId)?->update([
            'status' => 'failed',
            'status_message' => 'Permanently failed after ' . $this->tries . ' attempts',
            'error' => [
                'message' => $exception->getMessage(),
                'attempts' => $this->tries,
            ],
            'completed_at' => now(),
        ]);
    }
}

