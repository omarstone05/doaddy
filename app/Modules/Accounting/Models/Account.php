<?php

namespace App\Modules\Accounting\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'account_type_id',
        'parent_account_id',
        'code',
        'name',
        'description',
        'normal_balance',
        'opening_balance',
        'current_balance',
        'is_sub_account',
        'is_system_account',
        'allows_postings',
        'level',
        'sort_order',
        'metadata',
        'is_active',
        'account_type', // Enum: asset, liability, equity, income, expense, other_income, cost_of_goods_sold
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_sub_account' => 'boolean',
            'is_system_account' => 'boolean',
            'allows_postings' => 'boolean',
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-populate account_type from account_type_id relationship
        static::saving(function ($account) {
            if ($account->account_type_id && !$account->account_type) {
                $accountType = AccountType::find($account->account_type_id);
                if ($accountType) {
                    $account->account_type = match($accountType->category) {
                        'asset' => 'asset',
                        'liability' => 'liability',
                        'equity' => 'equity',
                        'revenue' => 'income',
                        'expense' => 'expense',
                        default => null,
                    };
                }
            }
        });
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function parentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_account_id');
    }

    public function subAccounts(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_account_id')->orderBy('sort_order');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Get full account code path (e.g., "1000.1100.1110")
     */
    public function getFullCodeAttribute(): string
    {
        $codes = [$this->code];
        $parent = $this->parentAccount;
        
        while ($parent) {
            array_unshift($codes, $parent->code);
            $parent = $parent->parentAccount;
        }
        
        return implode('.', $codes);
    }

    /**
     * Get full account name path
     */
    public function getFullNameAttribute(): string
    {
        $names = [$this->name];
        $parent = $this->parentAccount;
        
        while ($parent) {
            array_unshift($names, $parent->name);
            $parent = $parent->parentAccount;
        }
        
        return implode(' > ', $names);
    }

    /**
     * Calculate current balance from journal entries
     */
    public function calculateBalance(\Carbon\Carbon $asOf = null): float
    {
        $query = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($q) {
                $q->where('status', 'posted');
            });

        if ($asOf) {
            $query->whereHas('journalEntry', function ($q) use ($asOf) {
                $q->where('entry_date', '<=', $asOf);
            });
        }

        $debits = (clone $query)->where('type', 'debit')->sum('amount');
        $credits = (clone $query)->where('type', 'credit')->sum('amount');

        $balance = $debits - $credits;

        // Adjust based on normal balance
        if ($this->normal_balance === 'credit') {
            $balance = $credits - $debits;
        }

        return $this->opening_balance + $balance;
    }

    /**
     * Get current balance from transactions
     */
    public function getBalance(): float
    {
        return $this->calculateBalance();
    }

    /**
     * Check if account has any transactions
     */
    public function hasTransactions(): bool
    {
        return $this->journalEntryLines()
            ->whereHas('journalEntry', function ($q) {
                $q->where('status', 'posted');
            })
            ->exists();
    }

    /**
     * Check if account can be deleted
     */
    public function canDelete(): bool
    {
        // System accounts cannot be deleted
        if ($this->is_system_account) {
            return false;
        }

        // Accounts with transactions cannot be deleted
        if ($this->hasTransactions()) {
            return false;
        }

        // Accounts with non-zero balance cannot be deleted
        if (abs($this->current_balance) > 0.01) {
            return false;
        }

        // Accounts with sub-accounts cannot be deleted
        if ($this->subAccounts()->count() > 0) {
            return false;
        }

        return true;
    }

    /**
     * Scope: Active accounts only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by account type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Get last transaction date
     */
    public function getLastTransactionDate(): ?\Carbon\Carbon
    {
        $lastEntry = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($q) {
                $q->where('status', 'posted');
            })
            ->with('journalEntry')
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastEntry?->journalEntry?->entry_date;
    }

    protected static function newFactory()
    {
        return \Database\Factories\Modules\Accounting\AccountFactory::new();
    }
}
