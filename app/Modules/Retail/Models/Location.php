<?php

namespace App\Modules\Retail\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $table = 'retail_locations';

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'type',
        'address',
        'city',
        'phone',
        'is_active',
        'is_primary',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'settings' => 'array',
    ];
}

