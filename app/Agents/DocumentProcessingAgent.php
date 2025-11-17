<?php

namespace App\Agents;

use App\Models\DocumentProcessingJob as ProcessingJobModel;
use App\Models\MoneyMovement;
use App\Models\MoneyAccount;
use App\Services\Addy\DocumentProcessorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Smart agent that processes documents in the background
 * Handles type detection, extraction, validation, and import
 */
class DocumentProcessingAgent
{
    protected DocumentProcessorService $processor;
    protected string $organizationId;
    protected int $userId;
    protected ProcessingJobModel $job;

    public function __construct(
        DocumentProcessorService $processor,
        string $organizationId,
        int $userId
    ) {
        $this->processor = $processor;
        $this->organizationId = $organizationId;
        $this->userId = $userId;
    }

    /**
     * Main processing pipeline
     */
    public function process(string $filePath, array $metadata = []): array
    {
        // Create processing job record
        $this->job = $this->createJobRecord($filePath, $metadata);

        try {
            // Step 1: Extract text and initial data
            $this->updateStatus('extracting', 'Extracting text from document...');
            $extracted = $this->extractData($filePath);

            // Step 2: Determine document type (with smart detection)
            $this->updateStatus('analyzing', 'Analyzing document type...');
            $documentType = $this->determineDocumentType($extracted);

            // Step 3: Clean and validate data
            $this->updateStatus('validating', 'Validating extracted data...');
            $cleanedData = $this->cleanAndValidateData($extracted['data'], $documentType);

            // Step 4: Fix common issues
            $this->updateStatus('fixing', 'Fixing common issues...');
            $fixedData = $this->fixCommonIssues($cleanedData, $documentType);

            // Step 5: Analyze confidence and generate questions
            $this->updateStatus('analyzing_confidence', 'Analyzing data confidence...');
            $analysis = $this->analyzeConfidence($fixedData, $documentType);

            // Step 6: Prepare final result
            $result = [
                'success' => true,
                'document_type' => $documentType,
                'data' => $fixedData,
                'raw_text' => $extracted['text'],
                'confidence' => $analysis['confidence'],
                'requires_review' => $analysis['requires_review'],
                'questions' => $analysis['questions'] ?? [],
                'metadata' => array_merge($metadata, [
                    'processing_time' => now()->diffInSeconds($this->job->created_at),
                    'extraction_method' => $extracted['method'] ?? 'unknown',
                ]),
            ];

            // Step 7: Auto-import if high confidence
            if (!$analysis['requires_review'] && $analysis['confidence'] >= 0.85) {
                $this->updateStatus('importing', 'Auto-importing document...');
                $importResult = $this->autoImport($result);
                $result['imported'] = true;
                $result['import_result'] = $importResult;
            }

            // Mark as complete
            $this->updateStatus('completed', 'Document processed successfully', $result);

            return $result;

        } catch (Exception $e) {
            // Mark as failed
            $this->updateStatus('failed', $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Log::error('DocumentProcessingAgent failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
                'organization_id' => $this->organizationId,
            ]);

            throw $e;
        }
    }

