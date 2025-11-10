<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddyPrediction extends Model
{
    protected $fillable = [
        'organization_id',
        'type',
        'category',
        'prediction_date',
        'target_date',
        'predicted_value',
        'confidence',
        'factors',
        'metadata',
        'actual_value',
        'accuracy',
    ];

    protected $casts = [
        'prediction_date' => 'date',
        'target_date' => 'date',
        'predicted_value' => 'decimal:2',
        'confidence' => 'decimal:2',
        'factors' => 'array',
        'metadata' => 'array',
        'actual_value' => 'decimal:2',
        'accuracy' => 'decimal:2',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get latest prediction of a type
     */
    public static function getLatest($organizationId, string $type, string $targetDate = null)
    {
        $query = self::where('organization_id', $organizationId)
            ->where('type', $type);
        
        if ($targetDate) {
            $query->where('target_date', $targetDate);
        }
        
        return $query->latest('prediction_date')->first();
    }

    /**
     * Calculate accuracy after actual value is known
     */
    public function calculateAccuracy(): void
    {
        if (!$this->actual_value || !$this->predicted_value) {
            return;
        }

        $error = abs($this->actual_value - $this->predicted_value);
        $percentError = $this->actual_value > 0 
            ? ($error / $this->actual_value) * 100 
            : 100;

        $accuracy = max(0, 100 - $percentError) / 100;

        $this->update(['accuracy' => $accuracy]);
    }
}

