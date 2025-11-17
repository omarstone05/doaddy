<?php

namespace App\Services\Addy;

use Illuminate\Support\Str;

class AddyCommandParser
{
    /**
     * Parse user message and extract intent
     */
    public function parse(string $message): array
    {
        $originalMessage = $message; // Keep original for bank statement detection
        $message = strtolower(trim($message));
        
        // Check if message looks like bank statement data (before lowercasing)
        if ($this->looksLikeBankStatement($originalMessage)) {
            return [
                'intent' => 'action',
                'action_type' => 'create_transaction',
                'parameters' => [
                    'is_bank_statement_text' => true,
                    'raw_text' => $originalMessage,
                ],
                'confidence' => 0.95,
            ];
        }
        
        // Check for action requests FIRST
        if ($this->isActionRequest($message)) {
            $action = $this->parseAction($message);
            if ($action) {
                return [
                    'intent' => 'action',
                    'action_type' => $action['action_type'],
                    'parameters' => $action['parameters'],
                    'confidence' => 0.9,
                ];
            }
        }
        
        // Greeting
        if ($this->isGreeting($message)) {
            return [
                'intent' => 'greeting',
                'confidence' => 1.0,
            ];
        }
        
        // Cash/Money queries
        if ($this->isCashQuery($message)) {
            return [
                'intent' => 'query_cash',
                'confidence' => 0.9,
            ];
        }
        
        // Budget queries
        if ($this->isBudgetQuery($message)) {
            return [
                'intent' => 'query_budget',
                'confidence' => 0.9,
            ];
        }
        
        // Expense queries - check if they're asking for specific data (category, date, amount) - if so, generate a report
        if ($this->isExpenseQuery($message)) {
            // Check if query has specific parameters that warrant a report
            if ($this->hasReportableExpenseQuery($message)) {
                return [
                    'intent' => 'action',
                    'action_type' => 'generate_report',
                    'parameters' => $this->extractExpenseReportParameters($message),
                    'confidence' => 0.9,
                ];
            }
            return [
                'intent' => 'query_expenses',
                'confidence' => 0.9,
            ];
        }
        
        // Transaction queries (latest, recent transactions)
        if ($this->isTransactionQuery($message)) {
            return [
                'intent' => 'query_transactions',
                'confidence' => 0.9,
            ];
        }
        
        // Invoice queries
        if ($this->isInvoiceQuery($message)) {
            return [
                'intent' => 'query_invoices',
                'type' => $this->getInvoiceType($message),
                'confidence' => 0.9,
            ];
        }
        
        // Sales queries
        if ($this->isSalesQuery($message)) {
            return [
                'intent' => 'query_sales',
                'confidence' => 0.9,
            ];
        }
        
        // Team/People queries
        if ($this->isTeamQuery($message)) {
            return [
                'intent' => 'query_team',
                'confidence' => 0.9,
            ];
        }
        
        // Payroll queries
        if ($this->isPayrollQuery($message)) {
            return [
                'intent' => 'query_payroll',
                'confidence' => 0.9,
            ];
        }
        
        // Inventory/Stock queries
        if ($this->isInventoryQuery($message)) {
            return [
                'intent' => 'query_inventory',
                'confidence' => 0.9,
            ];
        }
        
        // Focus/Priority queries
        if ($this->isFocusQuery($message)) {
            return [
                'intent' => 'query_focus',
                'confidence' => 0.9,
            ];
        }
        
        // Insights queries
        if ($this->isInsightsQuery($message)) {
            return [
                'intent' => 'query_insights',
                'confidence' => 0.9,
            ];
        }
        
        // General conversation
        return [
            'intent' => 'general',
            'confidence' => 0.5,
        ];
    }
    
    protected function isGreeting(string $message): bool
    {
        $greetings = ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening'];
        foreach ($greetings as $greeting) {
            if (str_contains($message, $greeting)) {
                return true;
            }
        }
        return false;
    }
    
    protected function isCashQuery(string $message): bool
    {
        $keywords = ['cash', 'cash position', 'balance', 'money', 'funds', 'account balance'];
        return $this->containsAny($message, $keywords);
    }
    
    protected function isBudgetQuery(string $message): bool
    {
        $keywords = ['budget', 'budgets', 'spending limit', 'budget status'];
        return $this->containsAny($message, $keywords);
    }
    
