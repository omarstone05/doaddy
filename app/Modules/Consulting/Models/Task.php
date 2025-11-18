<?php

namespace App\Modules\Consulting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Task extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'consulting_tasks';

    protected $fillable = [
        'project_id',
        'parent_task_id',
        'milestone_id',
        'title',
        'description',
        'order',
        'assigned_to',
        'assigned_team',
        'created_by',
        'status',
        'progress_percentage',
        'priority',
        'start_date',
        'due_date',
        'completed_at',
        'estimated_hours',
        'actual_hours',
        'billable',
        'visible_to_client',
        'checklist',
        'tags',
        'attachments',
        'custom_fields',
    ];

    protected $casts = [
        'assigned_team' => 'array',
        'checklist' => 'array',
        'tags' => 'array',
        'attachments' => 'array',
        'custom_fields' => 'array',
        'progress_percentage' => 'integer',
        'estimated_hours' => 'integer',
        'actual_hours' => 'integer',
        'billable' => 'boolean',
        'visible_to_client' => 'boolean',
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function parentTask()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedTeam()
    {
        return $this->belongsToMany(User::class, 'consulting_task_assignees', 'task_id', 'user_id')
            ->withTimestamps();
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'consulting_task_followers', 'task_id', 'user_id')
            ->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id')
            ->whereNull('parent_comment_id')
            ->orderBy('created_at', 'asc');
    }

    public function allComments()
    {
        return $this->hasMany(TaskComment::class, 'task_id')
            ->orderBy('created_at', 'asc');
    }

    public function steps()
    {
        return $this->hasMany(TaskStep::class, 'task_id')
            ->orderBy('order', 'asc');
    }

    public function dependencies()
    {
        return $this->belongsToMany(Task::class, 'consulting_task_dependencies', 'task_id', 'depends_on_task_id')
            ->withPivot('dependency_type', 'lag_days')
            ->withTimestamps();
    }

    public function dependents()
    {
        return $this->belongsToMany(Task::class, 'consulting_task_dependencies', 'depends_on_task_id', 'task_id')
            ->withPivot('dependency_type', 'lag_days')
            ->withTimestamps();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
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

