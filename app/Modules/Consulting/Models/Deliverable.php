<?php

namespace App\Modules\Consulting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Deliverable extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'consulting_deliverables';

    protected $fillable = [
        'project_id',
        'milestone_id',
        'name',
        'description',
        'type',
        'order',
        'status',
        'version',
        'due_date',
        'submitted_at',
        'approved_at',
        'owner_id',
        'contributors',
        'approved_by',
        'approval_comments',
        'approval_history',
        'files',
        'version_history',
        'visible_to_client',
        'requires_client_approval',
        'comments',
        'custom_fields',
    ];

    protected $casts = [
        'contributors' => 'array',
        'approval_history' => 'array',
        'files' => 'array',
        'version_history' => 'array',
        'comments' => 'array',
        'custom_fields' => 'array',
        'version' => 'integer',
        'visible_to_client' => 'boolean',
        'requires_client_approval' => 'boolean',
        'due_date' => 'date',
        'submitted_at' => 'date',
        'approved_at' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'review');
    }
}

