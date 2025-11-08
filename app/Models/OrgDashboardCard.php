<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgDashboardCard extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'dashboard_card_id',
        'config',
        'display_order',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'display_order' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    public function dashboardCard(): BelongsTo
    {
        return $this->belongsTo(DashboardCard::class);
    }
}
