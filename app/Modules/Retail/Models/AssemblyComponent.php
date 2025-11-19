<?php

namespace App\Modules\Retail\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssemblyComponent extends Model
{
    use HasUuid;

    protected $table = 'retail_assembly_components';

    protected $fillable = [
        'assembly_id',
        'component_product_id',
        'quantity_needed',
        'unit_of_measure',
        'notes',
    ];

    protected $casts = [
        'quantity_needed' => 'decimal:3',
    ];

    public function assembly(): BelongsTo
    {
        return $this->belongsTo(ProductAssembly::class, 'assembly_id');
    }

    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }
}

