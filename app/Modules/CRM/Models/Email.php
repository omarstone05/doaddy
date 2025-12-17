<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Email extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $table = 'crm_emails';

    protected $fillable = [
        'organization_id',
        'email_type',
        'from_address',
        'to_addresses',
        'cc_addresses',
        'bcc_addresses',
        'subject',
        'body_html',
        'body_text',
        'related_to_type',
        'related_to_id',
        'is_read',
        'is_replied',
        'bounced',
        'opened_count',
        'last_opened_at',
        'clicked_count',
        'thread_id',
        'in_reply_to',
        'message_id',
        'has_attachments',
        'attachments',
        'template_id',
        'sent_by',
        'sent_at',
        'received_at',
        'scheduled_at',
    ];

    protected $casts = [
        'to_addresses' => 'array',
        'cc_addresses' => 'array',
        'bcc_addresses' => 'array',
        'attachments' => 'array',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'last_opened_at' => 'datetime',
        'is_read' => 'boolean',
        'is_replied' => 'boolean',
        'bounced' => 'boolean',
        'has_attachments' => 'boolean',
    ];

    public function relatedTo(): MorphTo
    {
        return $this->morphTo();
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'sent_by');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }
}


