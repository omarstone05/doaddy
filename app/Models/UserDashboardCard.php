<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDashboardCard extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dashboardCard(): BelongsTo
    {
        return $this->belongsTo(DashboardCard::class);
    }
}

