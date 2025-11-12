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
                            'text' => 'Extract all text and information from this image. Include any numbers, dates, amounts, descriptions, and details that could be used for business transactions like receipts, invoices, or expense records.',
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
            'max_tokens' => 2000,
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
            'max_tokens' => 2000,
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
                            'text' => 'Extract all text and information from this image. Include any numbers, dates, amounts, descriptions, and details that could be used for business transactions like receipts, invoices, or expense records.',
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
     */
    protected function extractTextFromPdf(UploadedFile $file): string
    {
        try {
            // Use Smalot PDF Parser for text-based PDFs
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file->getRealPath());
            $text = $pdf->getText();
            
            if (!empty($text)) {
                return $text;
            }
            
            // If text extraction returns empty, the PDF might be image-based
            // Try using AI vision on the first page
            \Log::info('PDF text extraction returned empty, trying AI vision');
            return $this->extractTextFromPdfWithAI($file);
        } catch (\Exception $e) {
            \Log::warning('PDF text extraction failed, trying AI vision', ['error' => $e->getMessage()]);
            // Fallback to AI vision for image-based PDFs or if parsing fails
            return $this->extractTextFromPdfWithAI($file);
        }
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
     */
    protected function extractTextFromPdfOpenAI(string $pdfData): string
    {
        $apiKey = \App\Models\PlatformSetting::get('openai_api_key');
        $model = \App\Models\PlatformSetting::get('openai_model', 'gpt-4o');
        
        // OpenAI supports PDF files directly in some models
        // For gpt-4o, we can send the PDF as base64
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Extract all text and information from this PDF document. This could be a bank statement, invoice, receipt, or other business document. Include all numbers, dates, amounts, descriptions, transaction details, and any other relevant information.',
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:application/pdf;base64,{$pdfData}",
                            ],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 4000,
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('OpenAI API error: ' . $response->body());
        }
        
        $data = $response->json();
        return $data['choices'][0]['message']['content'] ?? '';
    }
    
    /**
     * Extract text from PDF using Anthropic
     */
    protected function extractTextFromPdfAnthropic(string $pdfData): string
    {
        $apiKey = \App\Models\PlatformSetting::get('anthropic_api_key');
        $model = \App\Models\PlatformSetting::get('anthropic_model', 'claude-sonnet-4-20250514');
        
        // Anthropic supports PDF files directly
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 4000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => 'application/pdf',
                                'data' => $pdfData,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Extract all text and information from this PDF document. This could be a bank statement, invoice, receipt, or other business document. Include all numbers, dates, amounts, descriptions, transaction details, and any other relevant information.',
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

