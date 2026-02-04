<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prospect extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'assigned_to',
        'prospect_code', // Auto-generated in boot() method, but included for manual assignment if needed
        'name',
        'company_name',
        'email',
        'phone',
        'website',
        'source',
        'source_details',
        'stage',
        'stage_order',
        'stage_changed_at',
        'estimated_value',
        'probability',
        'expected_close_date',
        'currency',
        'contact_person',
        'contact_title',
        'contact_email',
        'contact_phone',
        'address',
        'city',
        'state',
        'country',
        'priority',
        'needs',
        'pain_points',
        'budget_status',
        'decision_timeframe',
        'engagement_score',
        'last_contacted_at',
        'last_activity_at',
        'touch_count',
        'converted_to_customer_id',
        'converted_at',
        'lost_reason',
        'notes',
        'tags',
        'custom_fields',
    ];

    protected $casts = [
        'stage_changed_at' => 'date',
        'estimated_value' => 'decimal:2',
        'expected_close_date' => 'date',
        'last_contacted_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'converted_at' => 'datetime',
        'tags' => 'array',
        'custom_fields' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($prospect) {
            if (empty($prospect->prospect_code)) {
                // Pass organization_id to ensure unique codes per organization
                $prospect->prospect_code = self::generateProspectCode($prospect->organization_id);
            }
            
            if ($prospect->probability === null) {
                $prospect->probability = 0;
            }

            // Set currency from organization if not provided
            if (empty($prospect->currency) && $prospect->organization_id) {
                $organization = Organization::find($prospect->organization_id);
                if ($organization && $organization->currency) {
                    $prospect->currency = $organization->currency;
                } else {
                    $prospect->currency = 'ZMW'; // Default fallback
                }
            } elseif (empty($prospect->currency)) {
                $prospect->currency = 'ZMW'; // Default fallback
            }
        });

        static::updating(function ($prospect) {
            if ($prospect->isDirty('stage')) {
                $prospect->stage_changed_at = now();
            }
        });
    }

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function convertedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_to_customer_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotIn('stage', ['won', 'lost']);
    }

    public function scopeInStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    // Accessors
    public function getWeightedValueAttribute(): float
    {
        return $this->estimated_value * ($this->probability / 100);
    }

    public function getIsHotLeadAttribute(): bool
    {
        return $this->engagement_score > 70 && 
               $this->priority === 'high' && 
               $this->stage !== 'lost';
    }

    public function getDaysSinceLastContactAttribute(): ?int
    {
        if (!$this->last_contacted_at) {
            return null;
        }

        return now()->diffInDays($this->last_contacted_at);
    }

    // Methods
    public static function generateProspectCode(?string $organizationId = null): string
    {
        $prefix = 'PRO';
        
        // Filter by organization if provided
        $query = self::query();
        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }
        
        $lastProspect = $query->latest('id')->first();
        
        if ($lastProspect && !empty($lastProspect->prospect_code)) {
            // Extract number from prospect_code (format: PRO000001)
            $codePart = substr($lastProspect->prospect_code, 3);
            if (is_numeric($codePart)) {
                $number = ((int) $codePart) + 1;
            } else {
                $number = 1;
            }
        } else {
            $number = 1;
        }
        
        $code = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
        
        // Retry logic to handle race conditions
        $maxRetries = 10;
        $retries = 0;
        while ($retries < $maxRetries) {
            // Check if code already exists
            $existsQuery = self::query()->where('prospect_code', $code);
            if ($organizationId) {
                $existsQuery->where('organization_id', $organizationId);
            }
            
            if (!$existsQuery->exists()) {
                return $code;
            }
            
            // Code exists, increment and try again
            $number++;
            $code = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
            $retries++;
        }
        
        // Fallback: use timestamp-based code if all retries fail
        return $prefix . str_pad((int) substr(time(), -6), 6, '0', STR_PAD_LEFT);
    }

    public function moveToStage(string $stage): void
    {
        $stageOrder = [
            'lead' => 1,
            'contacted' => 2,
            'qualified' => 3,
            'proposal' => 4,
            'negotiation' => 5,
            'won' => 6,
            'lost' => 0,
        ];

        $this->stage = $stage;
        $this->stage_order = $stageOrder[$stage] ?? 1;
        $this->stage_changed_at = now();
        $this->save();
    }

    public function convertToCustomer(array $customerData = []): Customer
    {
        $customer = Customer::create(array_merge([
            'organization_id' => $this->organization_id,
            'type' => $this->company_name ? 'business' : 'individual',
            'name' => $this->company_name ?: $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'billing_address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'primary_contact_name' => $this->contact_person,
            'primary_contact_email' => $this->contact_email,
            'primary_contact_phone' => $this->contact_phone,
            'payment_terms' => 'net_30',
            'currency' => $this->currency ?? 'ZMW',
            'status' => 'active',
            'notes' => $this->notes,
            'tags' => $this->tags,
        ], $customerData));

        $this->converted_to_customer_id = $customer->id;
        $this->converted_at = now();
        $this->moveToStage('won');

        return $customer;
    }

    public function updateEngagementScore(): void
    {
        $score = 0;

        // Touch frequency (max 30 points)
        if ($this->touch_count > 10) $score += 30;
        elseif ($this->touch_count > 5) $score += 20;
        elseif ($this->touch_count > 0) $score += 10;

        // Recency (max 25 points)
        $daysSinceContact = $this->days_since_last_contact;
        if ($daysSinceContact === null) {
            $score += 0;
        } elseif ($daysSinceContact < 7) $score += 25;
        elseif ($daysSinceContact < 14) $score += 15;
        elseif ($daysSinceContact < 30) $score += 5;

        // Stage progression (max 25 points)
        $score += $this->stage_order * 4;

        // Value potential (max 20 points)
        if ($this->estimated_value > 100000) $score += 20;
        elseif ($this->estimated_value > 50000) $score += 15;
        elseif ($this->estimated_value > 10000) $score += 10;
        elseif ($this->estimated_value > 0) $score += 5;

        $this->engagement_score = min($score, 100);
        $this->save();
    }
}
