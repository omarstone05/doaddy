<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Territory extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $table = 'crm_territories';

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'countries',
        'provinces',
        'cities',
        'assigned_to',
        'sales_team_id',
        'is_active',
    ];

    protected $casts = [
        'countries' => 'array',
        'provinces' => 'array',
        'cities' => 'array',
        'is_active' => 'boolean',
    ];

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function salesTeam(): BelongsTo
    {
        return $this->belongsTo(SalesTeam::class, 'sales_team_id');
    }
}


