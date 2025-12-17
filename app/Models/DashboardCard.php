<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DashboardCard extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'key',
        'name',
        'component',
        'default_config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function orgInstances(): HasMany
    {
        return $this->hasMany(OrgDashboardCard::class);
    }

    public function userInstances(): HasMany
    {
        return $this->hasMany(UserDashboardCard::class);
    }
}
