<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsAndService extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'description',
        'sku',
        'barcode',
        'cost_price',
        'selling_price',
        'current_stock',
        'minimum_stock',
        'unit',
        'category',
        'is_active',
        'track_stock',
        'purchase_date',
        'purchase_price',
        'depreciation_method',
        'useful_life_years',
        'salvage_value',
        'accumulated_depreciation',
        'last_depreciation_date',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'current_stock' => 'decimal:2',
            'minimum_stock' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
            'is_active' => 'boolean',
            'track_stock' => 'boolean',
            'purchase_date' => 'date',
            'last_depreciation_date' => 'date',
        ];
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'goods_service_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function isLowStock(): bool
    {
        if (!$this->track_stock || $this->minimum_stock === null) {
            return false;
        }
        return $this->current_stock <= $this->minimum_stock;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->isLowStock();
    }
}
