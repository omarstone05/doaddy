<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Exception;

class ContextAwareOcrService extends ImprovedOcrService
{
    /**
     * Process document with context awareness and uncertainty handling
     */
    public function processDocumentWithContext(
        string $filePath,
        array $userContext = [],
        bool $isHistorical = false
    ): array {
        // Standard OCR processing
        $result = $this->processDocument($filePath);

        if (!$result['success']) {
            return $result;
        }

        // Analyze uncertainty and context
        $analysis = $this->analyzeUncertainty($result['data'], $result['document_type']);
        
        // Enrich with historical context if applicable
        if ($isHistorical) {
            $result['data'] = $this->enrichHistoricalContext($result['data'], $userContext);
        }

        // Generate clarifying questions if needed
        if ($analysis['has_uncertainty']) {
            $result['questions'] = $this->generateClarifyingQuestions(
                $result['data'],
                $analysis['uncertain_fields'],
                $result['document_type'],
                $userContext
            );
            $result['requires_review'] = true;
        } else {
            $result['requires_review'] = false;
        }

        $result['uncertainty_analysis'] = $analysis;
        $result['auto_importable'] = !$result['requires_review'] && $result['confidence'] >= 0.85;

        return $result;
    }

    /**
     * Analyze OCR results for uncertainty
     */
    protected function analyzeUncertainty(array $data, string $documentType): array
    {
        $uncertainFields = [];
        $confidenceScores = [];

        // Check each critical field
        $criticalFields = $this->getCriticalFields($documentType);

        foreach ($criticalFields as $field) {
            $fieldConfidence = $this->assessFieldConfidence($field, $data[$field] ?? null, $data);
            
            $confidenceScores[$field] = $fieldConfidence;

            if ($fieldConfidence < 0.7) {
                $uncertainFields[] = [
                    'field' => $field,
                    'value' => $data[$field] ?? null,
                    'confidence' => $fieldConfidence,
                    'reason' => $this->getUncertaintyReason($field, $data[$field] ?? null),
                ];
            }
        }

        // Overall uncertainty
        $averageConfidence = count($confidenceScores) > 0 
            ? array_sum($confidenceScores) / count($confidenceScores)
            : 0;

        return [
            'has_uncertainty' => count($uncertainFields) > 0 || $averageConfidence < 0.75,
            'uncertain_fields' => $uncertainFields,
            'confidence_scores' => $confidenceScores,
            'average_confidence' => $averageConfidence,
            'needs_review' => $averageConfidence < 0.75,
        ];
    }

    /**
     * Assess confidence for a specific field
     */
    protected function assessFieldConfidence(string $field, $value, array $allData): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $confidence = 1.0;

        switch ($field) {
            case 'date':
                // Check if date is valid and makes sense
                try {
                    $date = Carbon::parse($value);
                    
                    // Future date? Suspicious
                    if ($date->isFuture()) {
                        $confidence *= 0.3;
                    }
                    
                    // Too old? (more than 10 years)
                    if ($date->lt(Carbon::now()->subYears(10))) {
                        $confidence *= 0.6;
                    }
                    
                    // Invalid date format that somehow parsed?
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        $confidence *= 0.8;
                    }
                } catch (Exception $e) {
                    $confidence = 0.0;
                }
                break;

            case 'amount':
            case 'total':
                // Check if amount is reasonable
                $amount = (float) $value;
                
                // Zero or negative? Suspicious
                if ($amount <= 0) {
                    $confidence = 0.2;
                }
                
                // Extremely large amount? Needs verification
                if ($amount > 1000000) {
                    $confidence *= 0.5;
                }
                
                // Check if it has proper decimal places
                if (!preg_match('/^\d+\.\d{2}$/', (string)$value)) {
                    $confidence *= 0.9;
                }
                break;

            case 'merchant':
            case 'vendor':
            case 'customer':
                // Check if name looks valid
                $length = strlen((string)$value);
                
                if ($length < 2) {
                    $confidence = 0.1;
                } elseif ($length < 4) {
                    $confidence *= 0.6;
                }
                
                // Contains too many numbers? Suspicious
                if (preg_match_all('/\d/', (string)$value) > strlen((string)$value) / 2) {
                    $confidence *= 0.4;
                }
                break;