    protected function isExpenseQuery(string $message): bool
    {
        $keywords = ['expense', 'expenses', 'spending', 'spent', 'costs'];
        return $this->containsAny($message, $keywords);
    }
    
    protected function isTransactionQuery(string $message): bool
    {
        $keywords = ['transaction', 'transactions', 'latest transaction', 'recent transaction', 'recent transactions', 'latest transactions', 'where did you get', 'show me transactions'];
        return $this->containsAny($message, $keywords);
    }
    
    protected function isInvoiceQuery(string $message): bool
    {
        $keywords = ['invoice', 'invoices', 'bill', 'bills'];
        return $this->containsAny($message, $keywords);
    }
    
    protected function getInvoiceType(string $message): string
    {
        if (str_contains($message, 'overdue') || str_contains($message, 'late')) {
            return 'overdue';
        }
        if (str_contains($message, 'pending') || str_contains($message, 'outstanding')) {
            return 'pending';
        }
        if (str_contains($message, 'paid')) {
            return 'paid';
        }
        return 'all';
    }
    
    protected function isSalesQuery(string $message): bool
    {
        $keywords = ['sales', 'revenue', 'sold', 'performance'];
        return $this->containsAny($message, $keywords);
    }
    
    protected function isTeamQuery(string $message): bool
    {
        $keywords = ['team', 'staff', 'employees', 'people', 'members'];
        return $this->containsAny($message, $keywords);
    }
    
    protected function isPayrollQuery(string $message): bool
    {
        $keywords = ['payroll', 'salary', 'salaries', 'wages', 'pay'];
        return $this->containsAny($message, $keywords);
    }
    
    protected function isInventoryQuery(string $message): bool
    {
        $keywords = ['inventory', 'stock', 'products', 'items', 'warehouse'];
        return $this->containsAny($message, $keywords);
    }
    
    protected function isFocusQuery(string $message): bool
    {
        $keywords = ['focus', 'priority', 'priorities', 'should i', 'what should', 'today'];
        return $this->containsAny($message, $keywords);
    }
    
    protected function isInsightsQuery(string $message): bool
    {
        $keywords = ['insight', 'insights', 'issue', 'issues', 'alert', 'alerts', 'recommendation'];
        return $this->containsAny($message, $keywords);
    }
    
    protected function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if message is an action request
     */
    protected function isActionRequest(string $message): bool
    {
        $actionKeywords = [
            'send', 'create', 'generate', 'schedule', 'approve', 
            'make', 'draft', 'prepare', 'export', 'download',
            'confirm', 'record', 'add', 'log', 'enter', 'register',
            'give me', 'show me', 'get me', 'give', 'show', 'get'
        ];
        
        foreach ($actionKeywords as $keyword) {
            if (str_starts_with($message, $keyword) || str_contains($message, $keyword)) {
                return true;
            }
        }
        
        // Also check for report requests specifically
        if (str_contains($message, 'report')) {
            return true;
        }
        
        return false;
    }

