<?php

namespace App\Services\Addy;

class AddyCommandParser
{
    /**
     * Parse user message and extract intent
     */
    public function parse(string $message): array
    {
        $message = strtolower(trim($message));
        
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
        
        // Expense queries
        if ($this->isExpenseQuery($message)) {
            return [
                'intent' => 'query_expenses',
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
            'confirm', 'record', 'add', 'log', 'enter', 'register'
        ];
        
        foreach ($actionKeywords as $keyword) {
            if (str_starts_with($message, $keyword) || str_contains($message, $keyword)) {
                return true;
            }
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
        if (str_contains($message, 'send') && (str_contains($message, 'reminder') || str_contains($message, 'invoice'))) {
            return [
                'action_type' => 'send_invoice_reminders',
                'parameters' => [],
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
        
        // Generate report
        if (str_contains($message, 'generate') || str_contains($message, 'create report')) {
            return [
                'action_type' => 'generate_report',
                'parameters' => ['type' => $this->extractReportType($message)],
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
        
        // Extract customer name
        if (preg_match('/(?:for|to)\s+([a-z\s]+?)(?:\s|$|,|\.|for|invoice)/i', $message, $matches)) {
            $params['customer_name'] = trim($matches[1]);
        }
        
        // Extract amount
        if (preg_match('/\$?(\d+(?:\.\d{2})?)/', $message, $matches)) {
            $params['total_amount'] = (float) $matches[1];
        }
        
        // Extract date
        if (preg_match('/(\d{4}-\d{2}-\d{2})|(\d{1,2}\/\d{1,2}\/\d{4})/', $message, $matches)) {
            $params['invoice_date'] = $matches[0];
        }
        
        return $params;
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
}

