<?php

namespace App\Modules\Consulting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Milestone extends Model
{
    use HasUuids;

    protected $table = 'consulting_milestones';

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'order',
        'due_date',
        'completed_at',
        'payment_amount',
        'payment_released',
        'status',
        'depends_on_milestone_id',
    ];

    protected $casts = [
        'order' => 'integer',
        'payment_amount' => 'decimal:2',
        'payment_released' => 'boolean',
        'due_date' => 'date',
        'completed_at' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function dependsOn()
    {
        return $this->belongsTo(Milestone::class, 'depends_on_milestone_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', 'completed');
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'completed';
    }
}

