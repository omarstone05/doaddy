<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StrategicGoal extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'status',
        'target_date',
        'progress_percentage',
        'notes',
        'owner_id',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'progress_percentage' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(GoalMilestone::class, 'strategic_goal_id')->orderBy('display_order');
    }

    public function updateProgress(): void
    {
        $milestones = $this->milestones;
        if ($milestones->isEmpty()) {
            $this->progress_percentage = 0;
        } else {
            $completedCount = $milestones->where('status', 'completed')->count();
            $this->progress_percentage = (int) round(($completedCount / $milestones->count()) * 100);
        }
        $this->save();
    }
}

