<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetInsight extends BaseModel
{
    protected $fillable = [
        'organization_id',
        'budget_id',
        'insight_type',
        'severity',
        'title',
        'description',
        'ai_model',
        'confidence_score',
        'recommendations',
        'is_dismissed',
    ];

    protected $casts = [
        'recommendations' => 'array',
        'is_dismissed' => 'boolean',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }
}
