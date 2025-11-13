<?php

namespace App\Services\Addy\Actions;

use App\Models\MoneyMovement;
use App\Models\MoneyAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\Addy\TransactionCategorizer;

class ImportBankStatementAction extends BaseAction
{
    protected TransactionCategorizer $categorizer;

    public function __construct($organization, $user, array $parameters = [])
    {
        parent::__construct($organization, $user, $parameters);
        $this->categorizer = new TransactionCategorizer();
    }

    public function validate(): bool
    {
        return isset($this->parameters['transactions']) 
            && is_array($this->parameters['transactions']) 
            && count($this->parameters['transactions']) > 0;
    }

    public function preview(): array
    {
        $transactions = $this->parameters['transactions'] ?? [];
        $accountNumber = $this->parameters['account_number'] ?? null;
        $statementPeriod = $this->parameters['statement_period_start'] ?? null 
            ? "{$this->parameters['statement_period_start']} to {$this->parameters['statement_period_end']}"
            : null;
        
        // Get or create account
        $accountId = $this->parameters['account_id'] ?? $this->getOrCreateAccount($accountNumber);
        
        // Analyze transactions
        $totalIncome = 0;
        $totalExpenses = 0;
        $incomeCount = 0;
        $expenseCount = 0;
        
        foreach ($transactions as $tx) {
            $amount = (float) ($tx['amount'] ?? 0);
            $flowType = $tx['flow_type'] ?? ($tx['type'] === 'credit' ? 'income' : 'expense');
            
            if ($flowType === 'income') {
                $totalIncome += $amount;
                $incomeCount++;
            } else {
                $totalExpenses += $amount;
                $expenseCount++;
            }
        }
        
        // Check for duplicates
        $duplicates = $this->checkDuplicates($transactions);
        
        $preview = [
            'title' => 'Import Bank Statement',
            'description' => "Import " . count($transactions) . " transaction(s) from bank statement",
            'items' => array_slice($transactions, 0, 10), // Show first 10 for preview
            'impact' => ($totalIncome + $totalExpenses) > 10000 ? 'high' : 'medium',
            'warnings' => [],
            'summary' => [
                'total_transactions' => count($transactions),
                'income_count' => $incomeCount,
                'expense_count' => $expenseCount,
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'account_number' => $accountNumber,
                'statement_period' => $statementPeriod,
                'duplicate_count' => count($duplicates),
            ],
        ];
        
        if (!empty($duplicates)) {
            $preview['warnings'][] = count($duplicates) . ' transaction(s) may be duplicates. They will be skipped.';
        }
        
        if (!$accountId) {
            $preview['warnings'][] = 'No account found. A default account will be created.';
        }
        
        // Store account_id for execution
        if ($accountId) {
            $this->parameters['account_id'] = $accountId;
        }
        
        return $preview;
    }

