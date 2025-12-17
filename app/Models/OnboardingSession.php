<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingSession extends Model
{
    protected $fillable = [
        'user_id',
        'current_phase',
        'data',
        'completed',
        'completed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the session
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get specific data field
     */
    public function getData(string $key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Set specific data field
     */
    public function setData(string $key, $value): void
    {
        $data = $this->data ?? [];
        data_set($data, $key, $value);
        $this->data = $data;
        $this->save();
    }

    /**
     * Check if phase is complete
     */
    public function isPhaseComplete(string $phase): bool
    {
        $phaseOrder = [
            'arrival',
            'business_description',
            'business_classification',
            'priorities',
            'team_size',
            'financial_rhythm',
            'summary',
            'complete',
        ];

        $currentIndex = array_search($this->current_phase, $phaseOrder);
        $checkIndex = array_search($phase, $phaseOrder);

        return $currentIndex !== false && $checkIndex !== false && $currentIndex > $checkIndex;
    }
}