            case 'transaction_id':
            case 'invoice_number':
                // Should have minimum length
                if (strlen((string)$value) < 4) {
                    $confidence *= 0.5;
                }
                break;

            case 'phone':
                // Zambian phone number validation
                $phone = preg_replace('/[^0-9]/', '', (string)$value);
                
                if (!in_array(strlen($phone), [9, 10, 12])) {
                    $confidence = 0.3;
                }
                
                // Check Zambian prefixes
                if (!preg_match('/^(260|0)?97[0-9]|96[0-9]|95[0-9]/', $phone)) {
                    $confidence *= 0.7;
                }
                break;

            case 'provider':
                // Mobile money provider
                $validProviders = ['airtel money', 'mtn', 'zamtel'];
                $matches = false;
                
                foreach ($validProviders as $provider) {
                    if (stripos((string)$value, $provider) !== false) {
                        $matches = true;
                        break;
                    }
                }
                
                if (!$matches) {
                    $confidence = 0.4;
                }
                break;
        }

        return max(0.0, min(1.0, $confidence));
    }

    /**
     * Get reason for uncertainty
     */
    protected function getUncertaintyReason(string $field, $value): string
    {
        if ($value === null || $value === '') {
            return "Field is empty or could not be extracted";
        }

        return match($field) {
            'date' => $this->getDateUncertaintyReason($value),
            'amount', 'total' => $this->getAmountUncertaintyReason($value),
            'merchant', 'vendor' => "Merchant name unclear or incomplete",
            'phone' => "Phone number format doesn't match Zambian standards",
            'provider' => "Mobile money provider unclear",
            default => "OCR confidence low for this field",
        };
    }

    /**
     * Get specific reason for date uncertainty
     */
    protected function getDateUncertaintyReason($value): string
    {
        try {
            $date = Carbon::parse($value);
            
            if ($date->isFuture()) {
                return "Date is in the future - this seems incorrect";
            }
            
            if ($date->lt(Carbon::now()->subYears(10))) {
                return "Date is more than 10 years old - please confirm";
            }
            
            return "Date format is ambiguous";
        } catch (Exception $e) {
            return "Date could not be parsed";
        }
    }

    /**
     * Get specific reason for amount uncertainty
     */
    protected function getAmountUncertaintyReason($value): string
    {
        $amount = (float) $value;
        
        if ($amount <= 0) {
            return "Amount is zero or negative";
        }
        
        if ($amount > 1000000) {
            return "Amount is very large (over 1M ZMW) - please verify";
        }
        
        if (!preg_match('/^\d+\.\d{2}$/', (string)$value)) {
            return "Amount format is unclear (missing decimals?)";
        }
        
        return "Amount extracted but confidence is low";
    }

    /**
     * Generate clarifying questions using AI
     */
    protected function generateClarifyingQuestions(
        array $data,
        array $uncertainFields,
        string $documentType,
        array $userContext
    ): array {
        $questions = [];

        foreach ($uncertainFields as $field) {
            $question = $this->generateFieldQuestion($field, $data, $documentType, $userContext);
            if ($question) {
                $questions[] = $question;
            }
        }

        // Add contextual questions
        $contextualQuestions = $this->generateContextualQuestions($data, $documentType, $userContext);
        $questions = array_merge($questions, $contextualQuestions);

        return $questions;
    }

    /**
     * Generate question for a specific uncertain field
     */
    protected function generateFieldQuestion(array $fieldInfo, array $data, string $type, array $context): ?array
    {
        $field = $fieldInfo['field'];
        $value = $fieldInfo['value'];
        $reason = $fieldInfo['reason'];

        switch ($field) {
            case 'date':
                return [
                    'field' => 'date',
                    'type' => 'date_picker',
                    'question' => "I extracted the date as '{$value}', but I'm not confident. What's the correct transaction date?",
                    'reason' => $reason,
                    'current_value' => $value,
                    'suggestions' => $this->suggestDates($value, $context),
                ];

            case 'amount':
            case 'total':
                return [
                    'field' => $field,
                    'type' => 'number_input',
                    'question' => "The total amount appears to be ZMW {$value}. Is this correct?",
                    'reason' => $reason,
                    'current_value' => $value,
                    'suggestions' => $this->suggestAmounts($value, $data),
                ];

            case 'merchant':
            case 'vendor':
                return [
                    'field' => $field,
                    'type' => 'text_with_suggestions',
                    'question' => "I think the merchant is '{$value}'. Can you confirm or correct this?",
                    'reason' => $reason,
                    'current_value' => $value,
                    'suggestions' => $this->suggestMerchants($value, $context),
                ];

            case 'category':
                return [
                    'field' => 'category',
                    'type' => 'select',
                    'question' => "What category should this expense be in?",
                    'reason' => "I couldn't determine the category from the receipt",
                    'current_value' => null,
                    'options' => $this->getCategories($type, $context),
                ];

            case 'type':
                return [
                    'field' => 'type',
                    'type' => 'select',
                    'question' => "Is this an income or expense transaction?",
                    'reason' => "Transaction type is unclear from the document",
                    'current_value' => $value,
                    'options' => [
                        ['value' => 'income', 'label' => 'Income'],
                        ['value' => 'expense', 'label' => 'Expense'],
                    ],
                ];

            default:
                return [
                    'field' => $field,
                    'type' => 'text_input',
                    'question' => "I'm not sure about the {$field}. The value I extracted is '{$value}'. Is this correct?",
                    'reason' => $reason,
                    'current_value' => $value,
                ];
        }
    }

    /**
     * Generate contextual questions based on document type and context
     */
    protected function generateContextualQuestions(array $data, string $type, array $context): array
    {
        $questions = [];

        // For receipts without category
        if ($type === 'receipt' && !isset($data['category'])) {
            $questions[] = [
                'field' => 'category',
                'type' => 'select',
                'question' => "What category should this purchase be recorded under?",
                'reason' => "Help me learn - what type of expense is this?",
                'current_value' => null,
                'options' => $this->getCategories($type, $context),
            ];
        }

        // For historical documents
        if (isset($data['date'])) {
            try {
                $date = Carbon::parse($data['date']);
                if ($date->lt(Carbon::now()->subMonths(6))) {
                    $questions[] = [
                        'field' => 'is_historical',
                        'type' => 'confirmation',
                        'question' => "This document is from {$date->format('F Y')} - that's {$date->diffForHumans()}. Is this historical data you're catching up on?",
                        'reason' => "This helps me understand the context of your data",
                        'current_value' => true,
                    ];
                }
            } catch (Exception $e) {
                // Ignore
            }
        }

        // For mobile money - ask about sender/recipient context
        if ($type === 'mobile_money' && isset($data['amount']) && $data['amount'] > 0) {
            $questions[] = [
                'field' => 'transaction_context',
                'type' => 'text_input',
                'question' => "What was this ZMW {$data['amount']} mobile money transaction for?",
                'reason' => "This helps categorize and describe the transaction",
                'current_value' => null,
                'placeholder' => 'e.g., Payment from customer, Salary, Supplier payment',
            ];
        }

        return $questions;
    }

    /**
     * Suggest possible dates
     */
    protected function suggestDates($currentValue, array $context): array
    {
        $suggestions = [];

        // Today
        $suggestions[] = [
            'value' => Carbon::today()->format('Y-m-d'),
            'label' => 'Today (' . Carbon::today()->format('M d, Y') . ')',
        ];

        // Yesterday
        $suggestions[] = [
            'value' => Carbon::yesterday()->format('Y-m-d'),
            'label' => 'Yesterday (' . Carbon::yesterday()->format('M d, Y') . ')',
        ];

        // This week
        $suggestions[] = [
            'value' => Carbon::now()->startOfWeek()->format('Y-m-d'),
            'label' => 'Start of this week (' . Carbon::now()->startOfWeek()->format('M d, Y') . ')',
        ];

        // Last month
        $suggestions[] = [
            'value' => Carbon::now()->subMonth()->format('Y-m-d'),
            'label' => 'Last month (' . Carbon::now()->subMonth()->format('M Y') . ')',
        ];

        return $suggestions;
    }

    /**
     * Suggest possible amounts based on context
     */
    protected function suggestAmounts($currentValue, array $data): array
    {
        $suggestions = [];
        $current = (float) $currentValue;

        // If there's a subtotal and tax, calculate expected total
        if (isset($data['subtotal']) && isset($data['tax'])) {
            $calculated = (float) $data['subtotal'] + (float) $data['tax'];
            if (abs($calculated - $current) > 0.01) {
                $suggestions[] = [
                    'value' => $calculated,
                    'label' => "Calculated from subtotal + tax: ZMW " . number_format($calculated, 2),
                ];
            }
        }

        // Round numbers (OCR might have misread decimals)
        if ($current > 10) {
            $rounded = round($current / 5) * 5;
            if ($rounded != $current) {
                $suggestions[] = [
                    'value' => $rounded,
                    'label' => "Rounded to nearest 5: ZMW " . number_format($rounded, 2),
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Suggest merchants based on history
     */
    protected function suggestMerchants($currentValue, array $context): array
    {
        // This would query recent merchant names from the database
        // For now, return Zambian common merchants
        $common = [
            'Shoprite', 'Pick n Pay', 'Game Stores', 'Spar',
            'Hungry Lion', 'Debonairs Pizza', 'Chicken Inn',
            'Engen', 'Puma Energy', 'Total Energies',
        ];

        $suggestions = [];
        
        foreach ($common as $merchant) {
            if (stripos((string)$currentValue, $merchant) !== false || 
                stripos($merchant, (string)$currentValue) !== false) {
                $suggestions[] = [
                    'value' => $merchant,
                    'label' => $merchant,
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Get categories based on document type
     */
    protected function getCategories(string $type, array $context): array
    {
        return [
            ['value' => 'groceries', 'label' => 'Groceries & Food'],
            ['value' => 'fuel', 'label' => 'Fuel & Transport'],
            ['value' => 'office', 'label' => 'Office Supplies'],
            ['value' => 'utilities', 'label' => 'Utilities'],
            ['value' => 'rent', 'label' => 'Rent & Premises'],
            ['value' => 'salaries', 'label' => 'Salaries & Wages'],
            ['value' => 'equipment', 'label' => 'Equipment & Tools'],
            ['value' => 'marketing', 'label' => 'Marketing & Advertising'],
            ['value' => 'professional', 'label' => 'Professional Services'],
            ['value' => 'inventory', 'label' => 'Inventory Purchase'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }

    /**
     * Get critical fields for document type
     */
    protected function getCriticalFields(string $documentType): array
    {
        return match($documentType) {
            'receipt' => ['date', 'total', 'merchant'],
            'invoice' => ['date', 'total', 'invoice_number', 'vendor'],
            'mobile_money' => ['date', 'amount', 'transaction_id', 'provider'],
            'bank_statement' => ['date', 'amount', 'description'],
            default => ['date', 'amount'],
        };
    }

    /**
     * Enrich data with historical context
     */
    protected function enrichHistoricalContext(array $data, array $userContext): array
    {
        // Mark as historical
        $data['is_historical'] = true;

        // Try to infer fiscal year/period if date exists
        if (isset($data['date'])) {
            try {
                $date = Carbon::parse($data['date']);
                $data['fiscal_year'] = $date->year;
                $data['fiscal_quarter'] = $date->quarter;
                $data['fiscal_month'] = $date->format('Y-m');
            } catch (Exception $e) {
                // Ignore
            }
        }

        return $data;
    }

    /**
     * Batch process historical documents with intelligent grouping
     */
    public function batchProcessHistorical(array $filePaths, User $user): array
    {
        $results = [];
        $needsReview = [];
        $autoImportable = [];

        foreach ($filePaths as $filePath) {
            $result = $this->processDocumentWithContext($filePath, [
                'user_id' => $user->id,
                'organization_id' => session('current_organization_id') ?? $user->current_organization_id,
            ], true);

            $results[] = $result;

            if ($result['requires_review']) {
                $needsReview[] = $result;
            } else {
                $autoImportable[] = $result;
            }
        }

        return [
            'total' => count($filePaths),
            'needs_review' => count($needsReview),
            'auto_importable' => count($autoImportable),
            'results' => $results,
            'review_queue' => $needsReview,
            'auto_import_queue' => $autoImportable,
        ];
    }
}