    /**
     * Extract text and structured data from document
     */
    protected function extractData(string $filePath): array
    {
        // Try multiple extraction methods
        $methods = ['ai_vision', 'pdf_parser', 'ocr'];
        $lastError = null;

        foreach ($methods as $method) {
            try {
                $result = $this->tryExtractionMethod($filePath, $method);
                if ($result) {
                    $result['method'] = $method;
                    return $result;
                }
            } catch (Exception $e) {
                $lastError = $e;
                Log::warning("Extraction method {$method} failed", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw new Exception('All extraction methods failed: ' . ($lastError?->getMessage() ?? 'Unknown error'));
    }

    /**
     * Try specific extraction method
     */
    protected function tryExtractionMethod(string $filePath, string $method): ?array
    {
        $file = new \Illuminate\Http\UploadedFile(
            $filePath,
            basename($filePath),
            mime_content_type($filePath),
            null,
            true
        );

        $result = $this->processor->processFile($file, $this->organizationId);

        if (!empty($result['extracted_text'])) {
            return [
                'text' => $result['extracted_text'],
                'data' => $result['extracted_data'] ?? [],
            ];
        }

        return null;
    }

    /**
     * Smart document type detection
     */
    protected function determineDocumentType(array $extracted): string
    {
        $data = $extracted['data'];
        $text = strtolower($extracted['text']);

        // Check explicit document_type from AI
        if (!empty($data['document_type']) && $data['document_type'] !== 'unknown') {
            return $this->normalizeDocumentType($data['document_type']);
        }

        // Check type field
        if (!empty($data['type']) && $data['type'] !== 'unknown') {
            return $this->normalizeDocumentType($data['type']);
        }

        // Smart detection based on content
        $detectors = [
            'bank_statement' => [
                'keywords' => ['bank statement', 'account statement', 'closing balance', 'opening balance', 'transaction history'],
                'has_transactions' => true,
            ],
            'receipt' => [
                'keywords' => ['receipt', 'thank you for your purchase', 'total paid', 'cash', 'change'],
                'has_amount' => true,
            ],
            'invoice' => [
                'keywords' => ['invoice', 'invoice number', 'due date', 'bill to', 'invoice date'],
                'has_invoice_number' => true,
            ],
            'mobile_money' => [
                'keywords' => ['airtel money', 'mtn money', 'zamtel money', 'mobile money', 'transaction id'],
                'has_transaction_id' => true,
            ],
            'quote' => [
                'keywords' => ['quotation', 'quote', 'valid until', 'estimated cost'],
            ],
        ];

        foreach ($detectors as $type => $rules) {
            $score = 0;

            // Check keywords
            foreach ($rules['keywords'] as $keyword) {
                if (str_contains($text, $keyword)) {
                    $score += 2;
                }
            }

            // Check data presence
            if (isset($rules['has_transactions']) && !empty($data['transactions'])) {
                $score += 5;
            }
            if (isset($rules['has_amount']) && !empty($data['amount'])) {
                $score += 1;
            }
            if (isset($rules['has_invoice_number']) && !empty($data['invoice_number'])) {
                $score += 3;
            }
            if (isset($rules['has_transaction_id']) && !empty($data['transaction_id'])) {
                $score += 3;
            }

            // If score is high enough, this is likely the type
            if ($score >= 5) {
                Log::info("Document type detected: {$type} (score: {$score})");
                return $type;
            }
        }

        Log::warning('Could not determine document type', [
            'data_keys' => array_keys($data),
            'text_snippet' => substr($text, 0, 200),
        ]);

        return 'unknown';
    }

    /**
     * Normalize document type
     */
    protected function normalizeDocumentType(string $type): string
    {
        $normalized = strtolower(trim($type));

        $mapping = [
            'receipt' => 'receipt',
            'expense' => 'receipt',
            'invoice' => 'invoice',
            'bill' => 'invoice',
            'bank_statement' => 'bank_statement',
            'bank statement' => 'bank_statement',
            'statement' => 'bank_statement',
            'mobile_money' => 'mobile_money',
            'mobile money' => 'mobile_money',
            'airtel' => 'mobile_money',
            'mtn' => 'mobile_money',
            'income' => 'income',
            'quote' => 'quote',
            'quotation' => 'quote',
        ];

        return $mapping[$normalized] ?? $normalized;
    }

    /**
     * Clean and validate extracted data
     */
    protected function cleanAndValidateData(array $data, string $documentType): array
    {
        $cleaned = $data;

        // Ensure document type is set
        $cleaned['document_type'] = $documentType;
        $cleaned['type'] = $documentType;

        // Clean amounts
        foreach (['amount', 'total', 'total_amount'] as $field) {
            if (isset($cleaned[$field])) {
                $cleaned[$field] = $this->cleanAmount($cleaned[$field]);
            }
        }

        // Clean dates
        foreach (['date', 'due_date', 'statement_period_start', 'statement_period_end'] as $field) {
            if (isset($cleaned[$field])) {
                $cleaned[$field] = $this->cleanDate($cleaned[$field]);
            }
        }

        // Document-specific cleaning
        switch ($documentType) {
            case 'bank_statement':
                $cleaned = $this->cleanBankStatementData($cleaned);
                break;
            case 'mobile_money':
                $cleaned = $this->cleanMobileMoneyData($cleaned);
                break;
        }

        return $cleaned;
    }

    /**
     * Clean bank statement data
     */
    protected function cleanBankStatementData(array $data): array
    {
        // Ensure transactions array exists
        if (empty($data['transactions'])) {
            $data['transactions'] = [];
        }

        // Clean each transaction
        foreach ($data['transactions'] as $index => $transaction) {
            // Parse date (handle "Sep 29" format)
            $data['transactions'][$index]['date'] = $this->parseBankStatementDate(
                $transaction['date'] ?? '',
                $data['statement_period_end'] ?? null
            );

            // Clean amount
            $data['transactions'][$index]['amount'] = $this->cleanAmount($transaction['amount'] ?? 0);

            // Ensure flow_type is set
            if (empty($transaction['flow_type'])) {
                $data['transactions'][$index]['flow_type'] = $this->inferFlowType($transaction);
            }

            // Clean description
            $data['transactions'][$index]['description'] = trim($transaction['description'] ?? 'Transaction');
        }

        return $data;
    }

    /**
     * Parse bank statement date (handles "Sep 29" format)
     */
    protected function parseBankStatementDate(string $dateStr, ?string $statementEndDate): string
    {
        if (empty($dateStr)) {
            return now()->format('Y-m-d');
        }

        // Try standard parsing first
        try {
            return Carbon::parse($dateStr)->format('Y-m-d');
        } catch (Exception $e) {
            // Ignore, try custom parsing
        }

        // Handle "Sep 29" or "Sep 29 2024" format
        if (preg_match('/^([A-Za-z]{3})\s+(\d{1,2})(?:\s+(\d{4}))?$/', trim($dateStr), $matches)) {
            $month = $matches[1];
            $day = $matches[2];
            $year = $matches[3] ?? null;

            // If no year, infer from statement end date
            if (!$year && $statementEndDate) {
                try {
                    $year = Carbon::parse($statementEndDate)->year;
                } catch (Exception $e) {
                    $year = now()->year;
                }
            }

            if (!$year) {
                $year = now()->year;
            }

            try {
                return Carbon::parse("{$month} {$day}, {$year}")->format('Y-m-d');
            } catch (Exception $e) {
                Log::warning("Failed to parse date: {$dateStr}");
                return now()->format('Y-m-d');
            }
        }

        return now()->format('Y-m-d');
    }

    /**
     * Infer transaction flow type
     */
    protected function inferFlowType(array $transaction): string
    {
        $type = strtolower($transaction['type'] ?? '');
        
        if (in_array($type, ['debit', 'expense', 'withdrawal'])) {
            return 'expense';
        }
        
        if (in_array($type, ['credit', 'income', 'deposit'])) {
            return 'income';
        }

        // Check description for clues
        $description = strtolower($transaction['description'] ?? '');
        if (str_contains($description, 'deposit') || str_contains($description, 'credit')) {
            return 'income';
        }

        return 'expense'; // Default
    }

    /**
     * Clean mobile money data
     */
    protected function cleanMobileMoneyData(array $data): array
    {
        // Ensure provider is recognized
        $data['provider'] = $this->normalizeProvider($data['provider'] ?? '');

        // Infer flow type if not set
        if (empty($data['flow_type']) && empty($data['type'])) {
            $data['flow_type'] = $this->inferMobileMoneyFlowType($data);
        }

        return $data;
    }

    /**
     * Normalize mobile money provider
     */
    protected function normalizeProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));

        if (str_contains($provider, 'airtel')) return 'Airtel Money';
        if (str_contains($provider, 'mtn')) return 'MTN Money';
        if (str_contains($provider, 'zamtel')) return 'Zamtel Money';

        return $provider;
    }

