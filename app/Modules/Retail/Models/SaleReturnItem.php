<?php

namespace App\Modules\Retail\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'sale_return_items';

    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'quantity_returned',
        'refund_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity_returned' => 'decimal:2',
            'refund_amount' => 'decimal:2',
        ];
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}

