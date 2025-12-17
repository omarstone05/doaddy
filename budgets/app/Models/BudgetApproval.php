<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetApproval extends BaseModel
{
    protected $fillable = [
        'budget_id',
        'workflow_id',
        'stage_number',
        'stage_name',
        'approver_id',
        'status',
        'modifications',
    ];

    protected $casts = [
        'modifications' => 'array',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(BudgetApprovalWorkflow::class, 'workflow_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
