<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetItem extends BaseModel
{
    protected $fillable = [
        'budget_id',
        'category_id',
        'name',
        'description',
        'item_code',
        'budgeted_amount',
        'spent_amount',
        'committed_amount',
        'item_type',
        'frequency',
        'sort_order',
        'notes',
        'tags',
        'is_active',
    ];

    protected $casts = [
        'budgeted_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BudgetTransaction::class);
    }
}
