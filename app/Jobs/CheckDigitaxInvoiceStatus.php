<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Modules\SmartInvoice\Services\InvoiceDigitaxService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Background job to poll DigiTax for invoice status.
 * 
 * Uses exponential backoff:
 * - Initial delay: 2 seconds
 * - Backoff: 2s, 4s, 8s, 16s, 30s (capped)
 * - Max attempts: 20 (~5 minutes total)
 * 
 * @see apps/addy/docs/DIGITAX_IMPLEMENTATION_GUIDE.md
 */
class CheckDigitaxInvoiceStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of polling attempts
     */
    public const MAX_ATTEMPTS = 20;

    /**
     * Maximum delay between attempts (seconds)
     */
    public const MAX_DELAY = 30;

    /**
     * The invoice to check
     */
    public Invoice $invoice;

    /**
     * Current attempt number
     */
    public int $attemptNumber;

    /**
     * Create a new job instance.
     */
    public function __construct(Invoice $invoice, int $attemptNumber = 1)
    {
        $this->invoice = $invoice;
        $this->attemptNumber = $attemptNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(InvoiceDigitaxService $digitaxService): void
    {
        // Refresh invoice from database
        $this->invoice->refresh();

        // Skip if already complete or failed
        if (in_array($this->invoice->digitax_queue_status, ['complete', 'failed'])) {
            Log::info('DigiTax status check skipped - already finalized', [
                'invoice_id' => $this->invoice->id,
                'status' => $this->invoice->digitax_queue_status,
            ]);
            return;
        }

        // Check if we've exceeded max attempts
        if ($this->attemptNumber > self::MAX_ATTEMPTS) {
            $digitaxService
                ->forOrganization($this->invoice->organization)
                ->markAsFailed($this->invoice, 'Max polling attempts exceeded');
            return;
        }

        Log::info('Checking DigiTax invoice status', [
            'invoice_id' => $this->invoice->id,
            'attempt' => $this->attemptNumber,
        ]);

        // Initialize service for the invoice's organization
        $digitaxService->forOrganization($this->invoice->organization);

        // Check status
        $result = $digitaxService->checkStatus($this->invoice);

        if ($result['complete']) {
            Log::info('DigiTax invoice complete', [
                'invoice_id' => $this->invoice->id,
                'receipt_url' => $result['receipt_url'],
            ]);
            return;
        }

        if ($result['status'] === 'failed') {
            Log::warning('DigiTax invoice failed', [
                'invoice_id' => $this->invoice->id,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
            return;
        }

        // Not complete yet - schedule next check with exponential backoff
        $delay = $this->calculateDelay();
        
        // Update retry count
        $this->invoice->update([
            'digitax_retry_count' => $this->attemptNumber,
        ]);

        Log::info('DigiTax still processing, scheduling next check', [
            'invoice_id' => $this->invoice->id,
            'next_attempt' => $this->attemptNumber + 1,
            'delay_seconds' => $delay,
        ]);

        self::dispatch($this->invoice, $this->attemptNumber + 1)
            ->delay(now()->addSeconds($delay));
    }

    /**
     * Calculate delay for next attempt using exponential backoff
     */
    protected function calculateDelay(): int
    {
        // Exponential backoff: 2^attempt seconds, capped at MAX_DELAY
        $delay = min(pow(2, $this->attemptNumber), self::MAX_DELAY);
        return (int) $delay;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CheckDigitaxInvoiceStatus job failed', [
            'invoice_id' => $this->invoice->id,
            'attempt' => $this->attemptNumber,
            'error' => $exception->getMessage(),
        ]);

        // Mark invoice as failed
        $this->invoice->update([
            'digitax_queue_status' => 'failed',
            'digitax_error' => [
                'type' => 'job_failure',
                'message' => $exception->getMessage(),
                'attempt' => $this->attemptNumber,
            ],
        ]);
    }
}
