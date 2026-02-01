<?php

namespace Addy\Modules\SmartInvoice\Services;

use App\Models\Invoice;
use App\Models\Organization;
use Addy\Modules\SmartInvoice\Models\DigitaxCredential;
use Addy\Modules\SmartInvoice\Services\DigitaxService;
use App\Jobs\CheckDigitaxInvoiceStatus;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service for submitting Addy invoices to DigiTax ZRA Smart Invoice system.
 * 
 * Flow:
 * 1. Submit invoice → POST /sales
 * 2. Poll for completion → GET /sales/{id}
 * 3. When complete → store receipt_url for QR code
 * 
 * @see apps/addy/docs/DIGITAX_IMPLEMENTATION_GUIDE.md
 */
class InvoiceDigitaxService
{
    protected ?DigitaxService $digitaxService = null;
    protected ?DigitaxCredential $credential = null;

    /**
     * Payment type mapping from Addy to DigiTax codes
     */
    protected array $paymentTypeCodes = [
        'cash' => '01',
        'credit' => '02',
        'mixed' => '03',
        'check' => '04',
        'card' => '05',
        'mobile_money' => '06',
        'bank_transfer' => '07',
        'other' => '07',
    ];

    /**
     * Initialize service for an organization
     */
    public function forOrganization(Organization $organization): self
    {
        $this->credential = DigitaxCredential::where('organization_id', $organization->id)
            ->active()
            ->first();

        if ($this->credential) {
            $this->digitaxService = new DigitaxService($this->credential);
        }

        return $this;
    }

    /**
     * Check if Smart Invoice is enabled for the organization
     */
    public function isEnabled(): bool
    {
        return $this->credential !== null && $this->credential->is_active;
    }

    /**
     * Check if credential is configured and valid
     */
    public function isConfigured(): bool
    {
        return $this->credential !== null && $this->credential->isValid();
    }

