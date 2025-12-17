<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetAlert extends BaseModel
{
    protected $fillable = [
        'organization_id',
        'budget_id',
        'alert_type',
        'severity',
        'title',
        'message',
        'threshold_percentage',
        'current_percentage',
        'is_resolved',
        'snoozed_until',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'snoozed_until' => 'datetime',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }
}
