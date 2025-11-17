<?php

namespace App\Services;

use App\Services\Addy\DocumentProcessorService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImprovedOcrService
{
    protected DocumentProcessorService $documentProcessor;

    public function __construct()
    {
        $this->documentProcessor = new DocumentProcessorService();
    }

    /**
     * Process document and extract structured data
     */
    public function processDocument(string $filePath): array
    {
        try {
            // Check if file exists
            if (!Storage::exists($filePath) && !file_exists($filePath)) {
                return [
                    'success' => false,
                    'error' => 'File not found',
                ];
            }

            // Get full path
            $fullPath = $filePath;
            if (Storage::exists($filePath)) {
                $fullPath = Storage::path($filePath);
            } elseif (!file_exists($filePath)) {
                // Try to find it in storage
                $fullPath = storage_path('app/' . $filePath);
            }

            if (!file_exists($fullPath)) {
                return [
                    'success' => false,
                    'error' => 'File not found at path: ' . $filePath,
                ];
            }

            // Create a temporary UploadedFile object for processing
            $file = new UploadedFile(
                $fullPath,
                basename($fullPath),
                mime_content_type($fullPath),
                null,
                true
            );

            // Get organization ID from session or use a default
            $organizationId = session('current_organization_id') ?? auth()->user()?->current_organization_id ?? 'default';

            // Process using DocumentProcessorService
            $result = $this->documentProcessor->processFile($file, $organizationId);

            // Extract structured data
            $extractedData = $result['extracted_data'] ?? [];
            $documentType = $extractedData['document_type'] ?? 'unknown';

            // Calculate overall confidence (simplified - can be enhanced)
            $confidence = $this->calculateConfidence($extractedData, $documentType);

            return [
                'success' => true,
                'data' => $extractedData,
                'document_type' => $documentType,
                'raw_text' => $result['extracted_text'] ?? '',
                'confidence' => $confidence,
                'file_path' => $filePath,
            ];
        } catch (\Exception $e) {
            \Log::error('OCR processing failed', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate overall confidence score
     */
    protected function calculateConfidence(array $data, string $documentType): float
    {
        $scores = [];
        $requiredFields = $this->getRequiredFields($documentType);

        foreach ($requiredFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $scores[] = 1.0;
            } else {
                $scores[] = 0.0;
            }
        }

        return count($scores) > 0 ? array_sum($scores) / count($scores) : 0.5;
    }

    /**
     * Get required fields for document type
     */
    protected function getRequiredFields(string $documentType): array
    {
        return match($documentType) {
            'receipt' => ['date', 'total', 'merchant'],
            'invoice' => ['date', 'total', 'invoice_number'],
            'mobile_money' => ['date', 'amount', 'transaction_id'],
            'bank_statement' => ['date', 'amount'],
            default => ['date', 'amount'],
        };
    }
}

