<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessValuation extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'valuation_date',
        'valuation_amount',
        'currency',
        'valuation_method',
        'method_details',
        'assumptions',
        'notes',
        'valued_by_id',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'valuation_date' => 'date',
            'valuation_amount' => 'decimal:2',
        ];
    }

    public function valuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valued_by_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}

