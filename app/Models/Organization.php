<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Organization extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'slug',
        'business_type',
        'industry',
        'tone_preference',
        'currency',
        'timezone',
        'logo',
        'settings',
        'status',
        'trial_ends_at',
        'suspended_at',
        'suspension_reason',
        'billing_plan',
        'mrr',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'mrr' => 'decimal:2',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }
}

