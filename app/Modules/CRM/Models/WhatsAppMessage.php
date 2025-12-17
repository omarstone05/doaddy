<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WhatsAppMessage extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $table = 'crm_whatsapp_messages';

    protected $fillable = [
        'organization_id',
        'message_type',
        'from_number',
        'to_number',
        'message_body',
        'message_status',
        'related_to_type',
        'related_to_id',
        'has_media',
        'media_type',
        'media_url',
        'template_id',
        'template_variables',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'failure_reason',
        'sent_by',
        'is_automated',
        'campaign_id',
    ];

    protected $casts = [
        'template_variables' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
        'has_media' => 'boolean',
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


