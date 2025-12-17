<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTransaction extends BaseModel
{
    protected $fillable = [
        'organization_id',
        'budget_id',
        'budget_item_id',
        'category_id',
        'transaction_date',
        'description',
        'amount',
        'currency_code',
        'transaction_type',
        'source_app',
        'source_id',
        'source_data',
        'is_auto_categorized',
        'ai_confidence',
        'category_overridden',
        'is_reconciled',
        'reconciled_at',
        'reconciled_by',
        'receipt_url',
        'receipt_data',
        'notes',
        'tags',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'ai_confidence' => 'decimal:2',
        'is_auto_categorized' => 'boolean',
        'category_overridden' => 'boolean',
        'is_reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
        'source_data' => 'array',
        'receipt_data' => 'array',
        'tags' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}