    /**
     * Parse action from message
     */
    protected function parseAction(string $message): ?array
    {
        // Create invoice
        if ((str_contains($message, 'create') || str_contains($message, 'make') || str_contains($message, 'generate') || str_contains($message, 'new')) 
            && str_contains($message, 'invoice')) {
            return [
                'action_type' => 'create_invoice',
                'parameters' => $this->extractInvoiceParameters($message),
            ];
        }
        
        // Send invoice reminders
        if (str_contains($message, 'send') && str_contains($message, 'reminder') && str_contains($message, 'invoice')) {
            return [
                'action_type' => 'send_invoice_reminders',
                'parameters' => $this->extractReminderParameters($message),
            ];
        }

        // Follow up on quotes
        if (str_contains($message, 'follow up') && str_contains($message, 'quote')) {
            return [
                'action_type' => 'follow_up_quote',
                'parameters' => $this->extractQuoteParameters($message),
            ];
        }

        // Record invoice payment
        if ((str_contains($message, 'mark') || str_contains($message, 'record') || str_contains($message, 'log'))
            && str_contains($message, 'invoice')
            && (str_contains($message, 'paid') || str_contains($message, 'payment'))) {
            return [
                'action_type' => 'record_invoice_payment',
                'parameters' => $this->extractInvoicePaymentParameters($message),
            ];
        }

        // Categorize transactions
        if ((str_contains($message, 'categorize') || str_contains($message, 'classify')) 
            && str_contains($message, 'transaction')) {
            return [
                'action_type' => 'categorize_transactions',
                'parameters' => [
                    'limit' => $this->extractLimit($message),
                ],
            ];
        }
        
        // Create/confirm/record transaction or expense
        // Patterns: "create transaction", "create expense", "confirm expense", "record expense", "log expense", etc.
        if ((str_contains($message, 'create') || str_contains($message, 'confirm') || str_contains($message, 'record') || 
             str_contains($message, 'log') || str_contains($message, 'add') || str_contains($message, 'enter')) && 
            (str_contains($message, 'transaction') || str_contains($message, 'expense') || str_contains($message, 'income'))) {
            
            // Extract parameters from the message
            $params = $this->extractTransactionParams($message);
            
            // If confirming, try to extract from context (amount, type mentioned earlier)
            if (str_contains($message, 'confirm')) {
                // Look for amount in the message
                if (!isset($params['amount']) && preg_match('/\$?(\d+(?:\.\d{2})?)/', $message, $matches)) {
                    $params['amount'] = (float) $matches[1];
                }
                
                // Default to expense if confirming and no type specified
                if (!isset($params['flow_type'])) {
                    $params['flow_type'] = 'expense';
                }
            }
            
            return [
                'action_type' => 'create_transaction',
                'parameters' => $params,
            ];
        }
        
        // Generate report - recognize multiple patterns
        if (str_contains($message, 'report')) {
            // Check for action verbs: generate, create, give me, show me, get me, prepare, make
            $hasActionVerb = str_contains($message, 'generate') 
                || str_contains($message, 'create') 
                || str_contains($message, 'give me')
                || str_contains($message, 'show me')
                || str_contains($message, 'get me')
                || str_contains($message, 'prepare')
                || str_contains($message, 'make')
                || str_contains($message, 'give')
                || str_contains($message, 'show')
                || str_contains($message, 'get');
            
            if ($hasActionVerb || str_contains($message, 'weekly report') || str_contains($message, 'monthly report')) {
                return [
                    'action_type' => 'generate_report',
                    'parameters' => $this->extractReportParameters($message),
                ];
            }
        }
        
        // Create organization/company
        if ((str_contains($message, 'create') || str_contains($message, 'make') || str_contains($message, 'new') || str_contains($message, 'start'))
            && (str_contains($message, 'organization') || str_contains($message, 'organisation') || str_contains($message, 'company') || str_contains($message, 'business'))) {
            return [
                'action_type' => 'create_organization',
                'parameters' => $this->extractOrganizationParameters($message),
            ];
        }
        
        return null;
    }

