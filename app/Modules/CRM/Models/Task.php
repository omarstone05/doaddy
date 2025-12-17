<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $table = 'crm_tasks';

    protected $fillable = [
        'organization_id',
        'subject',
        'description',
        'priority',
        'related_to_type',
        'related_to_id',
        'assigned_to',
        'created_by',
        'due_date',
        'due_time',
        'completed_at',
        'status',
        'is_completed',
        'reminder_enabled',
        'reminder_datetime',
        'reminder_sent',
        'is_recurring',
        'recurrence_pattern',
    ];

    protected $casts = [
        'due_date' => 'date',
        'due_time' => 'datetime',
        'completed_at' => 'datetime',
        'reminder_datetime' => 'datetime',
        'recurrence_pattern' => 'array',
        'is_completed' => 'boolean',
        'reminder_enabled' => 'boolean',
        'reminder_sent' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    public function relatedTo(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}


