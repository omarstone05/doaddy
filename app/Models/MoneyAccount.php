<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MoneyAccount extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'account_number',
        'bank_name',
        'currency',
        'opening_balance',
        'current_balance',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function incomingMovements(): HasMany
    {
        return $this->hasMany(MoneyMovement::class, 'to_account_id');
    }

    public function outgoingMovements(): HasMany
    {
        return $this->hasMany(MoneyMovement::class, 'from_account_id');
    }

    public function getBalanceAttribute(): float
    {
        return $this->current_balance;
    }
}
