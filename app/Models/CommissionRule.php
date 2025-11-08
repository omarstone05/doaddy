<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionRule extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'rule_type',
        'rate',
        'fixed_amount',
        'tiers',
        'applicable_to',
        'team_member_id',
        'department_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'fixed_amount' => 'decimal:2',
            'tiers' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(CommissionEarning::class);
    }

    public function calculateCommission($saleAmount): float
    {
        if ($this->rule_type === 'percentage') {
            return ($saleAmount * $this->rate) / 100;
        } elseif ($this->rule_type === 'fixed') {
            return $this->fixed_amount;
        } elseif ($this->rule_type === 'tiered') {
            return $this->calculateTieredCommission($saleAmount);
        }
        return 0;
    }

    private function calculateTieredCommission($saleAmount): float
    {
        $commission = 0;
        $remaining = $saleAmount;

        foreach ($this->tiers ?? [] as $tier) {
            if ($remaining <= 0) break;

            $tierMin = $tier['min'] ?? 0;
            $tierMax = $tier['max'] ?? PHP_INT_MAX;
            $tierRate = $tier['rate'] ?? 0;

            if ($saleAmount > $tierMin) {
                $applicableAmount = min($remaining, $tierMax - $tierMin);
                $commission += ($applicableAmount * $tierRate) / 100;
                $remaining -= $applicableAmount;
            }
        }

        return $commission;
    }
}

