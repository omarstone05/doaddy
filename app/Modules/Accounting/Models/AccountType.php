<?php

namespace App\Modules\Accounting\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AccountType extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'category',
        'normal_balance',
        'report_category',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    protected static function booted(): void
    {
        static::creating(function ($type) {
            if (empty($type->code)) {
                $type->code = 'AT-' . strtoupper(Str::random(5));
            }
            if (empty($type->normal_balance)) {
                $type->normal_balance = 'debit';
            }
        });
    }

    protected static function newFactory()
    {
        return \Database\Factories\Modules\Accounting\AccountTypeFactory::new();
    }

    /**
     * Get default account types for an organization
     */
    public static function getDefaults(): array
    {
        return [
            [
                'name' => 'Assets',
                'code' => 'ASSET',
                'category' => 'asset',
                'normal_balance' => 'debit',
                'description' => 'Resources owned by the business',
                'sort_order' => 1,
            ],
            [
                'name' => 'Liabilities',
                'code' => 'LIAB',
                'category' => 'liability',
                'normal_balance' => 'credit',
                'description' => 'Obligations owed by the business',
                'sort_order' => 2,
            ],
            [
                'name' => 'Equity',
                'code' => 'EQUITY',
                'category' => 'equity',
                'normal_balance' => 'credit',
                'description' => 'Owner\'s interest in the business',
                'sort_order' => 3,
            ],
            [
                'name' => 'Revenue',
                'code' => 'REV',
                'category' => 'revenue',
                'normal_balance' => 'credit',
                'description' => 'Income generated from business operations',
                'sort_order' => 4,
            ],
            [
                'name' => 'Expenses',
                'code' => 'EXP',
                'category' => 'expense',
                'normal_balance' => 'debit',
                'description' => 'Costs incurred in business operations',
                'sort_order' => 5,
            ],
        ];
    }
}
