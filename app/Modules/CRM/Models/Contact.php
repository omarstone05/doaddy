<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $table = 'crm_contacts';

    protected $fillable = [
        'organization_id',
        'customer_id',
        'contact_type',
        'first_name',
        'last_name',
        'full_name',
        'job_title',
        'department',
        'date_of_birth',
        'company_name',
        'company_size',
        'industry',
        'annual_revenue',
        'email_primary',
        'email_secondary',
        'phone_primary',
        'phone_secondary',
        'mobile',
        'whatsapp_number',
        'fax',
        'address_line1',
        'address_line2',
        'city',
        'province',
        'postal_code',
        'country',
        'website',
        'linkedin_url',
        'facebook_url',
        'twitter_handle',
        'account_type',
        'relationship_status',
        'customer_since',
        'owner_id',
        'team_ids',
        'territory',
        'customer_lifetime_value',
        'total_purchases',
        'total_opportunities',
        'won_opportunities',
        'lost_opportunities',
        'customer_tier',
        'segment',
        'last_contacted_at',
        'last_purchase_at',
        'last_activity_at',
        'engagement_score',
        'preferred_contact_method',
        'preferred_language',
        'best_time_to_contact',
        'marketing_consent',
        'do_not_call',
        'do_not_email',
        'gdpr_consent',
        'consent_date',
        'social_profiles',
        'parent_contact_id',
        'is_primary_contact',
        'description',
        'notes',
        'tags',
        'custom_fields',
        'avatar',
        'is_active',
    ];

    protected $casts = [
        'team_ids' => 'array',
        'social_profiles' => 'array',
        'tags' => 'array',
        'custom_fields' => 'array',
        'date_of_birth' => 'date',
        'customer_since' => 'date',
        'consent_date' => 'datetime',
        'last_contacted_at' => 'datetime',
        'last_purchase_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'annual_revenue' => 'decimal:2',
        'customer_lifetime_value' => 'decimal:2',
        'total_purchases' => 'decimal:2',
        'is_primary_contact' => 'boolean',
        'is_active' => 'boolean',
        'marketing_consent' => 'boolean',
        'do_not_call' => 'boolean',
        'do_not_email' => 'boolean',
        'gdpr_consent' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function parentContact(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_contact_id');
    }

    public function childContacts(): HasMany
    {
        return $this->hasMany(self::class, 'parent_contact_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'contact_id');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'related_to');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'related_to');
    }
}


