<?php

namespace App\Modules\Consulting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ChangeOrder extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'consulting_change_orders';

    protected $fillable = [
        'project_id',
        'number',
        'title',
        'description',
        'reason',
        'cost_impact',
        'timeline_impact_days',
        'scope_impact',
        'status',
        'requested_by',
        'internal_approved_by',
        'internal_approved_at',
        'internal_comments',
        'client_approved_by',
        'client_approved_at',
        'client_comments',
        'version',
        'version_history',
        'attachments',
    ];

    protected $casts = [
        'cost_impact' => 'decimal:2',
        'timeline_impact_days' => 'integer',
        'version' => 'integer',
        'version_history' => 'array',
        'attachments' => 'array',
        'internal_approved_at' => 'datetime',
        'client_approved_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function internalApprover()
    {
        return $this->belongsTo(User::class, 'internal_approved_by');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending_internal', 'pending_client']);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}

