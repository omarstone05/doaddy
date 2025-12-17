<?php

namespace App\Services\Addy\Actions;

use App\Models\BudgetLine;

class AdjustBudgetAction extends BaseAction
{
    public function validate(): bool
    {
        return isset($this->parameters['budget_id']) 
            && isset($this->parameters['amount']);
    }

    public function preview(): array
    {
        $budget = BudgetLine::find($this->parameters['budget_id']);
        $newAmount = $this->parameters['amount'];
        $currentAmount = $budget->amount ?? 0;
        $difference = $newAmount - $currentAmount;

        return [
            'title' => 'Adjust Budget',
            'description' => "Update budget '{$budget->name}' from \${$currentAmount} to \${$newAmount}",
            'items' => [
                [
                    'budget_name' => $budget->name,
                    'current_amount' => $currentAmount,
                    'new_amount' => $newAmount,
                    'difference' => $difference,
                ]
            ],
            'impact' => abs($difference) > 1000 ? 'high' : 'medium',
            'warnings' => $difference < 0 ? ['Reducing budget may affect ongoing projects.'] : [],
        ];
    }

    public function execute(): array
    {
        $budget = BudgetLine::find($this->parameters['budget_id']);
        $oldAmount = $budget->amount;
        
        $budget->update([
            'amount' => $this->parameters['amount'],
        ]);

        return [
            'success' => true,
            'budget_id' => $budget->id,
            'old_amount' => $oldAmount,
            'new_amount' => $this->parameters['amount'],
            'message' => "Budget updated successfully.",
        ];
    }
}

