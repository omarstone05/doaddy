<?php

namespace App\Models\Print;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'print_material_id',
        'rule_name',
        'markup_type',
        'markup_value',
        'min_area',
        'max_area',
        'is_default',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'markup_value' => 'decimal:2',
        'min_area' => 'decimal:2',
        'max_area' => 'decimal:2',
        'is_default' => 'boolean',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function printMaterial(): BelongsTo
    {
        return $this->belongsTo(PrintMaterial::class);
    }

    // Calculate price per sqm based on cost
    public function calculatePricePerSqm(float $costPerSqm): float
    {
        return match ($this->markup_type) {
            'percentage' => $costPerSqm * (1 + ($this->markup_value / 100)),
            'fixed_amount' => $costPerSqm + $this->markup_value,
            'fixed_price' => $this->markup_value,
            default => $costPerSqm,
        };
    }

    public static function getMarkupTypeOptions(): array
    {
        return [
            'percentage' => 'Percentage Markup',
            'fixed_amount' => 'Fixed Amount Markup',
            'fixed_price' => 'Fixed Price per Sqm',
        ];
    }
}

