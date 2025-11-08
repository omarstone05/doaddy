<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class GoalMilestone extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'strategic_goal_id',
        'title',
        'description',
        'target_date',
        'completed_date',
        'status',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'completed_date' => 'date',
            'display_order' => 'integer',
        ];
    }

    public function strategicGoal(): BelongsTo
    {
        return $this->belongsTo(StrategicGoal::class, 'strategic_goal_id');
    }

    protected static function booted(): void
    {
        static::saving(function ($milestone) {
            // Auto-update status based on dates
            if ($milestone->completed_date) {
                $milestone->status = 'completed';
            } elseif ($milestone->target_date && Carbon::parse($milestone->target_date)->isPast() && $milestone->status !== 'completed') {
                $milestone->status = 'overdue';
            } elseif ($milestone->status === 'overdue' && !Carbon::parse($milestone->target_date)->isPast()) {
                $milestone->status = 'pending';
            }

            // Update parent goal progress
            if ($milestone->strategicGoal) {
                $milestone->strategicGoal->updateProgress();
            }
        });
    }
}

