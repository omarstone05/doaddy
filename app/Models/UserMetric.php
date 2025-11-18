<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMetric extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'metric_type',
        'value',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'value' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the metric
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by metric type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('metric_type', $type);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}