    /**
     * Extract invoice parameters from message
     */
    protected function extractInvoiceParameters(string $message): array
    {
        $params = [];
        
        // Extract customer name - handle "for [name]" or "to [name]"
        // Pattern: "for brave brands" or "to brave brands" or "for brave brands from"
        if (preg_match('/(?:for|to)\s+([a-z\s]+?)(?:\s+(?:from|on|dated|invoice)|$|,|\.)/i', $message, $matches)) {
            $params['customer_name'] = trim($matches[1]);
        }
        
        // Extract amount - handle formats like "10 000", "10,000", "10000", "$10000"
        // Pattern: number with optional spaces, commas, or decimal
        if (preg_match('/\$?\s*(\d{1,3}(?:\s+\d{3})*(?:\.\d{2})?|\d{1,3}(?:,\d{3})*(?:\.\d{2})?|\d+(?:\.\d{2})?)/', $message, $matches)) {
            $amount = str_replace([' ', ','], '', $matches[1]);
            $params['total_amount'] = (float) $amount;
        }
        
        // Extract date - handle formats like "23 july 2025", "23/07/2025", "2025-07-23"
        // Pattern: day month year or date formats
        if (preg_match('/(\d{1,2})\s+(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\s+(\d{4})/i', $message, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = $this->monthToNumber($matches[2]);
            $year = $matches[3];
            $params['invoice_date'] = "{$year}-{$month}-{$day}";
        } elseif (preg_match('/(\d{4}-\d{2}-\d{2})|(\d{1,2}\/\d{1,2}\/\d{4})/', $message, $matches)) {
            $params['invoice_date'] = $matches[0];
        }
        
        return $params;
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
     * Extract transaction parameters from message
     */
    protected function extractTransactionParams(string $message): array
    {
        $params = [];
        
        // Extract amount
        if (preg_match('/\$?(\d+(?:\.\d{2})?)/', $message, $matches)) {
            $params['amount'] = (float) $matches[1];
        }
        
        // Extract type (income/expense)
        if (str_contains($message, 'income') || str_contains($message, 'revenue')) {
            $params['flow_type'] = 'income';
        } elseif (str_contains($message, 'expense') || str_contains($message, 'cost')) {
            $params['flow_type'] = 'expense';
        }
        
        // Extract category
        if (preg_match('/for\s+([a-z\s]+)/i', $message, $matches)) {
            $params['category'] = trim($matches[1]);
        }
        
        return $params;
    }

    /**
     * Extract report type from message
     */
    protected function extractReportType(string $message): string
    {
        if (str_contains($message, 'sales')) return 'sales';
        if (str_contains($message, 'expense')) return 'expenses';
        if (str_contains($message, 'cash')) return 'cash_flow';
        if (str_contains($message, 'budget')) return 'budget';
        
        return 'general';
    }

    protected function extractReportParameters(string $message): array
    {
        $period = $this->extractReportPeriod($message);
        $type = $this->extractReportType($message);

        return [
            'type' => $type,
            'period' => $period,
        ];
    }

    /**
     * Check if an expense query should generate a report (has specific category, date, or amount query)
     */
    protected function hasReportableExpenseQuery(string $message): bool
    {
        // Check for specific category mentions (e.g., "coffee", "office supplies", "travel")
        if (preg_match('/(?:on|for|spent on|spending on|expenses? for)\s+([a-z\s]+?)(?:\s|$|,|\.|on|in|during)/i', $message, $matches)) {
            $category = trim($matches[1]);
            // Exclude common words that aren't categories
            $excludeWords = ['the', 'a', 'an', 'we', 'did', 'do', 'how', 'much', 'what', 'when', 'where', 'which', 'all', 'any'];
            if (!in_array(strtolower($category), $excludeWords) && strlen($category) > 2) {
                return true;
            }
        }
        
        // Check for specific date mentions (e.g., "14th of november", "november 14", "last year")
        if (preg_match('/(\d{1,2})(?:st|nd|rd|th)?\s+(?:of\s+)?(january|february|march|april|may|june|july|august|september|october|november|december)/i', $message)) {
            return true;
        }
        if (preg_match('/(january|february|march|april|may|june|july|august|september|october|november|december)\s+(\d{1,2})(?:st|nd|rd|th)?/i', $message)) {
            return true;
        }
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/', $message)) {
            return true;
        }
        
        // Check for "how much" or "what" questions about expenses
        if ((str_contains($message, 'how much') || str_contains($message, 'what')) 
            && (str_contains($message, 'spend') || str_contains($message, 'spent') || str_contains($message, 'expense'))) {
            return true;
        }
        
        // Check for "recorded" or "we recorded" with dates
        if (str_contains($message, 'recorded') && (str_contains($message, 'on') || str_contains($message, 'in'))) {
            return true;
        }
        
        return false;
    }

    /**
     * Extract parameters for expense report from query
     */
    protected function extractExpenseReportParameters(string $message): array
    {
        $params = $this->extractReportParameters($message);
        $params['type'] = 'expenses'; // Always expenses for expense queries
        
        // Extract category if mentioned
        if (preg_match('/(?:on|for|spent on|spending on|expenses? for)\s+([a-z\s]+?)(?:\s|$|,|\.|on|in|during)/i', $message, $matches)) {
            $category = trim($matches[1]);
            $excludeWords = ['the', 'a', 'an', 'we', 'did', 'do', 'how', 'much', 'what', 'when', 'where', 'which', 'all', 'any'];
            if (!in_array(strtolower($category), $excludeWords) && strlen($category) > 2) {
                $params['category'] = $category;
            }
        }
        
        // Extract specific date if mentioned
        $specificDate = $this->extractSpecificDate($message);
        if ($specificDate) {
            $params['specific_date'] = $specificDate;
            // Override period to be that specific date
            $params['period'] = 'specific_date';
        }
        
        return $params;
    }

    /**
     * Extract specific date from message (e.g., "14th of november last year", "november 14, 2024")
     */
    protected function extractSpecificDate(string $message): ?string
    {
        $message = strtolower($message);
        $now = now();
        
        // Pattern: "14th of november" or "14th november" with optional year
        if (preg_match('/(\d{1,2})(?:st|nd|rd|th)?\s+(?:of\s+)?(january|february|march|april|may|june|july|august|september|october|november|december)(?:\s+(\d{4}))?(?:\s+last\s+year)?/i', $message, $matches)) {
            $day = (int) $matches[1];
            $monthName = strtolower($matches[2]);
            $year = isset($matches[3]) ? (int) $matches[3] : null;
            
            // If "last year" mentioned, use previous year
            if (str_contains($message, 'last year')) {
                $year = $now->year - 1;
            } elseif (!$year) {
                // Default to current year, but if the date has passed this year, might be asking about last year
                $year = $now->year;
            }
            
            $month = $this->monthToNumber($monthName);
            return sprintf('%d-%s-%02d', $year, $month, $day);
        }
        
        // Pattern: "november 14" or "november 14, 2024"
        if (preg_match('/(january|february|march|april|may|june|july|august|september|october|november|december)\s+(\d{1,2})(?:st|nd|rd|th)?(?:,\s*(\d{4}))?(?:\s+last\s+year)?/i', $message, $matches)) {
            $monthName = strtolower($matches[1]);
            $day = (int) $matches[2];
            $year = isset($matches[3]) ? (int) $matches[3] : null;
            
            if (str_contains($message, 'last year')) {
                $year = $now->year - 1;
            } elseif (!$year) {
                $year = $now->year;
            }
            
            $month = $this->monthToNumber($monthName);
            return sprintf('%d-%s-%02d', $year, $month, $day);
        }
        
        // Pattern: MM/DD/YYYY or DD/MM/YYYY
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/', $message, $matches)) {
            // Try to determine format - if first number > 12, it's likely DD/MM
            if ((int) $matches[1] > 12) {
                $day = (int) $matches[1];
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = (int) $matches[3];
                if ($year < 100) {
                    $year += 2000;
                }
            } else {
                $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $day = (int) $matches[2];
                $year = (int) $matches[3];
                if ($year < 100) {
                    $year += 2000;
                }
            }
            return sprintf('%d-%s-%02d', $year, $month, $day);
        }
        
        return null;
    }

