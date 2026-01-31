<?php

namespace App\Modules\Accounting\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBalance extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'account_id',
        'balance_date',
        'debit_balance',
        'credit_balance',
        'net_balance',
    ];

    protected function casts(): array
    {
        return [
            'balance_date' => 'date',
            'debit_balance' => 'decimal:2',
            'credit_balance' => 'decimal:2',
            'net_balance' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

