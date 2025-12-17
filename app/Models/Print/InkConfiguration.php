<?php

namespace App\Models\Print;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InkConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'bottles_per_set',
        'cost_per_set',
        'coverage_area',
        'coverage_multiplier',
        'is_default',
        'notes',
    ];

    protected $casts = [
        'bottles_per_set' => 'integer',
        'cost_per_set' => 'decimal:2',
        'coverage_area' => 'decimal:2',
        'coverage_multiplier' => 'integer',
        'is_default' => 'boolean',
    ];

    protected $appends = ['ink_unit_cost'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(PrintMaterial::class, 'material_ink_mappings');
    }

    // Calculate ink unit cost per sqm
    public function getInkUnitCostAttribute(): float
    {
        return $this->getInkUnitCost();
    }

    public function getInkUnitCost(): float
    {
        $effectiveCoverage = $this->coverage_area * $this->coverage_multiplier;
        return $effectiveCoverage > 0 ? round($this->cost_per_set / $effectiveCoverage, 2) : 0;
    }

    // Set this as the default for the organization
    public function setAsDefault(): void
    {
        // Remove default from other configurations
        self::where('organization_id', $this->organization_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}

