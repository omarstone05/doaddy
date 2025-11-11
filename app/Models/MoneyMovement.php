<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MoneyMovement extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'flow_type',
        'amount',
        'currency',
        'transaction_date',
        'from_account_id',
        'to_account_id',
        'description',
        'category',
        'related_type',
        'related_id',
        'status',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'status' => 'string',
        ];
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(MoneyAccount::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(MoneyAccount::class, 'to_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(\App\Models\Attachment::class, 'attachable');
    }

    protected static function booted(): void
    {
        static::created(function ($movement) {
            // Update account balances
            if ($movement->flow_type === 'income' && $movement->to_account_id) {
                $account = MoneyAccount::find($movement->to_account_id);
                if ($account) {
                    $account->increment('current_balance', $movement->amount);
                }
            } elseif ($movement->flow_type === 'expense' && $movement->from_account_id) {
                $account = MoneyAccount::find($movement->from_account_id);
                if ($account) {
                    $account->decrement('current_balance', $movement->amount);
                }
            } elseif ($movement->flow_type === 'transfer') {
                if ($movement->from_account_id) {
                    $fromAccount = MoneyAccount::find($movement->from_account_id);
                    if ($fromAccount) {
                        $fromAccount->decrement('current_balance', $movement->amount);
                    }
                }
                if ($movement->to_account_id) {
                    $toAccount = MoneyAccount::find($movement->to_account_id);
                    if ($toAccount) {
                        $toAccount->increment('current_balance', $movement->amount);
                    }
                }
            }
        });
    }
}
