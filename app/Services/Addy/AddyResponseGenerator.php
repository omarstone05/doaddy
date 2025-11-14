<?php

namespace App\Services\Addy;

use App\Models\Organization;
use App\Services\Addy\Agents\MoneyAgent;
use App\Services\Addy\Agents\SalesAgent;
use App\Services\Addy\Agents\PeopleAgent;
use App\Services\Addy\Agents\InventoryAgent;
use App\Services\AI\AIService;
use App\Services\Document\DocumentContextService;

class AddyResponseGenerator
{
    protected Organization $organization;
    protected AddyCoreService $core;
    protected ?\App\Models\User $user;

    public function __construct(Organization $organization, ?\App\Models\User $user = null)
    {
        $this->organization = $organization;
        $this->core = new AddyCoreService($organization);
        $this->user = $user ?? request()->user();
    }

    /**
     * Format currency amount with organization's currency
     */
    protected function formatCurrency(float $amount): string
    {
        $currency = $this->organization->currency ?? 'ZMW';
        return $currency . ' ' . number_format($amount, 2);
    }

    /**
     * Generate response based on intent
     * NEW FLOW: OpenAI handles all conversation, code assists with data/actions
     */
    public function generateResponse(array $intent, string $userMessage, array $chatHistory = [], array $extractedData = []): array
    {
        // Check if user pasted bank statement text directly
        if ($intent['intent'] === 'action' && 
            $intent['action_type'] === 'create_transaction' && 
            isset($intent['parameters']['is_bank_statement_text']) && 
            $intent['parameters']['is_bank_statement_text'] === true) {
            
            // Process the pasted text as if it were extracted from a document
            $rawText = $intent['parameters']['raw_text'] ?? $userMessage;
            $extractedData = $this->processBankStatementText($rawText);
            
            if (!empty($extractedData)) {
                $intent = $this->createIntentFromExtractedData($extractedData, $intent);
            }
        }
        
        // If we have extracted data from files, try to create transaction action
        if (!empty($extractedData)) {
            $intent = $this->createIntentFromExtractedData($extractedData, $intent);
        }
        
        // Actions are handled by code (create transaction, send invoice, etc.)
        if ($intent['intent'] === 'action') {
            // Handle bank statements with multiple transactions differently
            if ($intent['action_type'] === 'create_transaction' && isset($intent['parameters']['transactions']) && is_array($intent['parameters']['transactions'])) {
                return $this->handleBankStatementTransactions($intent, $userMessage, $extractedData);
            }
            
            // If parameters are missing, try to extract from chat history or extracted data
            if (empty($intent['parameters']) || (!isset($intent['parameters']['amount']) && !isset($intent['parameters']['transactions']))) {
                $intent = $this->enrichActionFromHistory($intent, $chatHistory, $userMessage, $extractedData);
            }
            return $this->handleActionRequest($intent, $userMessage);
        }
        
        // For all other queries (including greetings, data queries, general conversation):
        // 1. Get data from code if it's a data query
        // 2. Pass everything to OpenAI with cultural context to format conversationally
        $dataContext = $this->getDataContext($intent);
        
        return $this->handleConversationalQuery($userMessage, $chatHistory, $intent, $dataContext);
    }
    
    /**
     * Process pasted bank statement text
     */
    protected function processBankStatementText(string $text): array
    {
        try {
            // Use the same structured data extraction as documents
            $processor = new \App\Services\Addy\DocumentProcessorService();
            $reflection = new \ReflectionClass($processor);
            $method = $reflection->getMethod('extractStructuredData');
            $method->setAccessible(true);
            
            $extractedData = $method->invoke($processor, $text, 'text/plain');
            
            if (!empty($extractedData) && isset($extractedData['document_type']) && $extractedData['document_type'] === 'bank_statement') {
                return [$extractedData];
            }
            
            // If AI didn't recognize it as bank_statement, try to parse it manually
            return [$this->parseBankStatementTextManually($text)];
        } catch (\Exception $e) {
            \Log::error('Failed to process bank statement text', ['error' => $e->getMessage()]);
            // Fallback to manual parsing
            return [$this->parseBankStatementTextManually($text)];
        }
    }
    
    /**
     * Manually parse bank statement text
     */
    protected function parseBankStatementTextManually(string $text): array
    {
        $transactions = [];
        $lines = explode("\n", $text);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || preg_match('/^(Date|Description|Amount|Balance|Sep|Description Amount Balance|Accrued)$/i', $line)) {
                continue; // Skip headers
            }
            
            // Pattern 1: Multiple dates at start, then description, amount, balance
            // Example: "Sep 23 Sep 23 Sep 24 POS Purchase Cremosa Investment 426094*6476 17 Sep 200.00 2,709.57Cr"
            if (preg_match('/^((?:\w+\s+\d{1,2}\s+)+)(.+?)\s+(\d+(?:[.,]\d{2})?)\s+(\d+(?:[.,]\d{3})*(?:\.\d{2})?)?\s*(Cr|Dr)?/i', $line, $matches)) {
                $dates = trim($matches[1]);
                $description = trim($matches[2]);
                $amount = str_replace(',', '', $matches[3] ?? '0');
                $balance = isset($matches[4]) ? str_replace(',', '', $matches[4]) : null;
                $type = isset($matches[5]) && strtolower($matches[5]) === 'cr' ? 'credit' : 'debit';
                
                // Get the last date from the dates string
                if (preg_match('/(\w+\s+\d{1,2})\s*$/', $dates, $dateMatch)) {
                    $date = $dateMatch[1];
                } else {
                    $date = trim(explode(' ', $dates)[0] ?? '');
                }
                
                // Determine if it's income or expense based on description
                $flowType = 'expense';
                if (preg_match('/\b(payment|credit|deposit|income|received|FNB OB Pmt|Magtape Credit|Wallet To Bank)\b/i', $description)) {
                    $flowType = 'income';
                }
                
                if (is_numeric($amount) && $amount > 0) {
                    $transactions[] = [
                        'date' => $date,
                        'amount' => (float) $amount,
                        'description' => $description,
                        'type' => $type,
                        'flow_type' => $flowType,
                        'balance' => $balance ? (float) $balance : null,
                    ];
                }
                continue;
            }
            
