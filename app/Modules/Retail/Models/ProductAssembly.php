<?php

namespace App\Modules\Retail\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAssembly extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $table = 'retail_product_assemblies';

    protected $fillable = [
        'organization_id',
        'assembled_product_id',
        'name',
        'description',
        'quantity_produced',
        'assembly_time',
        'labor_cost',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'quantity_produced' => 'decimal:3',
        'labor_cost' => 'decimal:2',
    ];

    public function assembledProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'assembled_product_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(AssemblyComponent::class, 'assembly_id');
    }

    /**
     * Calculate total cost including components and labor
     */
    public function calculateTotalCost(): float
    {
        $componentCost = $this->components->sum(function ($component) {
            return $component->componentProduct->cost_price * $component->quantity_needed;
        });

        return $componentCost + $this->labor_cost;
    }
}

