<?php

namespace App\Services\Addy;

use App\Services\AI\AIService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentProcessorService
{
    protected AIService $aiService;

    public function __construct()
    {
        $this->aiService = new AIService();
    }

    /**
     * Process uploaded file and extract information
     */
    public function processFile(UploadedFile $file, string $organizationId): array
    {
        // Store the file
        $filePath = $file->store("chat-attachments/{$organizationId}", 'public');
        $fileName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Extract text/content based on file type
        $extractedText = $this->extractText($file, $filePath);

        // Use AI to extract structured information
        $extractedData = $this->extractStructuredData($extractedText, $mimeType);

        return [
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'extracted_text' => $extractedText,
            'extracted_data' => $extractedData,
        ];
    }

    /**
     * Extract text from file
     */
    protected function extractText(UploadedFile $file, string $filePath): string
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // Handle images - use AI vision API
        if (str_starts_with($mimeType, 'image/')) {
            return $this->extractTextFromImage($file);
        }

        // Handle PDFs
        if ($mimeType === 'application/pdf' || $extension === 'pdf') {
            return $this->extractTextFromPdf($file);
        }

        // Handle text files
        if (str_starts_with($mimeType, 'text/')) {
            return file_get_contents($file->getRealPath());
        }

        // Handle Office documents (basic - can be enhanced with libraries)
        if (in_array($extension, ['doc', 'docx', 'xls', 'xlsx'])) {
            return $this->extractTextFromOfficeDocument($file);
        }

        return '';
    }

    /**
     * Extract text from image using AI vision
     */
    protected function extractTextFromImage(UploadedFile $file): string
    {
        try {
            // Convert image to base64
            $imageData = base64_encode(file_get_contents($file->getRealPath()));
            $mimeType = $file->getMimeType();

            // Use OpenAI Vision API or similar
            $provider = \App\Models\PlatformSetting::get('ai_provider', 'openai');
            
            if ($provider === 'openai') {
                return $this->extractTextFromImageOpenAI($imageData, $mimeType);
            } else {
                // Anthropic Claude also supports images
                return $this->extractTextFromImageAnthropic($imageData, $mimeType);
            }
        } catch (\Exception $e) {
            \Log::error('Image text extraction failed', ['error' => $e->getMessage()]);
            return 'Unable to extract text from image.';
        }
    }

    /**
     * Extract text from image using OpenAI Vision
     */
    protected function extractTextFromImageOpenAI(string $imageData, string $mimeType): string
    {
        $apiKey = \App\Models\PlatformSetting::get('openai_api_key');
        $model = \App\Models\PlatformSetting::get('openai_model', 'gpt-4o');

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Extract ALL text and information from this image. This may be a bank statement, invoice, receipt, or other financial document. Include ALL numbers, dates, amounts, descriptions, account numbers, transaction details, balances, and any other information that could be used for business transactions. For bank statements, extract ALL transactions with dates, amounts, descriptions, and transaction types (debit/credit). Be thorough and extract everything visible.',
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$imageData}",
                            ],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 4000, // Increased for bank statements which can have many transactions
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI Vision API error: ' . $response->body());
        }

        $data = $response->json();
        return $data['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Extract text from image using Anthropic Claude
     */
    protected function extractTextFromImageAnthropic(string $imageData, string $mimeType): string
    {
        $apiKey = \App\Models\PlatformSetting::get('anthropic_api_key');
        $model = \App\Models\PlatformSetting::get('anthropic_model', 'claude-sonnet-4-20250514');

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 4000, // Increased for bank statements which can have many transactions
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mimeType,
                                'data' => $imageData,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Extract ALL text and information from this image. This may be a bank statement, invoice, receipt, or other financial document. Include ALL numbers, dates, amounts, descriptions, account numbers, transaction details, balances, and any other information that could be used for business transactions. For bank statements, extract ALL transactions with dates, amounts, descriptions, and transaction types (debit/credit). Be thorough and extract everything visible.',
                        ],
                    ],
                ],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Anthropic API error: ' . $response->body());
        }

        $data = $response->json();
        return $data['content'][0]['text'] ?? '';
    }

    /**
     * Extract text from PDF
     * Tries multiple methods: command-line tools (pdftotext), PHP parser, then AI vision
     */
    protected function extractTextFromPdf(UploadedFile $file): string
    {
        $filePath = $file->getRealPath();
        
        // Method 1: Try pdftotext (command-line tool) - best for password-protected PDFs
        if ($this->commandExists('pdftotext')) {
            try {
                $text = $this->extractTextWithPdftotext($filePath);
                if (!empty($text) && strlen(trim($text)) > 50) {
                    \Log::info('PDF text extracted successfully using pdftotext');
                    return $text;
                }
            } catch (\Exception $e) {
                \Log::warning('pdftotext extraction failed', ['error' => $e->getMessage()]);
            }
        }
        
        // Method 2: Try qpdf to decrypt if password-protected, then extract
        if ($this->commandExists('qpdf')) {
            try {
                $decryptedPath = $this->decryptPdfWithQpdf($filePath);
                if ($decryptedPath && $decryptedPath !== $filePath) {
                    // Try pdftotext on decrypted PDF
                    if ($this->commandExists('pdftotext')) {
                        $text = $this->extractTextWithPdftotext($decryptedPath);
                        @unlink($decryptedPath); // Clean up temp file
                        if (!empty($text) && strlen(trim($text)) > 50) {
                            \Log::info('PDF text extracted successfully after decryption with qpdf');
                            return $text;
                        }
                    }
                    // Try PHP parser on decrypted PDF
                    try {
                        $parser = new \Smalot\PdfParser\Parser();
                        $pdf = $parser->parseFile($decryptedPath);
                        $text = $pdf->getText();
                        @unlink($decryptedPath); // Clean up temp file
                        if (!empty($text) && strlen(trim($text)) > 50) {
                            \Log::info('PDF text extracted successfully using PHP parser after decryption');
                            return $text;
                        }
                    } catch (\Exception $e) {
                        @unlink($decryptedPath); // Clean up temp file
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('qpdf decryption failed', ['error' => $e->getMessage()]);
            }
        }
        
        // Method 3: Try PHP PDF Parser
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            if (!empty($text) && strlen(trim($text)) > 50) {
                \Log::info('PDF text extracted successfully using PHP parser');
                return $text;
            }
        } catch (\Smalot\PdfParser\Exception\EncodingException $e) {
            \Log::warning('PDF is password-protected (PHP parser)', ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            \Log::warning('PHP PDF parser failed', ['error' => $e->getMessage()]);
        }
        
        // Method 4: If all else fails, try AI vision (for image-based PDFs)
        \Log::info('All PDF text extraction methods failed, trying AI vision as last resort');
        try {
            return $this->extractTextFromPdfWithAI($file);
        } catch (\Exception $e) {
            \Log::error('All PDF extraction methods failed', ['error' => $e->getMessage()]);
            throw new \Exception('Unable to extract text from this PDF. The PDF may be password-protected, corrupted, or in an unsupported format. Please try: 1) Removing password protection, 2) Converting PDF pages to images and uploading those, or 3) Copying the text manually.');
        }
    }
    
    /**
     * Check if a command-line tool exists
     */
    protected function commandExists(string $command): bool
    {
        $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';
        $process = proc_open(
            "$whereIsCommand $command",
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );
        
        if ($process !== false) {
            $stdout = stream_get_contents($pipes[1]);
            proc_close($process);
            return !empty($stdout);
        }
        
        return false;
    }
    
    /**
     * Extract text using pdftotext command-line tool
     */
    protected function extractTextWithPdftotext(string $filePath): string
    {
        $tempOutput = tempnam(sys_get_temp_dir(), 'pdf_text_');
        
        // Run pdftotext command
        $command = escapeshellarg($filePath) . ' ' . escapeshellarg($tempOutput) . ' 2>&1';
        exec("pdftotext $command", $output, $returnCode);
        
        if ($returnCode !== 0) {
            @unlink($tempOutput);
            throw new \Exception('pdftotext failed: ' . implode("\n", $output));
        }
        
        if (!file_exists($tempOutput)) {
            throw new \Exception('pdftotext output file not created');
        }
        
        $text = file_get_contents($tempOutput);
        @unlink($tempOutput);
        
        return $text ?: '';
    }
    
    /**
     * Decrypt PDF using qpdf command-line tool
     */
    protected function decryptPdfWithQpdf(string $filePath): ?string
    {
        $decryptedPath = tempnam(sys_get_temp_dir(), 'pdf_decrypted_') . '.pdf';
        
        // Try to decrypt (qpdf will create output even if not encrypted)
        $command = escapeshellarg($filePath) . ' ' . escapeshellarg($decryptedPath) . ' 2>&1';
        exec("qpdf --decrypt $command", $output, $returnCode);
        
        if ($returnCode !== 0 || !file_exists($decryptedPath)) {
            @unlink($decryptedPath);
            return null;
        }
        
        // Check if decryption actually happened (file sizes might differ)
        return $decryptedPath;
    }
    
    /**
     * Extract text from PDF using AI vision (for image-based PDFs)
     */
    protected function extractTextFromPdfWithAI(UploadedFile $file): string
    {
        try {
            // Convert first page of PDF to image and use AI vision
            // For now, we'll use a simpler approach: try to read the PDF as an image
            // Note: This requires imagick extension for better PDF handling
            
            $provider = \App\Models\PlatformSetting::get('ai_provider', 'openai');
            $apiKey = $provider === 'openai' 
                ? \App\Models\PlatformSetting::get('openai_api_key')
                : \App\Models\PlatformSetting::get('anthropic_api_key');
            
            if (!$apiKey) {
                throw new \Exception('AI API key not configured');
            }
            
            // Read PDF file as base64
            $pdfData = base64_encode(file_get_contents($file->getRealPath()));
            
            if ($provider === 'openai') {
                return $this->extractTextFromPdfOpenAI($pdfData);
            } else {
                return $this->extractTextFromPdfAnthropic($pdfData);
            }
        } catch (\Exception $e) {
            \Log::error('PDF AI extraction failed', ['error' => $e->getMessage()]);
            return 'Unable to extract text from PDF. Please ensure the PDF contains readable text or try uploading as an image.';
        }
    }
    
    /**
     * Extract text from PDF using OpenAI
     * Note: OpenAI vision API doesn't support PDFs directly - only images
     * This method will attempt to use the file API or return an error
     */
    protected function extractTextFromPdfOpenAI(string $pdfData): string
    {
        // OpenAI vision API doesn't support PDFs directly
        // We need to convert PDF to images first, or use a different approach
        // For now, return an informative error message
        \Log::warning('OpenAI vision API does not support PDF files directly. PDF needs to be converted to images first.');
        
        throw new \Exception('This PDF cannot be processed automatically. It may be password-protected or in a format that requires conversion. Please try: 1) Removing password protection from the PDF, 2) Converting the PDF pages to images and uploading those, or 3) Copying the text from the PDF and pasting it in the chat.');
    }
    
    /**
     * Extract text from PDF using Anthropic
     * Note: Anthropic doesn't support PDFs directly, so we need to convert PDF to images first
     */
    protected function extractTextFromPdfAnthropic(string $pdfData): string
    {
        // Anthropic doesn't support PDFs directly - only images
        // For now, return a message indicating the PDF needs to be converted to images
        // In production, you'd use imagick or similar to convert PDF pages to images
        \Log::warning('Anthropic API does not support PDF files directly. PDF needs to be converted to images first.');
        
        // Try OpenAI instead if available, as it might have better PDF support
        $openaiKey = \App\Models\PlatformSetting::get('openai_api_key');
        if ($openaiKey) {
            \Log::info('Falling back to OpenAI for PDF processing');
            return $this->extractTextFromPdfOpenAI($pdfData);
        }
        
        throw new \Exception('PDF processing requires image conversion. Please upload the PDF as images or use OpenAI API which has better PDF support.');
    }

    /**
     * Extract text from Office documents
     */
    protected function extractTextFromOfficeDocument(UploadedFile $file): string
    {
        // For now, return a placeholder
        // In production, you'd use a library like PhpOffice\PhpWord or similar
        return 'Office document text extraction - to be implemented with document parsing library';
    }

    /**
     * Use AI to extract structured data from extracted text
     */
    protected function extractStructuredData(string $text, string $mimeType): array
    {
        if (empty($text) || $text === 'Unable to extract text from PDF. Please ensure the PDF contains readable text or try uploading as an image.') {
            return [];
        }

        try {
            $prompt = "Extract structured information from this document text. Focus on identifying:
1. Document type: 'invoice' (outgoing invoice to create for a customer), 'receipt' (expense), 'income' (money received), 'quote' (quotation), 'bank_statement' (bank statement with multiple transactions), 'client_list' (list of clients/customers), 'note' (written note/memo), 'contract' (contract/agreement), or 'unknown'
2. Amount(s) and currency - for bank statements, extract ALL transactions with dates, amounts, descriptions, and types (debit/credit)
3. Date(s) including due_date if it's an invoice or quote, statement period for bank statements
4. Customer/client name (if invoice/quote) or merchant/vendor name (if receipt/expense)
5. Description/items - for bank statements, include transaction descriptions
6. Category (if identifiable)
7. Any other relevant business transaction details
8. For bank statements: extract account number, statement period, opening balance, closing balance, and all transactions with dates, amounts, descriptions, and transaction types
9. For client lists: extract all customer/client names, emails, phone numbers, company names
10. For quotes: extract quote number, expiry date, line items
11. For notes: extract key information, action items, dates mentioned

Return the information in JSON format with these fields:
- document_type: 'invoice', 'receipt', 'income', 'quote', 'bank_statement', 'client_list', 'note', 'contract', or 'unknown'
- type: 'income' or 'expense' (for receipts/income) or 'invoice' (for invoices) or 'quote' (for quotations) or 'bank_statement' (for bank statements)
- amount: numeric amount (total for invoice/quote, or total for single transaction)
- currency: currency code (e.g., ZMW, USD)
- date: date in YYYY-MM-DD format
- due_date: due date in YYYY-MM-DD format (for invoices)
- expiry_date: expiry date in YYYY-MM-DD format (for quotes)
- description: transaction description
- customer_name: customer/client name (for invoices/quotes)
- merchant: merchant/vendor name (for receipts/expenses)
- category: expense/income category if identifiable
- items: array of line items with description, quantity, unit_price (for invoices/quotes or multi-item receipts)
- transactions: array of transaction objects for bank statements, each with: date, amount, description, type (debit/credit), balance
- account_number: account number (for bank statements)
- statement_period_start: start date of statement period (for bank statements)
- statement_period_end: end date of statement period (for bank statements)
- opening_balance: opening balance (for bank statements)
- closing_balance: closing balance (for bank statements)
- clients: array of client objects with name, email, phone, company (for client lists)
- quote_number: quote number if it's a quote
- key_points: array of key points or action items (for notes)
- contract_parties: array of party names (for contracts)
- contract_terms: key terms or conditions (for contracts)

Document text:
{$text}";

            $response = $this->aiService->ask($prompt, 'You are a business document parser. Extract structured transaction data from documents.');

            // Try to parse JSON from response
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                $data = json_decode($jsonString, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                }
            }

            // Fallback: return raw response
            return [
                'raw_extraction' => $response,
                'type' => 'unknown',
            ];
        } catch (\Exception $e) {
            \Log::error('Structured data extraction failed', ['error' => $e->getMessage()]);
            return [
                'error' => 'Failed to extract structured data',
                'raw_text' => $text,
            ];
        }
    }
}

