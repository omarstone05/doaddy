<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $table = 'crm_opportunities';

    protected $fillable = [
        'organization_id',
        'opportunity_number',
        'name',
        'contact_id',
        'account_id',
        'type',
        'amount',
        'currency',
        'recurring_revenue',
        'recurring_period',
        'stage',
        'pipeline_id',
        'probability',
        'expected_revenue',
        'created_date',
        'expected_close_date',
        'actual_close_date',
        'last_stage_change_date',
        'days_in_stage',
        'owner_id',
        'sales_team_id',
        'territory',
        'competitors',
        'our_position',
        'lead_source',
        'campaign_id',
        'referrer_contact_id',
        'is_won',
        'is_lost',
        'loss_reason',
        'loss_reason_details',
        'next_step',
        'next_step_date',
        'products_interested',
        'activities_count',
        'emails_count',
        'calls_count',
        'meetings_count',
        'description',
        'notes',
        'tags',
        'custom_fields',
        'automated_reminders_enabled',
        'stale_alert_sent',
    ];

    protected $casts = [
        'competitors' => 'array',
        'products_interested' => 'array',
        'tags' => 'array',
        'custom_fields' => 'array',
        'created_date' => 'date',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
        'last_stage_change_date' => 'date',
        'next_step_date' => 'date',
        'amount' => 'decimal:2',
        'expected_revenue' => 'decimal:2',
        'recurring_revenue' => 'boolean',
        'is_won' => 'boolean',
        'is_lost' => 'boolean',
        'automated_reminders_enabled' => 'boolean',
        'stale_alert_sent' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($opportunity) {
            if (!$opportunity->opportunity_number) {
                $opportunity->opportunity_number = static::generateOpportunityNumber($opportunity->organization_id);
            }
            if (!$opportunity->created_date) {
                $opportunity->created_date = now()->toDateString();
            }
            if (!$opportunity->expected_revenue) {
                $opportunity->expected_revenue = $opportunity->amount * ($opportunity->probability / 100);
            }
        });
    }

    public static function generateOpportunityNumber(string $organizationId): string
    {
        $prefix = 'OPP-' . date('Y') . '-';
        $lastOpp = static::where('organization_id', $organizationId)
            ->where('opportunity_number', 'like', $prefix . '%')
            ->orderBy('opportunity_number', 'desc')
            ->first();

        if ($lastOpp) {
            $lastNumber = (int) str_replace($prefix, '', $lastOpp->opportunity_number);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class, 'pipeline_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(OpportunityProduct::class, 'opportunity_id');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'related_to');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'opportunity_id');
    }
}