    protected function extractReportPeriod(string $message): string
    {
        $message = strtolower($message);

        // Check for specific date first - if found, return special marker
        if ($this->extractSpecificDate($message)) {
            return 'specific_date';
        }

        // Daily report patterns - check these FIRST before other patterns
        if (str_contains($message, "today's report") || str_contains($message, 'today report') 
            || str_contains($message, "day's report") || str_contains($message, 'daily report')
            || (str_contains($message, 'report') && str_contains($message, 'today'))
            || (str_contains($message, 'today') && (str_contains($message, 'spend') || str_contains($message, 'expense')))) {
            return 'today';
        }
        if (str_contains($message, "yesterday's report") || str_contains($message, 'yesterday report')
            || (str_contains($message, 'report') && str_contains($message, 'yesterday'))
            || (str_contains($message, 'yesterday') && (str_contains($message, 'spend') || str_contains($message, 'expense')))) {
            return 'yesterday';
        }

        if (preg_match('/last\s+(\d+)\s+day/', $message, $matches)) {
            return 'last_' . $matches[1] . '_days';
        }
        if (preg_match('/last\s+(\d+)\s+week/', $message, $matches)) {
            return 'last_' . $matches[1] . '_weeks';
        }
        if (preg_match('/last\s+(\d+)\s+month/', $message, $matches)) {
            return 'last_' . $matches[1] . '_months';
        }
        // Weekly report patterns
        if (str_contains($message, 'weekly report') || str_contains($message, 'week report')) {
            return 'this_week'; // Default to this week for weekly reports
        }
        if (str_contains($message, 'last week')) return 'last_week';
        if (str_contains($message, 'this week')) return 'this_week';
        // Monthly report patterns
        if (str_contains($message, 'monthly report') || str_contains($message, 'month report')) {
            return 'this_month'; // Default to this month for monthly reports
        }
        if (str_contains($message, 'last month')) return 'last_month';
        if (str_contains($message, 'this month')) return 'this_month';
        if (str_contains($message, 'last quarter')) return 'last_quarter';
        if (str_contains($message, 'this quarter')) return 'this_quarter';
        if (str_contains($message, 'year to date') || str_contains($message, 'ytd')) return 'year_to_date';
        if (str_contains($message, 'last year')) return 'last_year';
        if (str_contains($message, 'this year')) return 'this_year';

        // Default to last 30 days for expense queries without specific period
        return 'last_30_days';
    }

