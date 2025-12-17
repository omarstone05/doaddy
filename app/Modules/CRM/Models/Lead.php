<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $table = 'crm_leads';

    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'company_name',
        'job_title',
        'email',
        'phone',
        'secondary_phone',
        'whatsapp_number',
        'address',
        'city',
        'province',
        'country',
        'website',
        'lead_source',
        'source_details',
        'lead_status',
        'lead_score',
        'rating',
        'qualification_notes',
        'interested_in',
        'budget_range',
        'timeline',
        'estimated_value',
        'assigned_to',
        'assigned_date',
        'last_contacted_at',
        'next_follow_up_date',
        'is_converted',
        'converted_to_contact_id',
        'converted_to_opportunity_id',
        'converted_at',
        'converted_by',
        'is_lost',
        'lost_reason',
        'lost_at',
        'notes',
        'tags',
        'custom_fields',
        'do_not_call',
        'do_not_email',
        'do_not_whatsapp',
        'email_bounced',
        'unsubscribed',
    ];

    protected $casts = [
        'interested_in' => 'array',
        'tags' => 'array',
        'custom_fields' => 'array',
        'assigned_date' => 'datetime',
        'last_contacted_at' => 'datetime',
        'next_follow_up_date' => 'date',
        'converted_at' => 'datetime',
        'lost_at' => 'datetime',
        'estimated_value' => 'decimal:2',
        'is_converted' => 'boolean',
        'is_lost' => 'boolean',
        'do_not_call' => 'boolean',
        'do_not_email' => 'boolean',
        'do_not_whatsapp' => 'boolean',
        'email_bounced' => 'boolean',
        'unsubscribed' => 'boolean',
    ];

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function convertedToContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'converted_to_contact_id');
    }

    public function convertedToOpportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'converted_to_opportunity_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'related_to_id')
            ->where('related_to_type', self::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}


