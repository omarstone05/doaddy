<?php

namespace App\Models\Print;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrintMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'material_type',
        'roll_width',
        'roll_length',
        'material_cost',
        'off_cut_cost',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'roll_width' => 'decimal:2',
        'roll_length' => 'decimal:2',
        'material_cost' => 'decimal:2',
        'off_cut_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['total_area', 'material_unit_cost'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function inkConfigurations(): BelongsToMany
    {
        return $this->belongsToMany(InkConfiguration::class, 'material_ink_mappings');
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class);
    }

    // Calculated attributes
    public function getTotalAreaAttribute(): float
    {
        return $this->roll_width * $this->roll_length;
    }

    public function getMaterialUnitCostAttribute(): float
    {
        $totalArea = $this->total_area;
        return $totalArea > 0 ? round($this->material_cost / $totalArea, 2) : 0;
    }

    // Calculate costs for a given area
    public function calculateCost(float $area, ?InkConfiguration $inkConfig = null): array
    {
        $inkConfig = $inkConfig ?? $this->inkConfigurations()->where('is_default', true)->first() 
            ?? $this->inkConfigurations()->first();

        $materialUnitCost = $this->material_unit_cost;
        $inkUnitCost = $inkConfig ? $inkConfig->getInkUnitCost() : 0;
        $offCutCost = $this->off_cut_cost;
        
        $totalUnitCost = $materialUnitCost + $inkUnitCost + $offCutCost;
        $totalCost = $totalUnitCost * $area;

        return [
            'material_unit_cost' => $materialUnitCost,
            'ink_unit_cost' => $inkUnitCost,
            'off_cut_cost' => $offCutCost,
            'total_unit_cost' => round($totalUnitCost, 2),
            'total_cost' => round($totalCost, 2),
        ];
    }

    // Get applicable pricing rule for given area
    public function getApplicablePricingRule(float $area): ?PricingRule
    {
        return PricingRule::where('organization_id', $this->organization_id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('print_material_id', $this->id)
                    ->orWhereNull('print_material_id');
            })
            ->where(function ($query) use ($area) {
                $query->whereNull('min_area')
                    ->orWhere('min_area', '<=', $area);
            })
            ->where(function ($query) use ($area) {
                $query->whereNull('max_area')
                    ->orWhere('max_area', '>=', $area);
            })
            ->orderByDesc('priority')
            ->orderByDesc('print_material_id') // Material-specific rules take precedence
            ->first();
    }

    public static function getTypeOptions(): array
    {
        return [
            'vinyl' => 'Vinyl',
            'banner' => 'Banner',
            'banner_flex' => 'Banner Flex',
            'contra_vision' => 'Contra Vision',
            'clear_vinyl' => 'Clear Vinyl',
            'custom' => 'Custom',
        ];
    }
}

