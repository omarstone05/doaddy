<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetForecast extends BaseModel
{
    protected $fillable = [
        'budget_id',
        'forecast_date',
        'predicted_spend',
        'details',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'predicted_spend' => 'decimal:2',
        'details' => 'array',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }
}