    /**
     * Submit an invoice to DigiTax
     * 
     * @return array{success: bool, message: string, sale_id: ?string}
     */
    public function submitInvoice(Invoice $invoice): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'Smart Invoice is not enabled for this organization',
                'sale_id' => null,
            ];
        }

        // Prevent duplicate submissions
        if ($invoice->isDigitaxSubmitted()) {
            return [
                'success' => false,
                'message' => 'Invoice has already been submitted to DigiTax',
                'sale_id' => $invoice->digitax_sale_id,
            ];
        }

        try {
            // Map invoice to DigiTax sale format
            $saleData = $this->mapInvoiceToSale($invoice);

            Log::info('Submitting invoice to DigiTax', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'sale_data' => $saleData,
            ]);

            // Submit to DigiTax
            $response = $this->digitaxService->createSale($saleData);

            if ($response->status() === 403) {
                return $this->handleAuthError($invoice, $response);
            }

            if (!$response->successful()) {
                return $this->handleSubmissionError($invoice, $response);
            }

            $responseData = $response->json();
            $saleId = $responseData['id'] ?? $responseData['sale_id'] ?? null;

            // Update invoice with submission data
            $invoice->update([
                'digitax_sale_id' => $saleId,
                'digitax_queue_status' => 'queued',
                'digitax_response' => $responseData,
                'digitax_submitted_at' => now(),
                'digitax_retry_count' => 0,
            ]);

            // Dispatch job to check status
            CheckDigitaxInvoiceStatus::dispatch($invoice)
                ->delay(now()->addSeconds(2));

            Log::info('Invoice submitted to DigiTax successfully', [
                'invoice_id' => $invoice->id,
                'digitax_sale_id' => $saleId,
            ]);

            return [
                'success' => true,
                'message' => 'Invoice submitted to DigiTax. Processing with ZRA...',
                'sale_id' => $saleId,
            ];

        } catch (Exception $e) {
            Log::error('DigiTax submission failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            $invoice->update([
                'digitax_queue_status' => 'failed',
                'digitax_error' => ['exception' => $e->getMessage()],
            ]);

            return [
                'success' => false,
                'message' => 'Failed to submit invoice: ' . $e->getMessage(),
                'sale_id' => null,
            ];
        }
    }

    /**
     * Check status of a submitted invoice
     * 
     * @return array{status: string, complete: bool, receipt_url: ?string}
     */
    public function checkStatus(Invoice $invoice): array
    {
        if (!$invoice->digitax_sale_id) {
            return [
                'status' => 'not_submitted',
                'complete' => false,
                'receipt_url' => null,
            ];
        }

        try {
            $response = $this->digitaxService->getSale($invoice->digitax_sale_id);

            if (!$response->successful()) {
                Log::warning('DigiTax status check failed', [
                    'invoice_id' => $invoice->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'status' => 'error',
                    'complete' => false,
                    'receipt_url' => null,
                    'error' => $response->body(),
                ];
            }

            $data = $response->json();
            $queueStatus = $data['queue_status'] ?? $data['status'] ?? 'unknown';
            $receiptUrl = $data['receipt_url'] ?? null;

            // Map DigiTax status to our internal status
            $mappedStatus = match ($queueStatus) {
                'complete', 'completed' => 'complete',
                'processing', 'pending' => 'processing',
                'queued' => 'queued',
                'failed', 'error' => 'failed',
                default => 'processing',
            };

            $isComplete = $mappedStatus === 'complete' && $receiptUrl !== null;

            if ($isComplete) {
                $this->processCompletion($invoice, $data);
            } else {
                // Update with latest response
                $invoice->update([
                    'digitax_queue_status' => $mappedStatus,
                    'digitax_response' => $data,
                ]);
            }

            return [
                'status' => $mappedStatus,
                'complete' => $isComplete,
                'receipt_url' => $receiptUrl,
                'data' => $data,
            ];

        } catch (Exception $e) {
            Log::error('DigiTax status check exception', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'complete' => false,
                'receipt_url' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process a completed DigiTax sale
     */
    public function processCompletion(Invoice $invoice, array $saleDetails): void
    {
        $invoice->update([
            'digitax_queue_status' => 'complete',
            'digitax_receipt_url' => $saleDetails['receipt_url'] ?? null,
            'digitax_receipt_number' => $saleDetails['receipt_number'] ?? null,
            'digitax_serial_number' => $saleDetails['serial_number'] ?? null,
            'digitax_receipt_signature' => $saleDetails['receipt_signature'] ?? null,
            'digitax_response' => $saleDetails,
            'digitax_completed_at' => now(),
        ]);

        Log::info('DigiTax invoice processing complete', [
            'invoice_id' => $invoice->id,
            'receipt_url' => $saleDetails['receipt_url'] ?? null,
            'receipt_number' => $saleDetails['receipt_number'] ?? null,
        ]);
    }

    /**
     * Mark invoice as failed after max retries
     */
    public function markAsFailed(Invoice $invoice, string $reason): void
    {
        $invoice->update([
            'digitax_queue_status' => 'failed',
            'digitax_error' => [
                'reason' => $reason,
                'failed_at' => now()->toIso8601String(),
            ],
        ]);

        Log::warning('DigiTax invoice marked as failed', [
            'invoice_id' => $invoice->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Map Addy invoice to DigiTax sale format
     */
    protected function mapInvoiceToSale(Invoice $invoice): array
    {
        // Extract numeric part from invoice number for DigiTax
        $numericInvoiceNumber = (int) preg_replace('/\D/', '', $invoice->invoice_number);
        
        // Ensure we have a valid number
        if ($numericInvoiceNumber === 0) {
            $numericInvoiceNumber = $invoice->id;
        }

        $saleData = [
            'trader_invoice_number' => $invoice->invoice_number,
            'invoice_number' => $numericInvoiceNumber,
            'receipt_type_code' => 'S', // Sale
            'invoice_status_code' => '02', // Approved
            'payment_type_code' => $this->mapPaymentType($invoice),
            'items' => $this->mapInvoiceItems($invoice),
        ];

        // Add customer info if available
        if ($invoice->customer) {
            $saleData['customer_tpin'] = $invoice->customer->tax_id ?? null;
            $saleData['customer_name'] = $invoice->customer->name ?? null;
        }

        return $saleData;
    }

    /**
     * Map invoice items to DigiTax format
     */
    protected function mapInvoiceItems(Invoice $invoice): array
    {
        return $invoice->items->map(function ($item) {
            return [
                'item_name' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                // These would need to be mapped from product catalog if using DigiTax items
                // 'item_id' => $item->product->digitax_item_id ?? null,
            ];
        })->toArray();
    }

    /**
     * Map Addy payment method to DigiTax code
     */
    protected function mapPaymentType(Invoice $invoice): string
    {
        $paymentMethod = $invoice->payment_details['method'] ?? 'other';
        return $this->paymentTypeCodes[$paymentMethod] ?? '07';
    }

    /**
     * Handle authentication error from DigiTax
     */
    protected function handleAuthError(Invoice $invoice, $response): array
    {
        $body = $response->json();
        $error = $body['message'] ?? 'Unauthorized';

        $invoice->update([
            'digitax_queue_status' => 'failed',
            'digitax_error' => [
                'type' => 'auth_error',
                'message' => $error,
                'response' => $body,
            ],
        ]);

        return [
            'success' => false,
            'message' => 'DigiTax authentication failed. Please check your API key.',
            'sale_id' => null,
        ];
    }

    /**
     * Handle submission error from DigiTax
     */
    protected function handleSubmissionError(Invoice $invoice, $response): array
    {
        $body = $response->json();
        $error = $body['message'] ?? $response->body();

        // Check for validation errors
        $validationErrors = $body['errors'] ?? $body['issues'] ?? null;

        $invoice->update([
            'digitax_queue_status' => 'failed',
            'digitax_error' => [
                'type' => 'submission_error',
                'status' => $response->status(),
                'message' => $error,
                'validation_errors' => $validationErrors,
                'response' => $body,
            ],
        ]);

        Log::warning('DigiTax submission error', [
            'invoice_id' => $invoice->id,
            'status' => $response->status(),
            'error' => $error,
            'validation_errors' => $validationErrors,
        ]);

        return [
            'success' => false,
            'message' => 'DigiTax submission failed: ' . $error,
            'sale_id' => null,
            'validation_errors' => $validationErrors,
        ];
    }

    /**
     * Get the credential for this service
     */
    public function getCredential(): ?DigitaxCredential
    {
        return $this->credential;
    }
}