    protected function extractReminderParameters(string $message): array
    {
        $params = [];

        if (preg_match('/invoice\s+#?([a-z0-9\-]+)/i', $message, $matches)) {
            $params['invoice_number'] = strtoupper($matches[1]);
        }

        if (preg_match('/customer\s+([a-z\s]+)/i', $message, $matches)) {
            $params['customer_name'] = Str::title(trim($matches[1]));
        } elseif (preg_match('/for\s+([a-z\s]+?)(?:\s+invoice|$)/i', $message, $matches)) {
            $params['customer_name'] = Str::title(trim($matches[1]));
        }

        if (preg_match('/(\d+)\s+(?:days)?\s*overdue/i', $message, $matches)) {
            $params['min_days_overdue'] = (int) $matches[1];
        }

        if (preg_match('/(\d+)\s+(?:invoices|reminders)/i', $message, $matches)) {
            $params['limit'] = (int) $matches[1];
        }

        if (str_contains($message, 'sms')) {
            $params['channel'] = 'sms';
        } elseif (str_contains($message, 'call')) {
            $params['channel'] = 'call';
        }

        return $params;
    }

    protected function extractLimit(string $message): ?int
    {
        if (preg_match('/(\d+)\s+(?:transactions?|items?)/', $message, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function extractQuoteParameters(string $message): array
    {
        $params = [];

        if (preg_match('/quote\s+#?([a-z0-9\-]+)/i', $message, $matches)) {
            $params['quote_number'] = strtoupper($matches[1]);
        }

        if (preg_match('/for\s+([a-z\s]+?)(?:\s+quote|$)/i', $message, $matches)) {
            $params['customer_name'] = Str::title(trim($matches[1]));
        }

        if (preg_match('/(\d+)\s+(?:quotes?|follow ups?)/i', $message, $matches)) {
            $params['limit'] = (int) $matches[1];
        }

        if (str_contains($message, 'stale') || str_contains($message, 'again')) {
            $params['stale_only'] = true;
        }

        if (preg_match('/expire(?:s|d)?\s+in\s+(\d+)\s+days/i', $message, $matches)) {
            $params['expiring_within_days'] = (int) $matches[1];
        }

        return $params;
    }

    protected function extractInvoicePaymentParameters(string $message): array
    {
        $params = [];

        if (preg_match('/invoice\s+#?([a-z0-9\-]+)/i', $message, $matches)) {
            $params['invoice_number'] = strtoupper($matches[1]);
        }

        if (preg_match('/\$?\s*(\d+(?:\.\d{2})?)/', $message, $matches)) {
            $params['amount'] = (float) $matches[1];
        }

        if (preg_match('/on\s+(\d{4}-\d{2}-\d{2})/i', $message, $matches)) {
            $params['payment_date'] = $matches[1];
        } elseif (preg_match('/on\s+(\d{1,2}\/\d{1,2}\/\d{4})/i', $message, $matches)) {
            $params['payment_date'] = $matches[1];
        }

        if (str_contains($message, 'cash')) {
            $params['payment_method'] = 'cash';
        } elseif (str_contains($message, 'card')) {
            $params['payment_method'] = 'card';
        } elseif (str_contains($message, 'transfer')) {
            $params['payment_method'] = 'bank_transfer';
        }

        if (preg_match('/ref(?:erence)?\s+([a-z0-9\-]+)/i', $message, $matches)) {
            $params['payment_reference'] = strtoupper($matches[1]);
        }

        return $params;
    }

    /**
     * Extract organization parameters from message
     */
    protected function extractOrganizationParameters(string $message): array
    {
        $params = [];
        $originalMessage = $message; // Keep original for better extraction
        $message = trim($message);

        // Try to extract organization name
        // Patterns: "create organization called X", "create company X", "new business named X", etc.
        
        // Pattern 1: "called X", "named X", "for X", "with name X"
        if (preg_match('/(?:called|named|for|with name|with the name)\s+["\']?([^"\']+?)(?:\s|$|["\'])/i', $message, $matches)) {
            $params['name'] = trim($matches[1]);
        } 
        // Pattern 2: "create organization X", "make company X", "new business X" (name comes after the type)
        elseif (preg_match('/(?:create|make|new|start)\s+(?:organization|organisation|company|business)\s+["\']?([^"\']+?)(?:\s|$|["\'])/i', $message, $matches)) {
            $params['name'] = trim($matches[1]);
        } 
        // Pattern 3: "organization X", "company X", "business X" (standalone)
        elseif (preg_match('/(?:organization|organisation|company|business)\s+["\']?([^"\']+?)(?:\s|$|["\'])/i', $message, $matches)) {
            $params['name'] = trim($matches[1]);
        }
        // Pattern 4: "create X organization", "make X company" (name comes before the type)
        elseif (preg_match('/(?:create|make|new|start)\s+["\']?([a-z0-9\s]+?)\s+(?:organization|organisation|company|business)/i', $message, $matches)) {
            $name = trim($matches[1]);
            // Only use if it's not just the action word itself
            if (strlen($name) > 2 && !in_array(strtolower($name), ['a', 'an', 'the', 'new'])) {
                $params['name'] = $name;
            }
        }
        // Pattern 5: "create a new X" or "create X" where X is the name
        elseif (preg_match('/(?:create|make|start)\s+(?:a\s+)?(?:new\s+)?["\']?([a-z0-9\s]+?)(?:\s+(?:organization|organisation|company|business)|$)/i', $message, $matches)) {
            $name = trim($matches[1]);
            // Filter out common words
            $filterWords = ['a', 'an', 'the', 'new', 'organization', 'organisation', 'company', 'business'];
            $nameParts = explode(' ', $name);
            $nameParts = array_filter($nameParts, function($part) use ($filterWords) {
                return !in_array(strtolower($part), $filterWords) && strlen($part) > 0;
            });
            if (!empty($nameParts)) {
                $params['name'] = implode(' ', $nameParts);
            }
        }

        // Clean up the name
        if (isset($params['name'])) {
            $params['name'] = trim($params['name'], ' "\'.,;');
            // Remove common trailing words
            $params['name'] = preg_replace('/\s+(organization|organisation|company|business)$/i', '', $params['name']);
            // Remove empty result
            if (empty($params['name'])) {
                unset($params['name']);
            }
        }

        return $params;
    }
    
    /**
     * Check if message looks like bank statement data
     */
    protected function looksLikeBankStatement(string $message): bool
    {
        // Check for common bank statement patterns
        $patterns = [
            // Date patterns (Sep 23, Sep 24, etc.)
            '/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2}\b/i',
            // Transaction patterns (POS Purchase, Bank To Wallet, FNB OB Pmt, etc.)
            '/\b(POS|Purchase|Bank|Wallet|Payment|Transfer|Withdrawal|Deposit|Credit|Debit|FNB|OB|Pmt)\b/i',
            // Balance patterns (Cr, Dr, or numbers with commas)
            '/\b\d{1,3}(?:,\d{3})*(?:\.\d{2})?\s*(?:Cr|Dr|Balance)?\b/i',
            // Amount patterns (multiple amounts in sequence)
            '/\d+\.\d{2}/',
        ];
        
        $matchCount = 0;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $matchCount++;
            }
        }
        
        // If we have multiple date patterns and transaction patterns, it's likely a bank statement
        $hasMultipleDates = preg_match_all('/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2}\b/i', $message) >= 3;
        $hasTransactionKeywords = preg_match('/\b(POS|Purchase|Bank|Wallet|Payment|Transfer|Withdrawal|Deposit|FNB|OB|Pmt)\b/i', $message);
        $hasAmounts = preg_match_all('/\d+\.\d{2}/', $message) >= 3;
        
        // Also check for common bank statement headers
        $hasHeaders = preg_match('/\b(Description|Amount|Balance|Date|Transaction)\b/i', $message);
        
        return ($hasMultipleDates && $hasTransactionKeywords && $hasAmounts) || 
               ($hasHeaders && $hasAmounts && $hasTransactionKeywords);
    }
}