    public function execute(): array
    {
        DB::beginTransaction();
        try {
            $transactions = $this->parameters['transactions'] ?? [];
            $accountNumber = $this->parameters['account_number'] ?? null;
            
            // Get or create account
            $accountId = $this->parameters['account_id'] ?? $this->getOrCreateAccount($accountNumber);
            
            if (!$accountId) {
                throw new \Exception('Could not find or create account for import.');
            }
            
            // Check for duplicates
            $duplicates = $this->checkDuplicates($transactions);
            $duplicateHashes = array_flip(array_column($duplicates, 'hash'));
            
            // Import transactions
            $imported = 0;
            $skipped = 0;
            $errors = [];
            
            foreach ($transactions as $tx) {
                // Skip duplicates
                $txHash = $this->getTransactionHash($tx);
                if (isset($duplicateHashes[$txHash])) {
                    $skipped++;
                    continue;
                }
                
                try {
                    $amount = (float) ($tx['amount'] ?? 0);
                    if ($amount <= 0) {
                        $skipped++;
                        continue;
                    }
                    
                    $flowType = $tx['flow_type'] ?? ($tx['type'] === 'credit' || strtolower($tx['type'] ?? '') === 'credit' ? 'income' : 'expense');
                    
                    // Parse date
                    $date = $this->parseDate($tx['date'] ?? null);
                    if (!$date) {
                        $date = now();
                    }
                    
                    // Create transaction
                    [$category] = $this->categorizer->guess($tx['description'] ?? '', $flowType);

                    $transaction = MoneyMovement::create([
                        'organization_id' => $this->organization->id,
                        'from_account_id' => $flowType === 'expense' ? $accountId : null,
                        'to_account_id' => $flowType === 'income' ? $accountId : null,
                        'flow_type' => $flowType,
                        'amount' => $amount,
                        'category' => $tx['category'] ?? $category,
                        'description' => $tx['description'] ?? 'Bank statement transaction',
                        'transaction_date' => $date,
                        'status' => 'approved',
                        'created_by_id' => $this->user->id,
                    ]);
                    
                    // Update account balance
                    $account = MoneyAccount::find($accountId);
                    if ($account) {
                        if ($flowType === 'income') {
                            $account->increment('current_balance', $amount);
                        } else {
                            $account->decrement('current_balance', $amount);
                        }
                    }
                    
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'transaction' => $tx,
                        'error' => $e->getMessage(),
                    ];
                    \Log::error('Failed to import transaction', [
                        'error' => $e->getMessage(),
                        'transaction' => $tx,
                    ]);
                }
            }
            
            DB::commit();
            
            $message = "Successfully imported {$imported} transaction(s)";
            if ($skipped > 0) {
                $message .= ", skipped {$skipped} duplicate(s)";
            }
            if (count($errors) > 0) {
                $message .= ", {$errors} error(s)";
            }
            $message .= ".";
            
            return [
                'success' => true,
                'imported_count' => $imported,
                'skipped_count' => $skipped,
                'error_count' => count($errors),
                'errors' => $errors,
                'message' => $message,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Bank statement import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
    
    protected function getOrCreateAccount(?string $accountNumber): ?string
    {
        // Try to find existing account by number
        if ($accountNumber) {
            $account = MoneyAccount::where('organization_id', $this->organization->id)
                ->where('account_number', $accountNumber)
                ->where('is_active', true)
                ->first();
            
            if ($account) {
                return $account->id;
            }
        }
        
        // Get first active account
        $account = MoneyAccount::where('organization_id', $this->organization->id)
            ->where('is_active', true)
            ->first();
        
        if ($account) {
            return $account->id;
        }
        
        // Create default account
        try {
            $account = MoneyAccount::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $this->organization->id,
                'name' => $accountNumber ? "Account {$accountNumber}" : 'Default Account',
                'type' => 'bank',
                'account_number' => $accountNumber,
                'currency' => $this->organization->currency ?? 'ZMW',
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ]);
            return $account->id;
        } catch (\Exception $e) {
            \Log::error('Failed to create account for import', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    protected function checkDuplicates(array $transactions): array
    {
        $duplicates = [];
        $seen = [];
        
        foreach ($transactions as $tx) {
            $hash = $this->getTransactionHash($tx);
            
            if (isset($seen[$hash])) {
                $duplicates[] = [
                    'hash' => $hash,
                    'transaction' => $tx,
                ];
            } else {
                $seen[$hash] = true;
            }
        }
        
        // Also check against existing transactions in database
        $existingDuplicates = [];
        foreach ($transactions as $tx) {
            $amount = (float) ($tx['amount'] ?? 0);
            $date = $this->parseDate($tx['date'] ?? null);
            $description = $tx['description'] ?? '';
            
            if ($date && $amount > 0) {
                $existing = MoneyMovement::where('organization_id', $this->organization->id)
                    ->where('amount', $amount)
                    ->whereDate('transaction_date', $date->toDateString())
                    ->where('description', 'LIKE', "%{$description}%")
                    ->first();
                
                if ($existing) {
                    $existingDuplicates[] = [
                        'hash' => $this->getTransactionHash($tx),
                        'transaction' => $tx,
                        'existing_id' => $existing->id,
                    ];
                }
            }
        }
        
        return array_merge($duplicates, $existingDuplicates);
    }
    
    protected function getTransactionHash(array $tx): string
    {
        $amount = (float) ($tx['amount'] ?? 0);
        $date = $this->parseDate($tx['date'] ?? null);
        $description = substr($tx['description'] ?? '', 0, 50);
        
        return md5($amount . ($date ? $date->toDateString() : '') . $description);
    }
    
    protected function parseDate($dateInput): ?\Carbon\Carbon
    {
        if (!$dateInput) {
            return null;
        }
        
        // Try various date formats
        try {
            // Format: "Sep 23" or "Sep 23 2025"
            if (preg_match('/(\w+)\s+(\d{1,2})(?:\s+(\d{4}))?/', $dateInput, $matches)) {
                $month = $matches[1];
                $day = $matches[2];
                $year = $matches[3] ?? date('Y');
                
                $monthMap = [
                    'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
                    'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8,
                    'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
                ];
                
                $monthNum = $monthMap[strtolower(substr($month, 0, 3))] ?? null;
                if ($monthNum) {
                    return \Carbon\Carbon::create($year, $monthNum, $day);
                }
            }
            
            // Try standard date parsing
            return \Carbon\Carbon::parse($dateInput);
        } catch (\Exception $e) {
            return null;
        }
    }
    
   
}
