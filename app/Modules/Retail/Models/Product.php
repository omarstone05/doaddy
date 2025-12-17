<?php

namespace App\Modules\Retail\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $table = 'retail_products';

    protected $fillable = [
        'organization_id',
        'location_id',
        'sku',
        'barcode',
        'name',
        'description',
        'category_id',
        'brand',
        'product_type',
        'is_for_sale',
        'is_raw_material',
        'track_stock',
        'current_stock',
        'minimum_stock',
        'maximum_stock',
        'reorder_point',
        'reorder_quantity',
        'cost_price',
        'selling_price',
        'compare_at_price',
        'profit_margin',
        'markup_percentage',
        'tax_rate',
        'is_taxable',
        'unit_of_measure',
        'weight',
        'dimensions',
        'status',
        'is_featured',
        'allows_backorder',
        'expiry_date',
        'batch_number',
        'supplier_id',
        'images',
        'thumbnail',
        'tags',
        'custom_fields',
        'notes',
    ];

    protected $casts = [
        'is_for_sale' => 'boolean',
        'is_raw_material' => 'boolean',
        'track_stock' => 'boolean',
        'is_taxable' => 'boolean',
        'is_featured' => 'boolean',
        'allows_backorder' => 'boolean',
        'current_stock' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
        'maximum_stock' => 'decimal:3',
        'reorder_point' => 'decimal:3',
        'reorder_quantity' => 'decimal:3',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'weight' => 'decimal:3',
        'dimensions' => 'array',
        'images' => 'array',
        'tags' => 'array',
        'custom_fields' => 'array',
        'expiry_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function assemblies(): HasMany
    {
        return $this->hasMany(ProductAssembly::class, 'assembled_product_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'product_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForSale($query)
    {
        return $query->where('is_for_sale', true);
    }

    public function scopeRawMaterials($query)
    {
        return $query->where('is_raw_material', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock', '<=', 'minimum_stock')
            ->where('track_stock', true);
    }

    /**
     * Computed Properties
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->track_stock && $this->current_stock <= $this->minimum_stock;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->track_stock && $this->current_stock <= 0;
    }

    /**
     * Calculate profit margin
     */
    public function calculateProfitMargin(): float
    {
        if ($this->selling_price == 0) {
            return 0;
        }
        return (($this->selling_price - $this->cost_price) / $this->selling_price) * 100;
    }

    /**
     * Calculate markup percentage
     */
    public function calculateMarkup(): float
    {
        if ($this->cost_price == 0) {
            return 0;
        }
        return (($this->selling_price - $this->cost_price) / $this->cost_price) * 100;
    }

    /**
     * Update stock level
     */
    public function updateStock(float $quantity, string $type = 'adjustment'): void
    {
        if (!$this->track_stock) {
            return;
        }

        $previousStock = $this->current_stock;
        $this->current_stock += $quantity;

        if ($this->current_stock < 0 && !$this->allows_backorder) {
            throw new \Exception("Insufficient stock for {$this->name}");
        }

        $this->save();

        // Create stock movement record
        StockMovement::create([
            'organization_id' => $this->organization_id,
            'location_id' => $this->location_id,
            'product_id' => $this->id,
            'movement_type' => $type,
            'quantity' => $quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $this->current_stock,
            'created_by' => auth()->id() ?? $this->organization->owner_id,
        ]);
    }
}

