<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'sale_id',
        'goods_service_id',
        'product_name',
        'sku',
        'barcode',
        'quantity',
        'unit_price',
        'total',
        'cost_price',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'display_order' => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function goodsService(): BelongsTo
    {
        return $this->belongsTo(GoodsAndService::class, 'goods_service_id');
    }
}
