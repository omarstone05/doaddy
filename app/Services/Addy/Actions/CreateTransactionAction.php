<?php

namespace App\Services\Addy\Actions;

use App\Models\MoneyMovement;
use App\Models\MoneyAccount;

class CreateTransactionAction extends BaseAction
{
    public function validate(): bool
    {
        // For preview, we only need amount and flow_type
        // account_id can be set later or use default
        return isset($this->parameters['amount']) 
            && isset($this->parameters['flow_type']);
    }

    public function preview(): array
    {
        $amount = $this->parameters['amount'];
        $flowType = $this->parameters['flow_type'];
        
        // Get default account if not specified
        $accountId = $this->parameters['account_id'] ?? $this->getDefaultAccountId();
        $account = $accountId ? MoneyAccount::find($accountId) : null;

        $preview = [
            'title' => 'Create Transaction',
            'description' => "Record a new {$flowType} of \${$amount}",
            'items' => [
                [
                    'type' => ucfirst($flowType),
                    'amount' => $amount,
                    'account' => $account->name ?? 'Account not specified',
                    'category' => $this->parameters['category'] ?? 'Uncategorized',
                    'description' => $this->parameters['description'] ?? '',
                    'date' => $this->parameters['date'] ?? now()->toDateString(),
                ]
            ],
            'impact' => $amount > 1000 ? 'high' : 'medium',
            'warnings' => [],
        ];
        
        // Add warning if no account specified
        if (!$accountId) {
            $preview['warnings'][] = 'Please specify which account to use, or I will use the default account.';
        }
        
        // Store the account_id for execution
        if ($accountId) {
            $this->parameters['account_id'] = $accountId;
        }

        return $preview;
    }
    
    protected function getDefaultAccountId()
    {
        // Get the first active account for the organization
        $account = MoneyAccount::where('organization_id', $this->organization->id)
            ->where('is_active', true)
            ->first();
        
        // If no account exists, create a default one
        if (!$account) {
            try {
                $account = MoneyAccount::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'organization_id' => $this->organization->id,
                    'name' => 'Default Account',
                    'type' => 'bank',
                    'currency' => $this->organization->currency ?? 'ZMW',
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'is_active' => true,
                ]);
                \Log::info('Created default account for organization', ['organization_id' => $this->organization->id, 'account_id' => $account->id]);
            } catch (\Exception $e) {
                \Log::error('Failed to create default account', [
                    'error' => $e->getMessage(),
                    'organization_id' => $this->organization->id,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw new \Exception('Unable to create a default account. Please create an account manually in the Money section first.');
            }
        }
            
        return $account?->id;
    }

    public function execute(): array
    {
        // Ensure account_id is set (use default if not provided)
        if (!isset($this->parameters['account_id'])) {
            $this->parameters['account_id'] = $this->getDefaultAccountId();
        }
        
        if (!$this->parameters['account_id']) {
            throw new \Exception('No account specified and no default account available. Please specify an account.');
        }
        
        $transaction = MoneyMovement::create([
            'organization_id' => $this->organization->id,
            'from_account_id' => $this->parameters['flow_type'] === 'expense' ? $this->parameters['account_id'] : null,
            'to_account_id' => $this->parameters['flow_type'] === 'income' ? $this->parameters['account_id'] : null,
            'flow_type' => $this->parameters['flow_type'],
            'amount' => $this->parameters['amount'],
            'category' => $this->parameters['category'] ?? 'Uncategorized',
            'description' => $this->parameters['description'] ?? '',
            'transaction_date' => $this->parameters['date'] ?? now(),
            'status' => 'approved',
            'created_by_id' => $this->user->id,
        ]);

        // Update account balance
        $account = MoneyAccount::find($this->parameters['account_id']);
        if ($account) {
            if ($this->parameters['flow_type'] === 'income') {
                $account->increment('current_balance', $this->parameters['amount']);
            } else {
                $account->decrement('current_balance', $this->parameters['amount']);
            }
        }

        return [
            'success' => true,
            'transaction_id' => $transaction->id,
            'message' => "Transaction created successfully.",
        ];
    }

    public function canUndo(): bool
    {
        return true;
    }

    public function undo(array $result): array
    {
        $transaction = MoneyMovement::find($result['transaction_id']);
        
        if ($transaction) {
            // Reverse account balance
            $account = $transaction->flow_type === 'income' 
                ? $transaction->toAccount 
                : $transaction->fromAccount;
                
            if ($account) {
                if ($transaction->flow_type === 'income') {
                    $account->decrement('current_balance', $transaction->amount);
                } else {
                    $account->increment('current_balance', $transaction->amount);
                }
            }

            $transaction->delete();
        }

        return [
            'success' => true,
            'message' => 'Transaction deleted.',
        ];
    }
}

