<?php

namespace App\Modules\Consulting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TimeEntry extends Model
{
    use HasUuids;

    protected $table = 'consulting_time_entries';

    protected $fillable = [
        'project_id',
        'task_id',
        'user_id',
        'date',
        'start_time',
        'end_time',
        'minutes',
        'description',
        'billable',
        'hourly_rate',
        'billed',
        'approval_status',
        'approved_by',
        'approved_at',
        'is_running',
        'timer_started_at',
    ];

    protected $casts = [
        'minutes' => 'integer',
        'billable' => 'boolean',
        'billed' => 'boolean',
        'is_running' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'approved_at' => 'datetime',
        'timer_started_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getHoursAttribute()
    {
        return round($this->minutes / 60, 2);
    }

    public function getBillableAmountAttribute()
    {
        if (!$this->billable || !$this->hourly_rate) {
            return 0;
        }
        return ($this->minutes / 60) * $this->hourly_rate;
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeBillable($query)
    {
        return $query->where('billable', true);
    }

    public function scopeRunning($query)
    {
        return $query->where('is_running', true);
    }
}

