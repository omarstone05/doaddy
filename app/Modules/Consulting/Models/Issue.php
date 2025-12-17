<?php

namespace App\Modules\Consulting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Issue extends Model
{
    use HasUuids;

    protected $table = 'consulting_issues';

    protected $fillable = [
        'project_id',
        'task_id',
        'title',
        'description',
        'type',
        'severity',
        'priority',
        'reported_by',
        'assigned_to',
        'status',
        'resolution_notes',
        'deadline',
        'resolved_at',
        'blocks_progress',
        'affected_tasks',
        'attachments',
        'comments',
    ];

    protected $casts = [
        'blocks_progress' => 'boolean',
        'affected_tasks' => 'array',
        'attachments' => 'array',
        'comments' => 'array',
        'deadline' => 'date',
        'resolved_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'investigating', 'in_progress']);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
            ->where('status', '!=', 'closed');
    }
}

