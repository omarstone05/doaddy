<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTeam extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $table = 'crm_sales_teams';

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'team_lead_id',
        'members',
        'monthly_target',
        'quarterly_target',
        'annual_target',
        'is_active',
    ];

    protected $casts = [
        'members' => 'array',
        'monthly_target' => 'decimal:2',
        'quarterly_target' => 'decimal:2',
        'annual_target' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function teamLead(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'team_lead_id');
    }
}


