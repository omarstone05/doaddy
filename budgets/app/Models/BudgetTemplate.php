<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTemplate extends BaseModel
{
    protected $fillable = [
        'organization_id',
        'name',
        'industry',
        'template_data',
    ];

    protected $casts = [
        'template_data' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
