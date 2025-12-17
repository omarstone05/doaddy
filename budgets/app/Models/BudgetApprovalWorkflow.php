<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetApprovalWorkflow extends BaseModel
{
    protected $fillable = [
        'organization_id',
        'name',
        'stages',
        'trigger_conditions',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'stages' => 'array',
        'trigger_conditions' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