            // Pattern 2: Description, amount, balance (no dates at start)
            // Example: "Bank Charges 10.00"
            if (preg_match('/^([A-Z][^0-9]+?)\s+(\d+(?:[.,]\d{2})?)\s+(\d+(?:[.,]\d{3})*(?:\.\d{2})?)?\s*(Cr|Dr)?/i', $line, $matches)) {
                $description = trim($matches[1]);
                $amount = str_replace(',', '', $matches[2] ?? '0');
                $balance = isset($matches[3]) ? str_replace(',', '', $matches[3]) : null;
                $type = isset($matches[4]) && strtolower($matches[4]) === 'cr' ? 'credit' : 'debit';
                
                // Determine flow type
                $flowType = 'expense';
                if (preg_match('/\b(payment|credit|deposit|income|received|FNB OB Pmt|Magtape Credit|Wallet To Bank)\b/i', $description)) {
                    $flowType = 'income';
                }
                
                if (is_numeric($amount) && $amount > 0) {
                    $transactions[] = [
                        'date' => null,
                        'amount' => (float) $amount,
                        'description' => $description,
                        'type' => $type,
                        'flow_type' => $flowType,
                        'balance' => $balance ? (float) $balance : null,
                    ];
                }
            }
        }
        
        if (empty($transactions)) {
            // Fallback: use AI to extract
            try {
                $processor = new \App\Services\Addy\DocumentProcessorService();
                $reflection = new \ReflectionClass($processor);
                $method = $reflection->getMethod('extractStructuredData');
                $method->setAccessible(true);
                return $method->invoke($processor, $text, 'text/plain');
            } catch (\Exception $e) {
                \Log::error('Failed to extract bank statement data', ['error' => $e->getMessage()]);
            }
        }
        
        return [
            'document_type' => 'bank_statement',
            'type' => 'bank_statement',
            'transactions' => $transactions,
            'raw_text' => $text,
        ];
    }
    
    /**
     * Create intent from extracted document data
     */
    protected function createIntentFromExtractedData(array $extractedData, array $currentIntent): array
    {
        // Use the first extracted data item
        $data = $extractedData[0] ?? [];
        
        // Check document type and create appropriate intent
        $documentType = $data['document_type'] ?? $data['type'] ?? null;
        
        // Handle quotes
        if ($documentType === 'quote' || (isset($data['quote_number']) && !empty($data['quote_number']))) {
            return [
                'intent' => 'action',
                'action_type' => 'create_quote',
                'parameters' => [
                    'customer_id' => $data['customer_id'] ?? null,
                    'customer_name' => $data['customer_name'] ?? $data['merchant'] ?? null,
                    'quote_date' => $data['date'] ?? now()->toDateString(),
                    'expiry_date' => $data['expiry_date'] ?? null,
                    'quote_number' => $data['quote_number'] ?? null,
                    'items' => $data['items'] ?? (isset($data['amount']) ? [[
                        'description' => $data['description'] ?? 'Quote item',
                        'quantity' => 1,
                        'unit_price' => (float) $data['amount'],
                    ]] : []),
                    'total_amount' => (float) ($data['amount'] ?? 0),
                    'notes' => $data['description'] ?? null,
                ],
            ];
        }
        
        // Handle bank statements - these have multiple transactions
        if ($documentType === 'bank_statement' && isset($data['transactions']) && is_array($data['transactions']) && count($data['transactions']) > 0) {
            // For bank statements, we'll let the AI handle it conversationally
            // and create transactions one by one or in bulk
            // The AI will process the transactions array and create them
            return [
                'intent' => 'action',
                'action_type' => 'create_transaction',
                'parameters' => [
                    'transactions' => $data['transactions'], // Array of transactions
                    'account_number' => $data['account_number'] ?? null,
                    'statement_period_start' => $data['statement_period_start'] ?? null,
                    'statement_period_end' => $data['statement_period_end'] ?? null,
                    'opening_balance' => $data['opening_balance'] ?? null,
                    'closing_balance' => $data['closing_balance'] ?? null,
                ],
            ];
        }
        
        // Handle client lists
        if ($documentType === 'client_list' && isset($data['clients']) && is_array($data['clients'])) {
            // For now, return a conversational response to handle client import
            // This could be expanded to create an import action later
            return $currentIntent; // Let AI handle it conversationally
        }
        
        // Handle invoices
        if ($documentType === 'invoice' || ($data['type'] === 'invoice')) {
            // For invoices, we need customer_id, items, etc.
            // If we have customer info, use it; otherwise, we'll need to ask
            $totalAmount = (float) ($data['amount'] ?? $data['total_amount'] ?? 0);
            
            return [
                'intent' => 'action',
                'action_type' => 'create_invoice',
                'parameters' => [
                    'customer_id' => $data['customer_id'] ?? null,
                    'customer_name' => $data['customer_name'] ?? $data['merchant'] ?? null,
                    'invoice_date' => $data['date'] ?? now()->toDateString(),
                    'due_date' => $data['due_date'] ?? null,
                    'items' => $data['items'] ?? ($totalAmount > 0 ? [[
                        'description' => $data['description'] ?? 'Invoice item',
                        'quantity' => 1,
                        'unit_price' => $totalAmount,
                    ]] : []),
                    'total_amount' => $totalAmount,
                    'notes' => $data['description'] ?? null,
                ],
            ];
        }
        
        // Otherwise, treat as transaction (income/expense)
        // Try multiple ways to find amount
        $amount = $data['amount'] ?? $data['total_amount'] ?? $data['total'] ?? null;
        
        // If we have an amount, create transaction intent
        if ($amount !== null && is_numeric($amount)) {
            // Determine flow type
            $flowType = 'expense'; // Default
            if (isset($data['type'])) {
                $flowType = $data['type'] === 'income' ? 'income' : 'expense';
            } elseif (isset($data['document_type'])) {
                if ($data['document_type'] === 'income' || $data['document_type'] === 'receipt') {
                    $flowType = 'expense'; // Receipts are expenses
                } elseif ($data['document_type'] === 'income') {
                    $flowType = 'income';
                }
            }
            
            return [
                'intent' => 'action',
                'action_type' => 'create_transaction',
                'parameters' => [
                    'amount' => (float) $amount,
                    'flow_type' => $flowType,
                    'currency' => $data['currency'] ?? 'ZMW',
                    'description' => $data['description'] ?? $data['merchant'] ?? 'Transaction from document',
                    'category' => $data['category'] ?? null,
                    'date' => $data['date'] ?? null,
                ],
            ];
        }
        
        // If we have extracted text but no structured amount, try to extract from text
        if (isset($data['raw_text']) || isset($data['raw_extraction'])) {
            $rawText = $data['raw_text'] ?? $data['raw_extraction'] ?? '';
            // Try to find amount in text using regex
            if (preg_match('/\$\s*(\d+(?:[.,]\d{2})?)|(\d+(?:[.,]\d{2})?)\s*(?:ZMW|USD|EUR|GBP)/i', $rawText, $matches)) {
                $amount = str_replace(',', '', $matches[1] ?? $matches[2] ?? '0');
                if (is_numeric($amount) && $amount > 0) {
                    return [
                        'intent' => 'action',
                        'action_type' => 'create_transaction',
                        'parameters' => [
                            'amount' => (float) $amount,
                            'flow_type' => 'expense', // Default to expense
                            'currency' => $data['currency'] ?? 'ZMW',
                            'description' => 'Transaction from uploaded document',
                            'category' => null,
                            'date' => null,
                        ],
                    ];
                }
            }
        }
        
        // Log what we got for debugging
        \Log::warning('Could not extract amount from document data', [
            'data_keys' => array_keys($data),
            'document_type' => $data['document_type'] ?? null,
            'has_amount' => isset($data['amount']),
            'has_total' => isset($data['total_amount']),
        ]);
        
        return $currentIntent;
    }
    
    protected function monthToNumber(string $month): string
    {
        $months = [
            'january' => '01', 'jan' => '01',
            'february' => '02', 'feb' => '02',
            'march' => '03', 'mar' => '03',
            'april' => '04', 'apr' => '04',
            'may' => '05',
            'june' => '06', 'jun' => '06',
            'july' => '07', 'jul' => '07',
            'august' => '08', 'aug' => '08',
            'september' => '09', 'sep' => '09',
            'october' => '10', 'oct' => '10',
            'november' => '11', 'nov' => '11',
            'december' => '12', 'dec' => '12',
        ];
        
        return $months[strtolower($month)] ?? '01';
    }
    
    /**
     * Enrich action parameters from chat history
     */
    protected function enrichActionFromHistory(array $intent, array $chatHistory, string $currentMessage, array $extractedData = []): array
    {
        // If this is a create invoice action, enrich with customer and item details
        if ($intent['action_type'] === 'create_invoice') {
            // First, try to use extracted data from files
            if (!empty($extractedData)) {
                $data = $extractedData[0] ?? [];
                if (isset($data['customer_name']) && !isset($intent['parameters']['customer_id']) && !isset($intent['parameters']['customer_name'])) {
                    $intent['parameters']['customer_name'] = $data['customer_name'];
                }
                if (isset($data['items']) && empty($intent['parameters']['items'])) {
                    $intent['parameters']['items'] = $data['items'];
                }
                $amount = $data['amount'] ?? $data['total_amount'] ?? null;
                if ($amount && !isset($intent['parameters']['total_amount'])) {
                    $intent['parameters']['total_amount'] = (float) $amount;
                }
                if (isset($data['date']) && !isset($intent['parameters']['invoice_date'])) {
                    $intent['parameters']['invoice_date'] = $data['date'];
                }
                if (isset($data['due_date']) && !isset($intent['parameters']['due_date'])) {
                    $intent['parameters']['due_date'] = $data['due_date'];
                }
            }
            
            // Also extract from the current message if not already set
            $fullContext = $currentMessage;
            foreach ($chatHistory as $msg) {
                if ($msg['role'] === 'user') {
                    $fullContext .= ' ' . $msg['content'];
                }
            }
            
            // Extract customer name from message if not set
            if (!isset($intent['parameters']['customer_id']) && !isset($intent['parameters']['customer_name'])) {
                if (preg_match('/(?:for|to)\s+([a-z\s]+?)(?:\s+(?:from|on|dated|invoice)|$|,|\.)/i', $fullContext, $matches)) {
                    $intent['parameters']['customer_name'] = trim($matches[1]);
                }
            }
            
            // Extract amount from message if not set
            if (!isset($intent['parameters']['total_amount'])) {
                if (preg_match('/\$?\s*(\d{1,3}(?:\s+\d{3})*(?:\.\d{2})?|\d{1,3}(?:,\d{3})*(?:\.\d{2})?|\d+(?:\.\d{2})?)/', $fullContext, $matches)) {
                    $amount = str_replace([' ', ','], '', $matches[1]);
                    $intent['parameters']['total_amount'] = (float) $amount;
                }
            }
            
            // Extract date from message if not set
            if (!isset($intent['parameters']['invoice_date'])) {
                if (preg_match('/(\d{1,2})\s+(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\s+(\d{4})/i', $fullContext, $matches)) {
                    $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $month = $this->monthToNumber($matches[2]);
                    $year = $matches[3];
                    $intent['parameters']['invoice_date'] = "{$year}-{$month}-{$day}";
                } elseif (preg_match('/(\d{4}-\d{2}-\d{2})|(\d{1,2}\/\d{1,2}\/\d{4})/', $fullContext, $matches)) {
                    $intent['parameters']['invoice_date'] = $matches[0];
                }
            }
            
            // Combine all previous messages to search for parameters
            $fullContext = '';
            foreach ($chatHistory as $msg) {
                if ($msg['role'] === 'user') {
                    $fullContext .= ' ' . $msg['content'];
                }
            }
            $fullContext .= ' ' . $currentMessage;
            
            // Extract customer name from history if not already set
            if (!isset($intent['parameters']['customer_id']) && !isset($intent['parameters']['customer_name'])) {
                if (preg_match('/(?:for|to)\s+([a-z\s]+?)(?:\s|$|,|\.|for|invoice)/i', $fullContext, $matches)) {
                    $intent['parameters']['customer_name'] = trim($matches[1]);
                }
            }
            
            // Extract amount from history
            if (!isset($intent['parameters']['total_amount']) && preg_match('/\$?(\d+(?:\.\d{2})?)/', $fullContext, $matches)) {
                $intent['parameters']['total_amount'] = (float) $matches[1];
            }
        }
        
        // If this is a confirm/create transaction action, look for expense details in history
        if ($intent['action_type'] === 'create_transaction') {
            // First, try to use extracted data from files
            if (!empty($extractedData)) {
                $data = $extractedData[0] ?? [];
                if (isset($data['amount']) && !isset($intent['parameters']['amount'])) {
                    $intent['parameters']['amount'] = (float) $data['amount'];
                }
                if (isset($data['type']) && !isset($intent['parameters']['flow_type'])) {
                    $intent['parameters']['flow_type'] = $data['type'] === 'income' ? 'income' : 'expense';
                }
                if (isset($data['currency']) && !isset($intent['parameters']['currency'])) {
                    $intent['parameters']['currency'] = $data['currency'];
                }
                if (isset($data['description']) && !isset($intent['parameters']['description'])) {
                    $intent['parameters']['description'] = $data['description'];
                }
                if (isset($data['category']) && !isset($intent['parameters']['category'])) {
                    $intent['parameters']['category'] = $data['category'];
                }
                if (isset($data['date']) && !isset($intent['parameters']['date'])) {
                    $intent['parameters']['date'] = $data['date'];
                }
            }
            
            // Combine all previous messages to search for parameters
            $fullContext = '';
            foreach ($chatHistory as $msg) {
                if ($msg['role'] === 'user') {
                    $fullContext .= ' ' . $msg['content'];
                }
            }
            $fullContext .= ' ' . $currentMessage;
            
            // Extract amount from history if not already set
            if (!isset($intent['parameters']['amount']) && preg_match('/\$?(\d+(?:\.\d{2})?)/', $fullContext, $matches)) {
                $intent['parameters']['amount'] = (float) $matches[1];
            }
            
            // Extract flow type from history
            if (!isset($intent['parameters']['flow_type'])) {
                if (stripos($fullContext, 'expense') !== false || stripos($fullContext, 'cost') !== false || 
                    stripos($fullContext, 'spent') !== false || stripos($fullContext, 'bought') !== false) {
                    $intent['parameters']['flow_type'] = 'expense';
                } elseif (stripos($fullContext, 'income') !== false || stripos($fullContext, 'revenue') !== false) {
                    $intent['parameters']['flow_type'] = 'income';
                } else {
                    $intent['parameters']['flow_type'] = 'expense'; // Default
                }
            }
            
            // Extract category from history
            if (!isset($intent['parameters']['category'])) {
                // Look for common patterns
                if (preg_match('/for\s+([a-z\s]+?)(?:\s|$|,|\.)/i', $fullContext, $matches)) {
                    $intent['parameters']['category'] = trim($matches[1]);
                } elseif (preg_match('/(?:dog|office|wellness|support)/i', $fullContext, $matches)) {
                    // Extract context-based category
                    if (stripos($fullContext, 'dog') !== false || stripos($fullContext, 'wellness') !== false) {
                        $intent['parameters']['category'] = 'Wellness';
                    } elseif (stripos($fullContext, 'office') !== false) {
                        $intent['parameters']['category'] = 'Office';
                    }
                }
            }
            
            // Extract description from history
            if (!isset($intent['parameters']['description'])) {
                // Try to find a descriptive phrase
                if (preg_match('/(?:bought|purchased|spent on)\s+([^\.]+?)(?:\.|$)/i', $fullContext, $matches)) {
                    $intent['parameters']['description'] = trim($matches[1]);
                }
            }
        }
        
        return $intent;
    }
    
    /**
     * Get data context from code for data queries
     */
    protected function getDataContext(array $intent): ?array
    {
        switch ($intent['intent']) {
            case 'query_cash':
                $agent = new MoneyAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'cash',
                    'cash_position' => $data['cash_position'],
                    'top_expenses' => $data['top_expenses'],
                ];
            
            case 'query_budget':
                $agent = new MoneyAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'budget',
                    'budget_health' => $data['budget_health'],
                ];
            
            case 'query_expenses':
                $agent = new MoneyAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'expenses',
                    'monthly_burn' => $data['monthly_burn'],
                    'trends' => $data['trends'],
                    'top_expenses' => $data['top_expenses'],
                ];
            
            case 'query_transactions':
                $agent = new MoneyAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'transactions',
                    'latest_transactions' => $data['latest_transactions'] ?? [],
                ];
            
            case 'query_invoices':
                $agent = new SalesAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'invoices',
                    'invoice_health' => $data['invoice_health'],
                    'filter' => $intent['type'] ?? 'all',
                ];
            
            case 'query_sales':
                $agent = new SalesAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'sales',
                    'sales_performance' => $data['sales_performance'],
                    'customer_stats' => $data['customer_stats'],
                ];
            
            case 'query_team':
                $agent = new PeopleAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'team',
                    'team_stats' => $data['team_stats'],
                    'leave_patterns' => $data['leave_patterns'],
                ];
            
            case 'query_payroll':
                $agent = new PeopleAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'payroll',
                    'payroll_health' => $data['payroll_health'],
                ];
            
            case 'query_inventory':
                $agent = new InventoryAgent($this->organization);
                $data = $agent->perceive();
                return [
                    'type' => 'inventory',
                    'stock_levels' => $data['stock_levels'],
                    'low_stock_items' => $data['low_stock_items'],
                ];
            
            case 'query_focus':
                $state = $this->core->getState();
                $insights = $this->core->getActiveInsights();
                return [
                    'type' => 'focus',
                    'focus_area' => $state->focus_area,
                    'context' => $state->context,
                    'insights_count' => $insights->count(),
                ];
            
            case 'query_insights':
                $insights = $this->core->getActiveInsights();
                return [
                    'type' => 'insights',
                    'insights' => $insights->map(fn($i) => [
                        'title' => $i->title,
                        'description' => $i->description,
                        'priority' => $i->priority,
                        'category' => $i->category,
                    ])->toArray(),
                ];
            
            default:
                return null;
        }
    }

    protected function handleGreeting(): array
    {
        $culturalEngine = new \App\Services\Addy\AddyCulturalEngine(
            $this->organization, 
            auth()->user()
        );
        
        $greeting = $culturalEngine->getContextualGreeting();
        
        $state = $this->core->getState();
        $insights = $this->core->getActiveInsights();
        
        $response = $greeting . "\n\n";
        
        if ($insights->count() > 0) {
            $response .= "I have {$insights->count()} insight(s) for you. ";
        }
        
        if ($state->focus_area) {
            $response .= "Currently focusing on: {$state->focus_area}.";
        }
        
        // Add predictions if enabled
        if ($culturalEngine->shouldShowPredictions()) {
            $prediction = \App\Models\AddyPrediction::getLatest(
                $this->organization->id, 
                'cash_flow', 
                today()->addDays(30)->format('Y-m-d')
            );
            
            if ($prediction) {
                $value = $this->formatCurrency($prediction->predicted_value);
                $confidence = number_format($prediction->confidence * 100, 0);
                $response .= "\n\n**Prediction:** In 30 days, your cash position will be around {$value} ({$confidence}% confidence).";
            }
        }
        
        $response .= "\n\n" . $culturalEngine->getRecommendedFocus();
        
        // Proactive suggestion
        $suggestion = $culturalEngine->getProactiveSuggestion();
        if ($suggestion) {
            $response .= "\n\n" . $suggestion['message'];
        }
        
        $quickActions = [
            ['label' => 'Cash Position', 'command' => 'What is my cash position?'],
            ['label' => 'Top Expenses', 'command' => 'Show me top expenses'],
            ['label' => 'Daily Focus', 'command' => 'What should I focus on today?'],
        ];
        
        if ($suggestion && isset($suggestion['actions'])) {
            $quickActions = array_merge($quickActions, $suggestion['actions']);
        }
        
        return [
            'content' => $culturalEngine->adaptTone($response),
            'quick_actions' => $quickActions,
        ];
    }

    protected function handleCashQuery(): array
    {
        $agent = new MoneyAgent($this->organization);
        $data = $agent->perceive();
        
        $cash = $this->formatCurrency($data['cash_position']);
        $topExpenses = $data['top_expenses'];
        
        $response = "Your current cash position is **{$cash}** across all accounts.\n\n";
        
        if (!empty($topExpenses)) {
            $response .= "**Top 3 expenses this month:**\n";
            foreach ($topExpenses as $expense) {
                $amount = $this->formatCurrency($expense['amount']);
                $response .= "• {$expense['category']}: {$amount}\n";
            }
        }
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => 'View Accounts', 'url' => '/money/accounts'],
                ['label' => 'View Expenses', 'url' => '/money/movements'],
                ['label' => 'View Budgets', 'url' => '/money/budgets'],
            ],
        ];
    }

    protected function handleBudgetQuery(): array
    {
        $agent = new MoneyAgent($this->organization);
        $data = $agent->perceive();
        $budgetHealth = $data['budget_health'];
        
        $response = "**Budget Status:**\n\n";
        
        if (!empty($budgetHealth['overrun'])) {
            $response .= "**Over Budget:**\n";
            foreach ($budgetHealth['overrun'] as $budget) {
                $percentage = number_format($budget['percentage'], 0);
                $response .= "• {$budget['name']}: {$percentage}% spent\n";
            }
            $response .= "\n";
        }
        
        if (!empty($budgetHealth['warning'])) {
            $response .= "**Approaching Limit:**\n";
            foreach ($budgetHealth['warning'] as $budget) {
                $percentage = number_format($budget['percentage'], 0);
                $response .= "• {$budget['name']}: {$percentage}% spent\n";
            }
            $response .= "\n";
        }
        
        if (!empty($budgetHealth['healthy'])) {
            $healthyCount = count($budgetHealth['healthy']);
            $response .= "**Healthy:**\n";
            $response .= "• {$healthyCount} budget(s) in good standing\n";
        }
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => '📊 View All Budgets', 'url' => '/money/budgets'],
            ],
        ];
    }

    protected function handleExpensesQuery(): array
    {
        $agent = new MoneyAgent($this->organization);
        $data = $agent->perceive();
        
        $burn = $this->formatCurrency($data['monthly_burn']);
        $trends = $data['trends'];
        $topExpenses = $data['top_expenses'];
        
        $response = "**Expense Overview:**\n\n";
        $response .= "**Monthly burn:** {$burn}\n\n";
        
        if ($trends['trend'] === 'increasing') {
            $response .= "Spending is **up {$trends['change_percentage']}%** from last month.\n\n";
        } elseif ($trends['trend'] === 'decreasing') {
            $response .= "Spending is **down " . abs($trends['change_percentage']) . "%** from last month.\n\n";
        } else {
            $response .= "Spending is **stable** compared to last month.\n\n";
        }
        
        if (!empty($topExpenses)) {
            $response .= "**Top categories:**\n";
            foreach ($topExpenses as $expense) {
                $amount = $this->formatCurrency($expense['amount']);
                $response .= "• {$expense['category']}: {$amount}\n";
            }
        }
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => 'View Transactions', 'url' => '/money/movements'],
                ['label' => '📊 View Report', 'url' => '/reports/expenses'],
            ],
        ];
    }

    protected function handleInvoicesQuery(string $type): array
    {
        $agent = new SalesAgent($this->organization);
        $data = $agent->perceive();
        $invoiceHealth = $data['invoice_health'];
        
        $response = "**Invoice Status:**\n\n";
        
        if ($type === 'overdue' || $type === 'all') {
            if ($invoiceHealth['overdue_count'] > 0) {
                $amount = $this->formatCurrency($invoiceHealth['overdue_amount']);
                $response .= "**{$invoiceHealth['overdue_count']} overdue invoice(s)** totaling {$amount}\n\n";
            }
        }
        
        if ($type === 'pending' || $type === 'all') {
            if ($invoiceHealth['pending_count'] > 0) {
                $amount = $this->formatCurrency($invoiceHealth['pending_amount']);
                $response .= "**{$invoiceHealth['pending_count']} pending invoice(s)** totaling {$amount}\n\n";
            }
        }
        
        if ($invoiceHealth['overdue_count'] === 0 && $invoiceHealth['pending_count'] === 0) {
            $response .= "All invoices are paid! Great work.\n";
        }
        
        $quickActions = [
            ['label' => '📄 View All Invoices', 'url' => '/invoices'],
        ];
        
        if ($invoiceHealth['overdue_count'] > 0) {
            $quickActions[] = ['label' => 'Send Reminders', 'command' => 'Draft payment reminder emails'];
        }
        
        return [
            'content' => $response,
            'quick_actions' => $quickActions,
        ];
    }

    protected function handleSalesQuery(): array
    {
        $agent = new SalesAgent($this->organization);
        $data = $agent->perceive();
        
        $performance = $data['sales_performance'];
        $customers = $data['customer_stats'];
        
        $thisMonth = $this->formatCurrency($performance['current_month']);
        $lastMonth = $this->formatCurrency($performance['last_month']);
        
        $response = "**Sales Performance:**\n\n";
        $response .= "**This month:** {$thisMonth}\n";
        $response .= "**Last month:** {$lastMonth}\n\n";
        
        if ($performance['trend'] === 'increasing') {
            $response .= "Sales are **up {$performance['change_percentage']}%**! Excellent work.\n\n";
        } elseif ($performance['trend'] === 'decreasing') {
            $response .= "Sales are **down " . abs($performance['change_percentage']) . "%**. Time to review strategy.\n\n";
        } else {
            $response .= "Sales are **stable**.\n\n";
        }
        
        $response .= "**Customer stats:**\n";
        $response .= "• Total customers: {$customers['total']}\n";
        $response .= "• Active: {$customers['active']}\n";
        $response .= "• New this month: {$customers['new_this_month']}\n";
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => '📊 Sales Report', 'url' => '/reports/sales'],
                ['label' => 'View Customers', 'url' => '/customers'],
            ],
        ];
    }

    protected function handleTeamQuery(): array
    {
        $agent = new PeopleAgent($this->organization);
        $data = $agent->perceive();
        
        $stats = $data['team_stats'];
        $leave = $data['leave_patterns'];
        
        $response = "**Team Overview:**\n\n";
        $response .= "**Total team members:** {$stats['total']}\n";
        $response .= "**Active:** {$stats['active']}\n";
        $response .= "**On leave:** {$stats['on_leave']}\n\n";
        
        if ($stats['new_this_month'] > 0) {
            $response .= "Welcome to {$stats['new_this_month']} new team member(s) this month!\n\n";
        }
        
        if ($leave['pending_requests'] > 0) {
            $response .= "{$leave['pending_requests']} leave request(s) pending approval.\n";
        }
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => 'View Team', 'url' => '/team'],
                ['label' => 'Leave Requests', 'url' => '/leave/requests'],
            ],
        ];
    }

    protected function handlePayrollQuery(): array
    {
        $agent = new PeopleAgent($this->organization);
        $data = $agent->perceive();
        
        $payroll = $data['payroll_health'];
        
        $response = "**Payroll Status:**\n\n";
        
        if ($payroll['next_payroll_date']) {
            $amount = $this->formatCurrency($payroll['next_payroll_amount']);
            $days = $payroll['days_until_payroll'];
            
            $response .= "**Next payroll:** {$amount}\n";
            $response .= "**Due in:** {$days} day(s)\n\n";
            
            if ($days <= 7) {
                $response .= "Payroll is due soon. Make sure funds are available.\n";
            }
        } else {
            $response .= "No upcoming payroll scheduled.\n";
        }
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => 'View Payroll', 'url' => '/payroll/runs'],
            ],
        ];
    }

    protected function handleInventoryQuery(): array
    {
        $agent = new InventoryAgent($this->organization);
        $data = $agent->perceive();
        
        $levels = $data['stock_levels'];
        $value = $this->formatCurrency($data['inventory_value']);
        
        $response = "**Inventory Status:**\n\n";
        $response .= "**Total products:** {$levels['total_products']}\n";
        $response .= "**Total value:** {$value}\n\n";
        
        $response .= "**Stock levels:**\n";
        $response .= "Healthy: {$levels['healthy']}\n";
        
        if ($levels['low_stock'] > 0) {
            $response .= "Low stock: {$levels['low_stock']}\n";
        }
        
        if ($levels['out_of_stock'] > 0) {
            $response .= "Out of stock: {$levels['out_of_stock']}\n";
        }
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => 'View Inventory', 'url' => '/stock'],
                ['label' => 'Stock Movements', 'url' => '/stock/movements'],
            ],
        ];
    }

    protected function handleFocusQuery(): array
    {
        $state = $this->core->getState();
        $thought = $this->core->getCurrentThought();
        
        $response = "**Your Focus Today:**\n\n";
        $response .= "**Area:** {$state->focus_area}\n";
        $response .= "**Context:** {$state->context}\n\n";
        
        if (!empty($state->priorities)) {
            $response .= "**Priorities:**\n";
            foreach ($state->priorities as $priority) {
                $response .= "• {$priority}\n";
            }
        }
        
        return [
            'content' => $response,
            'quick_actions' => [
                ['label' => 'View Insights', 'command' => 'Show me all insights'],
            ],
        ];
    }

    protected function handleInsightsQuery(): array
    {
        $insights = $this->core->getActiveInsights();
        
        if ($insights->isEmpty()) {
            return [
                'content' => "✨ All clear! No urgent insights right now. Your business is running smoothly.",
            ];
        }
        
        $response = "**Active Insights ({$insights->count()}):**\n\n";
        
        foreach ($insights->take(3) as $insight) {
            $response .= "**{$insight->title}**\n";
            $response .= "   {$insight->description}\n\n";
        }
        
        if ($insights->count() > 3) {
            $more = $insights->count() - 3;
            $response .= "_+ {$more} more insight(s)_\n";
        }
        
        return [
            'content' => $response,
        ];
    }

    protected function handleActionRequest(array $intent, string $userMessage): array
    {
        $executionService = new \App\Services\Addy\ActionExecutionService(
            $this->organization,
            auth()->user()
        );

        try {
            // Prepare action
            $action = $executionService->prepareAction(
                $intent['action_type'],
                $intent['parameters'] ?? []
            );

            $preview = $action->preview_data;

            $response = "**{$preview['title']}**\n\n";
            $response .= "{$preview['description']}\n\n";

            // Show items
            if (!empty($preview['items'])) {
                foreach (array_slice($preview['items'], 0, 5) as $item) {
                    $response .= $this->formatActionItem($item) . "\n";
                }

                if (count($preview['items']) > 5) {
                    $more = count($preview['items']) - 5;
                    $response .= "\n_+ {$more} more item(s)_\n";
                }
            }

            // Show warnings
            if (!empty($preview['warnings'])) {
                $response .= "\n**Warnings:**\n";
                foreach ($preview['warnings'] as $warning) {
                    $response .= "- {$warning}\n";
                }
            }

            return [
                'content' => $response,
                'action' => [
                    'action_id' => $action->id,
                    'requires_confirmation' => true,
                    'preview' => $preview,
                ],
                'quick_actions' => [
                    ['label' => 'Confirm & Execute', 'action_id' => $action->id, 'type' => 'confirm'],
                    ['label' => 'Edit', 'action_id' => $action->id, 'type' => 'edit'],
                    ['label' => 'Cancel', 'action_id' => $action->id, 'type' => 'cancel'],
                ],
            ];

        } catch (\Exception $e) {
            return [
                'content' => "I couldn't prepare that action: {$e->getMessage()}",
            ];
        }
    }

    protected function formatActionItem(array $item): string
    {
        // Format based on item type
        if (isset($item['customer'])) {
            // Invoice preview or reminder
            if (isset($item['invoice_number'])) {
                // Invoice reminder (has invoice_number)
                $amount = $this->formatCurrency($item['amount']);
                return "**{$item['customer']}** - Invoice #{$item['invoice_number']} "
                    . "({$amount}, {$item['days_overdue']} days overdue)";
            } else {
                // Invoice preview (no invoice_number yet)
                $amount = $this->formatCurrency($item['amount']);
                $date = isset($item['date']) ? " on {$item['date']}" : '';
                return "**{$item['customer']}** - {$amount}{$date}";
            }
        }

        if (isset($item['type'])) {
            // Transaction
            $category = $item['category'] ?? 'Uncategorized';
            $account = $item['account'] ?? 'No account';
            $amount = $this->formatCurrency($item['amount']);
            return "**{$item['type']}** - {$amount} "
                . "({$category}) - {$account}";
        }
        
        // Bank statement transaction item
        if (isset($item['flow_type']) || isset($item['description'])) {
            $flowType = $item['flow_type'] ?? ($item['type'] === 'credit' ? 'income' : 'expense');
            $amount = $this->formatCurrency($item['amount'] ?? 0);
            $date = $item['date'] ?? 'Unknown date';
            $description = substr($item['description'] ?? 'No description', 0, 50);
            return "**{$date}** - {$flowType} {$amount} - {$description}";
        }

        // Generic
        return "• " . json_encode($item);
    }
    
    /**
     * Handle bank statement with multiple transactions
     */
    protected function handleBankStatementTransactions(array $intent, string $userMessage, array $extractedData): array
    {
        $transactions = $intent['parameters']['transactions'] ?? [];
        $accountNumber = $intent['parameters']['account_number'] ?? null;
        $statementPeriod = $intent['parameters']['statement_period_start'] ?? null 
            ? "{$intent['parameters']['statement_period_start']} to {$intent['parameters']['statement_period_end']}"
            : null;
        
        if (empty($transactions)) {
            return [
                'content' => "I found a bank statement, but couldn't extract any transactions from it. Please check the document and try again.",
            ];
        }
        
        // Create import action for bank statement
        $executionService = new \App\Services\Addy\ActionExecutionService(
            $this->organization,
            $this->user
        );
        
        try {
            // Prepare the import action
            $action = $executionService->prepareAction(
                'import_bank_statement',
                [
                    'transactions' => $transactions,
                    'account_number' => $accountNumber,
                    'statement_period_start' => $intent['parameters']['statement_period_start'] ?? null,
                    'statement_period_end' => $intent['parameters']['statement_period_end'] ?? null,
                    'opening_balance' => $intent['parameters']['opening_balance'] ?? null,
                    'closing_balance' => $intent['parameters']['closing_balance'] ?? null,
                ]
            );
            
            $preview = $action->preview_data;
            $summary = $preview['summary'] ?? [];
            
            $transactionCount = count($transactions);
            $response = "I found a **bank statement** with **{$transactionCount} transaction(s)**.\n\n";
            
            if ($summary['account_number']) {
                $response .= "**Account:** {$summary['account_number']}\n";
            }
            if ($summary['statement_period']) {
                $response .= "**Period:** {$summary['statement_period']}\n";
            }
            
            $response .= "\n**Summary:**\n";
            $totalIncome = $this->formatCurrency($summary['total_income']);
            $totalExpenses = $this->formatCurrency($summary['total_expenses']);
            $response .= "• **Income:** {$summary['income_count']} transaction(s) - {$totalIncome}\n";
            $response .= "• **Expenses:** {$summary['expense_count']} transaction(s) - {$totalExpenses}\n";
            
            if ($summary['duplicate_count'] > 0) {
                $response .= "• **Duplicates:** {$summary['duplicate_count']} transaction(s) will be skipped\n";
            }
            
            $response .= "\n**Sample transactions (first 5):**\n";
            foreach (array_slice($transactions, 0, 5) as $tx) {
                $flowType = $tx['flow_type'] ?? ($tx['type'] === 'credit' ? 'income' : 'expense');
                $amount = $this->formatCurrency($tx['amount'] ?? 0);
                $date = $tx['date'] ?? 'Unknown date';
                $description = substr($tx['description'] ?? 'No description', 0, 40);
                $response .= "• {$date}: **{$flowType}** {$amount} - {$description}\n";
            }
            
            if ($transactionCount > 5) {
                $more = $transactionCount - 5;
                $response .= "\n_+ {$more} more transaction(s)_\n";
            }
            
            if (!empty($preview['warnings'])) {
                $response .= "\n**⚠️ Warnings:**\n";
                foreach ($preview['warnings'] as $warning) {
                    $response .= "• {$warning}\n";
                }
            }
            
            return [
                'content' => $response,
                'action' => [
                    'action_id' => $action->id,
                    'requires_confirmation' => true,
                    'preview' => $preview,
                ],
                'quick_actions' => [
                    ['label' => 'Import All Transactions', 'action_id' => $action->id, 'type' => 'confirm'],
                    ['label' => 'Cancel', 'action_id' => $action->id, 'type' => 'cancel'],
                ],
            ];
            
        } catch (\Exception $e) {
            \Log::error('Failed to prepare bank statement import', [
                'error' => $e->getMessage(),
                'transactions_count' => count($transactions),
            ]);
            
            // Fallback to simple response
            $transactionCount = count($transactions);
            return [
                'content' => "I found a **bank statement** with **{$transactionCount} transaction(s)**, but couldn't prepare the import. Please try again or contact support.",
            ];
        }
    }

    /**
     * Handle all conversational queries through OpenAI with cultural context
     * Code provides data context, OpenAI formats it conversationally
     */
    protected function handleConversationalQuery(string $userMessage, array $chatHistory, array $intent, ?array $dataContext = null): array
    {
        // Get current business context
        $state = $this->core->getState();
        $thought = $this->core->getCurrentThought();
        
        // Get historical document context if relevant
        $historicalContext = $this->getHistoricalDocumentContext($userMessage);
        
        // Get cultural settings for personality
        $culturalEngine = new AddyCulturalEngine($this->organization, $this->user);
        $tone = $culturalEngine->getSettings()->tone ?? 'professional';
        
        // Build comprehensive system message with personality and data context
        $systemMessage = $this->buildConversationalSystemMessage($state, $thought, $tone, $dataContext, $culturalEngine, $historicalContext);
        
        // Try to use AI service, but fallback to simple response if API key not configured
        try {
            $ai = new AIService();
            
            // Build message array with history
            $messages = [
                ['role' => 'system', 'content' => $systemMessage]
            ];
            
            // Add recent chat history for context (limit to last 10 messages)
            $recentHistory = array_slice($chatHistory, -10);
            foreach ($recentHistory as $msg) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
            
            // Add current message
            $messages[] = [
                'role' => 'user',
                'content' => $userMessage
            ];
            
            $response = $ai->chat($messages, 1500); // Increased tokens for richer responses
            
            // Apply tone adaptation to the response
            $content = $culturalEngine->adaptTone($response['content']);
            
            // Generate quick actions based on intent and data context
            $quickActions = $this->generateQuickActions($intent, $dataContext);
            
            return [
                'content' => $content,
                'quick_actions' => $quickActions,
            ];
        } catch (\Exception $e) {
            // Fallback response when AI is not available (e.g., in tests without API key)
            $quickActions = $this->generateQuickActions($intent, $dataContext);
            
            // Provide a simple contextual response based on intent
            $fallbackContent = $this->getFallbackResponse($intent, $dataContext);
            
            return [
                'content' => $fallbackContent,
                'quick_actions' => $quickActions,
            ];
        }
    }
    
    /**
     * Get fallback response when AI is not available
     */
    protected function getFallbackResponse(array $intent, ?array $dataContext): string
    {
        $intentType = $intent['intent'] ?? 'general';
        
        switch ($intentType) {
            case 'query_cash':
                return "I can help you with cash information. Your current cash position is available in the Money section.";
            case 'query_budget':
                return "I can help you with budget information. Check the Money section for detailed budget status.";
            case 'query_expenses':
                return "I can help you track expenses. View your expense breakdown in the Money section.";
            case 'query_transactions':
                if ($dataContext && isset($dataContext['latest_transactions'])) {
                    $transactions = $dataContext['latest_transactions'];
                    if (empty($transactions)) {
                        return "You don't have any transactions yet. You can add transactions in the Money section.";
                    }
                    $response = "Here are your latest transactions:\n\n";
                    foreach (array_slice($transactions, 0, 5) as $tx) {
                        $type = $tx['type'] === 'income' ? 'Income' : 'Expense';
                        $amount = $this->formatCurrency($tx['amount']);
                        $response .= "• {$type}: {$amount} - {$tx['category']} ({$tx['formatted_date']})\n";
                        if (!empty($tx['description'])) {
                            $response .= "  {$tx['description']}\n";
                        }
                    }
                    return $response;
                }
                return "I can help you view transactions. Check the Money section for your transaction history.";
            case 'query_invoices':
                return "I can help you with invoices. Check the Sales section for invoice details.";
            case 'query_sales':
                return "I can help you with sales information. View sales performance in the Sales section.";
            case 'greeting':
                return "Hello! I'm Addy, your AI business assistant. How can I help you today?";
            default:
                return "I'm here to help! You can ask me about your cash position, budgets, expenses, invoices, sales, or team information.";
        }
    }
    
    /**
     * Generate quick actions based on intent and data context
     */
    protected function generateQuickActions(array $intent, ?array $dataContext): array
    {
        $actions = [];
        
        // Default quick actions
        $actions[] = ['label' => 'Cash Position', 'command' => 'What is my cash position?'];
        $actions[] = ['label' => 'Top Expenses', 'command' => 'Show me top expenses'];
        $actions[] = ['label' => 'Daily Focus', 'command' => 'What should I focus on today?'];
        
        // Context-specific actions
        if ($dataContext) {
            switch ($dataContext['type']) {
                case 'cash':
                    $actions = [
                        ['label' => 'View Accounts', 'url' => '/money/accounts'],
                        ['label' => 'View Expenses', 'url' => '/money/movements'],
                        ['label' => 'View Budgets', 'url' => '/money/budgets'],
                    ];
                    break;
                
                case 'budget':
                    $actions = [
                        ['label' => 'View All Budgets', 'url' => '/money/budgets'],
                    ];
                    break;
                
                case 'expenses':
                    $actions = [
                        ['label' => 'View Transactions', 'url' => '/money/movements'],
                        ['label' => 'View Report', 'url' => '/reports/expenses'],
                    ];
                    break;
                
                case 'transactions':
                    $actions = [
                        ['label' => 'View All Transactions', 'url' => '/money/movements'],
                        ['label' => 'Add Transaction', 'url' => '/money/movements/create'],
                        ['label' => 'Categorize Transactions', 'command' => 'Categorize transactions'],
                    ];
                    break;
                
                case 'invoices':
                    $actions = [
                        ['label' => 'View All Invoices', 'url' => '/invoices'],
                    ];
                    if (isset($dataContext['invoice_health']['overdue_count']) && $dataContext['invoice_health']['overdue_count'] > 0) {
                        $actions[] = ['label' => 'Send Reminders', 'command' => 'Send invoice reminders'];
                    }
                    break;
                
                case 'sales':
                    $actions = [
                        ['label' => 'Sales Report', 'url' => '/reports/sales'],
                        ['label' => 'View Customers', 'url' => '/customers'],
                    ];
                    break;
                
                case 'team':
                    $actions = [
                        ['label' => '👥 View Team', 'url' => '/team'],
                        ['label' => '🏖️ View Leave', 'url' => '/leave/requests'],
                    ];
                    break;
            }
        }
        
        return $actions;
    }
    
    /**
     * Get historical document context for the user's message
     */
    protected function getHistoricalDocumentContext(string $userMessage): ?array
    {
        try {
            $contextService = new DocumentContextService();
            
            // Extract potential customer name or keywords from message
            $customerName = null;
            $documentType = null;
            
            // Simple extraction - could be enhanced with NLP
            if (preg_match('/\b(customer|client|for|to)\s+([A-Z][a-zA-Z\s]+)/i', $userMessage, $matches)) {
                $customerName = trim($matches[2] ?? '');
            }
            
            // Check for document type keywords
            if (preg_match('/\b(invoice|quote|receipt|document|contract)\b/i', $userMessage, $matches)) {
                $documentType = strtolower($matches[1] ?? '');
            }
            
            $context = $contextService->getHistoricalContext(
                $this->organization->id,
                $customerName ?: null,
                $documentType ?: null,
                5 // Limit to 5 most recent relevant documents
            );
            
            return !empty($context) ? $context : null;
        } catch (\Exception $e) {
            \Log::warning('Failed to get historical document context', [
                'error' => $e->getMessage(),
                'organization_id' => $this->organization->id,
            ]);
            return null;
        }
    }
    
    protected function buildConversationalSystemMessage($state, $thought, string $tone, ?array $dataContext, AddyCulturalEngine $culturalEngine, ?array $historicalContext = null): string
    {
        $message = "You are Addy, a friendly and intelligent business COO assistant. ";
        
        // Personality traits
        $message .= "Your personality:\n";
        $message .= "- You are warm, approachable, and genuinely care about helping the business succeed\n";
        $message .= "- You're proactive and insightful, always thinking ahead\n";
        $message .= "- You communicate clearly and concisely, but with personality\n";
        $message .= "- You use natural, conversational language - like talking to a trusted colleague\n";
        $message .= "- You're encouraging and supportive, celebrating wins and helping navigate challenges\n";
        $message .= "- You ask follow-up questions when helpful, showing genuine interest\n";
        $message .= "\n**FORMATTING RULES:**\n";
        $message .= "- DO NOT use emojis in your responses unless the user explicitly uses them or asks about something emoji-related\n";
        $message .= "- DO NOT use asterisks (*) for emphasis - use markdown formatting instead\n";
        $message .= "- Use **bold** for important terms, numbers, or emphasis\n";
        $message .= "- Use [link text](url) for clickable links when referencing pages or resources\n";
        $message .= "- Use proper line breaks and spacing for readability\n";
        $message .= "- Format numbers, dates, and amounts clearly\n";
        $message .= "- Use bullet points (-) or numbered lists when presenting multiple items\n\n";
        
        // Tone-specific instructions
        switch ($tone) {
            case 'casual':
                $message .= "Communication style: Be casual and friendly. Use contractions, shorter sentences, and a relaxed tone. ";
                $message .= "It's like chatting with a friend who happens to know everything about your business.\n\n";
                break;
            case 'motivational':
                $message .= "Communication style: Be energetic and motivating. Celebrate progress, use positive language, ";
                $message .= "and inspire action. You're the cheerleader who also has the data.\n\n";
                break;
            default: // professional
                $message .= "Communication style: Be professional but warm. Clear, direct, and helpful. ";
                $message .= "You're the trusted advisor who makes complex things simple.\n\n";
        }
        
        // Cultural context
        $greeting = $culturalEngine->getContextualGreeting();
        $recommendedFocus = $culturalEngine->getRecommendedFocus();
        $suggestion = $culturalEngine->getProactiveSuggestion();
        
        $message .= "Cultural context:\n";
        $message .= "- Contextual greeting style: {$greeting}\n";
        $message .= "- Recommended focus: {$recommendedFocus}\n";
        if ($suggestion) {
            $message .= "- Proactive suggestion: {$suggestion['message']}\n";
        }
        $message .= "\n";
        
        // Conversational guidelines
        $message .= "How to be conversational:\n";
        $message .= "- Respond naturally, as if in a real conversation\n";
        $message .= "- Reference previous messages when relevant (you have chat history)\n";
        $message .= "- Ask clarifying questions if something is unclear\n";
        $message .= "- Show understanding by acknowledging what the user said\n";
        $message .= "- Provide context and explain your reasoning when helpful\n";
        $message .= "- Keep responses focused but not robotic - be human-like\n";
        $message .= "- If the user asks about something unrelated to business, be helpful but gently guide back to business topics\n";
        $message .= "- For greetings, use the cultural context naturally - don't just repeat it verbatim\n\n";
        
        // Business context
        $message .= "Current business context:\n";
        $message .= "- Focus area: {$state->focus_area}\n";
        $message .= "- Current situation: {$state->context}\n";
        
        if ($thought['top_insight']) {
            $message .= "- Top priority: {$thought['top_insight']['title']}\n";
            if (isset($thought['top_insight']['description'])) {
                $message .= "  Details: {$thought['top_insight']['description']}\n";
            }
        }
        
        // Data context (if provided by code)
        if ($dataContext) {
            $message .= "\n**DATA PROVIDED BY SYSTEM (use this to answer the user's question conversationally):**\n";
            $message .= json_encode($dataContext, JSON_PRETTY_PRINT);
            $message .= "\n\n**CRITICAL RULES FOR USING DATA:**\n";
            $message .= "- ONLY use the data provided above. NEVER invent, make up, or hallucinate data.\n";
            $message .= "- If the data is empty or missing, say so clearly (e.g., 'You don't have any transactions yet' or 'I don't have that information available').\n";
            $message .= "- If asked 'Where did you get this data from?', explain that it comes from the user's actual business records in the system.\n";
            $message .= "- NEVER create hypothetical examples or sample data - only use real data from the system.\n";
            $message .= "- If the user questions the data, acknowledge it and suggest they check the relevant section of the app.\n";
            $message .= "- Present this data naturally in conversation. Don't just list numbers - explain what they mean, provide context, and make it conversational. Use the data to answer the user's question, but format it as a natural response.\n";
        } else {
            $message .= "\n**IMPORTANT: NO DATA CONTEXT PROVIDED**\n";
            $message .= "- If the user asks about specific data (transactions, cash, expenses, etc.), you MUST tell them that you don't have access to that data right now.\n";
            $message .= "- DO NOT invent or make up data. Be honest that you need to query the system first.\n";
            $message .= "- Suggest they check the relevant section of the app or ask a more specific question.\n";
        }
        
        // Historical document context
        if ($historicalContext && !empty($historicalContext)) {
            $message .= "\n**HISTORICAL DOCUMENT CONTEXT (use this to provide context from past documents):**\n";
            $message .= json_encode($historicalContext, JSON_PRETTY_PRINT);
            $message .= "\n\nYou can reference this historical information to provide better context and insights. For example, if the user asks about a customer, you can reference past invoices or quotes.\n";
        }
        
        // Action capabilities
        $message .= "\n**YOUR CAPABILITIES:**\n";
        $message .= "You CAN execute actions! When users ask you to:\n";
        $message .= "- Create/confirm/record transactions or expenses → I will prepare the action for confirmation\n";
        $message .= "- Send invoice reminders → I will prepare the action for confirmation\n";
        $message .= "- Generate reports → I will prepare the action for confirmation\n";
        $message .= "- Other business actions → I will prepare them for confirmation\n\n";
        $message .= "IMPORTANT: When a user asks you to DO something (create, confirm, send, etc.), acknowledge that you CAN do it and that you're preparing it. ";
        $message .= "The system will handle the actual execution after confirmation. ";
        $message .= "Don't say you can't do things - you can! The system handles actions through a confirmation flow.\n";
        $message .= "If the user mentions an expense/transaction that was discussed earlier, you can create it. ";
        $message .= "If they say 'confirm that expense has been created', interpret this as 'create that expense we discussed'.\n\n";
        
        $message .= "Remember: You're having a conversation, not just answering questions. Be engaging, helpful, and personable!";
        
        return $message;
    }
}
