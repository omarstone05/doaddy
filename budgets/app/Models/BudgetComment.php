<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetComment extends BaseModel
{
    protected $fillable = [
        'budget_id',
        'user_id',
        'comment',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
