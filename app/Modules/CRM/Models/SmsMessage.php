<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SmsMessage extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $table = 'crm_sms_messages';

    protected $fillable = [
        'organization_id',
        'message_type',
        'from_number',
        'to_number',
        'message_body',
        'message_status',
        'related_to_type',
        'related_to_id',
        'sent_at',
        'delivered_at',
        'failed_at',
        'failure_reason',
        'sent_by',
        'provider',
        'provider_message_id',
        'is_automated',
        'campaign_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'is_automated' => 'boolean',
    ];

    public function relatedTo(): MorphTo
    {
        return $this->morphTo();
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'sent_by');
    }
}