    /**
     * Infer mobile money transaction type
     */
    protected function inferMobileMoneyFlowType(array $data): string
    {
        // Check transaction context
        $context = strtolower($data['transaction_context'] ?? $data['description'] ?? '');
        
        if (str_contains($context, 'received') || str_contains($context, 'payment from')) {
            return 'income';
        }
        
        if (str_contains($context, 'sent') || str_contains($context, 'payment to')) {
            return 'expense';
        }

        return 'income'; // Default for mobile money (usually receiving payments)
    }

    /**
     * Clean amount value
     */
    protected function cleanAmount($amount): float
    {
        if (is_numeric($amount)) {
            return (float) $amount;
        }

        // Remove currency symbols and commas
        $cleaned = preg_replace('/[^0-9.]/', '', (string) $amount);
        
        return $cleaned ? (float) $cleaned : 0.0;
    }

    /**
     * Clean date value
     */
    protected function cleanDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Fix common issues in data
     */
    protected function fixCommonIssues(array $data, string $documentType): array
    {
        // Fix missing required fields
        if (empty($data['date'])) {
            $data['date'] = now()->format('Y-m-d');
        }

        // Fix currency
        if (empty($data['currency'])) {
            $data['currency'] = 'ZMW';
        }

        // Document-specific fixes
        switch ($documentType) {
            case 'receipt':
                // Ensure amount field exists
                if (empty($data['amount']) && !empty($data['total'])) {
                    $data['amount'] = $data['total'];
                }
                break;

            case 'invoice':
                // Ensure total_amount field exists
                if (empty($data['total_amount']) && !empty($data['amount'])) {
                    $data['total_amount'] = $data['amount'];
                }
                break;
        }

        return $data;
    }

