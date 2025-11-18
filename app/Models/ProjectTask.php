<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTask extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'project_id',
        'organization_id',
        'title',
        'description',
        'status',
        'priority',
        'assigned_to_id',
        'created_by_id',
        'due_date',
        'start_date',
        'estimated_hours',
        'actual_hours',
        'order',
        'parent_task_id',
        'tags',
        'started_working_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'start_date' => 'date',
            'estimated_hours' => 'integer',
            'actual_hours' => 'integer',
            'order' => 'integer',
            'tags' => 'array',
            'started_working_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'parent_task_id')->orderBy('order');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(ProjectTimeEntry::class, 'task_id');
    }

    /**
     * Many-to-many relationship with users (task assignments with privileges)
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id')
            ->withPivot([
                'assigned_by_id',
                'can_edit',
                'can_delete',
                'can_assign',
                'can_view_time',
                'can_manage_subtasks',
                'can_change_status',
                'can_change_priority',
                'assigned_at',
            ])
            ->withTimestamps()
            ->orderBy('assigned_at', 'desc');
    }

    /**
     * Check if a user has a specific privilege on this task
     */
    public function userHasPrivilege($userId, string $privilege): bool
    {
        $assignment = $this->assignedUsers()
            ->where('users.id', $userId)
            ->first();
        
        if (!$assignment) {
            return false;
        }

        $privilegeField = 'can_' . str_replace('can_', '', $privilege);
        return $assignment->pivot->$privilegeField ?? false;
    }

    /**
     * Get user's privileges for this task
     */
    public function getUserPrivileges($userId): ?array
    {
        $assignment = $this->assignedUsers()
            ->where('users.id', $userId)
            ->first();
        
        if (!$assignment) {
            return null;
        }

        return [
            'can_edit' => $assignment->pivot->can_edit,
            'can_delete' => $assignment->pivot->can_delete,
            'can_assign' => $assignment->pivot->can_assign,
            'can_view_time' => $assignment->pivot->can_view_time,
            'can_manage_subtasks' => $assignment->pivot->can_manage_subtasks,
            'can_change_status' => $assignment->pivot->can_change_status,
            'can_change_priority' => $assignment->pivot->can_change_priority,
            'assigned_at' => $assignment->pivot->assigned_at,
            'assigned_by_id' => $assignment->pivot->assigned_by_id,
        ];
    }

    /**
     * Check if task is currently being worked on
     */
    public function isBeingWorkedOn(): bool
    {
        return $this->status === 'in_progress' && $this->started_working_at !== null;
    }

    /**
     * Start working on this task
     */
    public function startWork(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_working_at' => now(),
        ]);
    }

    /**
     * Stop working on this task
     */
    public function stopWork(string $newStatus = 'todo'): void
    {
        $this->update([
            'status' => $newStatus,
            'started_working_at' => null,
        ]);
    }

    /**
     * Get the duration the task has been worked on (in minutes)
     */
    public function getWorkDurationInMinutes(): ?int
    {
        if (!$this->started_working_at) {
            return null;
        }

        return now()->diffInMinutes($this->started_working_at);
    }
}

