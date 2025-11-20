<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'asset_number',
        'asset_tag',
        'category',
        'description',
        'purchase_date',
        'purchase_price',
        'current_value',
        'supplier',
        'purchase_order_number',
        'manufacturer',
        'model',
        'serial_number',
        'location',
        'assigned_to_user_id',
        'assigned_to_department_id',
        'status',
        'condition',
        'warranty_expiry',
        'last_maintenance_date',
        'next_maintenance_date',
        'maintenance_notes',
        'depreciation_method',
        'useful_life_years',
        'salvage_value',
        'accumulated_depreciation',
        'last_depreciation_date',
        'metadata',
        'notes',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'current_value' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
            'purchase_date' => 'date',
            'warranty_expiry' => 'date',
            'last_maintenance_date' => 'date',
            'next_maintenance_date' => 'date',
            'last_depreciation_date' => 'date',
            'metadata' => 'array',
            'attachments' => 'array',
        ];
    }

    /**
     * Get the user this asset is assigned to
     */
    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Get the department this asset is assigned to
     */
    public function assignedToDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'assigned_to_department_id');
    }
}
