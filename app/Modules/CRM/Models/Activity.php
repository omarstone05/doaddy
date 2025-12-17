<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $table = 'crm_activities';

    protected $fillable = [
        'organization_id',
        'activity_type',
        'direction',
        'related_to_type',
        'related_to_id',
        'subject',
        'description',
        'location',
        'activity_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'all_day',
        'status',
        'is_completed',
        'completed_at',
        'owner_id',
        'participants',
        'outcome',
        'outcome_notes',
        'next_activity_type',
        'next_activity_date',
        'next_activity_notes',
        'from_address',
        'to_addresses',
        'cc_addresses',
        'message_id',
        'email_subject',
        'email_body',
        'attachments',
        'is_automated',
        'workflow_id',
        'reminder_enabled',
        'reminder_datetime',
        'reminder_sent',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'next_activity_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'completed_at' => 'datetime',
        'reminder_datetime' => 'datetime',
        'participants' => 'array',
        'to_addresses' => 'array',
        'cc_addresses' => 'array',
        'attachments' => 'array',
        'all_day' => 'boolean',
        'is_completed' => 'boolean',
        'is_automated' => 'boolean',
        'reminder_enabled' => 'boolean',
        'reminder_sent' => 'boolean',
    ];

    public function relatedTo(): MorphTo
    {
        return $this->morphTo();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }
}