    /**
     * Analyze confidence and generate questions
     */
    protected function analyzeConfidence(array $data, string $documentType): array
    {
        $requiredFields = $this->getRequiredFields($documentType);
        $presentFields = 0;

        foreach ($requiredFields as $field) {
            if (!empty($data[$field])) {
                $presentFields++;
            }
        }

        $confidence = count($requiredFields) > 0 
            ? $presentFields / count($requiredFields)
            : 1.0;

        $requiresReview = $confidence < 0.85;

        return [
            'confidence' => $confidence,
            'requires_review' => $requiresReview,
            'questions' => $requiresReview ? $this->generateQuestions($data, $documentType) : [],
        ];
    }

    /**
     * Get required fields for document type
     */
    protected function getRequiredFields(string $documentType): array
    {
        return match($documentType) {
            'receipt' => ['date', 'amount', 'merchant'],
            'invoice' => ['date', 'total_amount', 'customer_name'],
            'bank_statement' => ['transactions'],
            'mobile_money' => ['date', 'amount', 'transaction_id'],
            default => ['date', 'amount'],
        };
    }

    /**
     * Generate review questions
     */
    protected function generateQuestions(array $data, string $documentType): array
    {
        $questions = [];
        $requiredFields = $this->getRequiredFields($documentType);

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $questions[] = [
                    'field' => $field,
                    'type' => 'text_input',
                    'question' => "What is the {$field}?",
                    'required' => true,
                ];
            }
        }

        return $questions;
    }

    /**
     * Auto-import document
     */
    protected function autoImport(array $result): array
    {
        $user = \App\Models\User::find($this->userId);
        $documentType = $result['document_type'];
        $data = $result['data'];

        // Use import methods from AgentDataUploadController
        $controller = new \App\Http\Controllers\AgentDataUploadController();

        // Use reflection to call protected methods
        $reflection = new \ReflectionClass($controller);
        
        try {
            switch ($documentType) {
                case 'bank_statement':
                    $method = $reflection->getMethod('importBankStatement');
                    $method->setAccessible(true);
                    return $method->invoke($controller, $user, $this->organizationId, $data);
                
                case 'receipt':
                case 'expense':
                    $method = $reflection->getMethod('importReceipt');
                    $method->setAccessible(true);
                    return $method->invoke($controller, $user, $this->organizationId, $data);
                
                case 'income':
                    $method = $reflection->getMethod('importIncome');
                    $method->setAccessible(true);
                    return $method->invoke($controller, $user, $this->organizationId, $data);
                
                case 'mobile_money':
                    $method = $reflection->getMethod('importMobileMoney');
                    $method->setAccessible(true);
                    return $method->invoke($controller, $user, $this->organizationId, $data);
                
                default:
                    return [
                        'success' => false,
                        'message' => "Auto-import not supported for document type: {$documentType}",
                    ];
            }
        } catch (\Exception $e) {
            Log::error('Auto-import failed', [
                'error' => $e->getMessage(),
                'document_type' => $documentType,
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create processing job record
     */
    protected function createJobRecord(string $filePath, array $metadata): ProcessingJobModel
    {
        return ProcessingJobModel::create([
            'id' => Str::uuid(),
            'organization_id' => $this->organizationId,
            'user_id' => $this->userId,
            'file_path' => $filePath,
            'file_name' => $metadata['original_name'] ?? basename($filePath),
            'status' => 'pending',
            'metadata' => $metadata,
            'started_at' => now(),
        ]);
    }

    /**
     * Update job status
     */
    protected function updateStatus(string $status, string $message, array $result = []): void
    {
        $updates = [
            'status' => $status,
            'status_message' => $message,
        ];

        if ($status === 'completed') {
            $updates['completed_at'] = now();
            $updates['result'] = $result;
        }

        if ($status === 'failed') {
            $updates['completed_at'] = now();
            $updates['error'] = $result;
        }

        $this->job->update($updates);

        Log::info("DocumentProcessingAgent: {$status}", [
            'job_id' => $this->job->id,
            'message' => $message,
        ]);
    }
}

