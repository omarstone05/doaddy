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
}

