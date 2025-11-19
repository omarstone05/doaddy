<?php

namespace App\Modules\Retail\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RegisterSession extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $table = 'register_sessions';

    protected $fillable = [
        'organization_id',
        'session_number',
        'money_account_id',
        'department_id',
        'opened_by_id',
        'opening_date',
        'opening_float',
        'closed_by_id',
        'closing_date',
        'closing_count',
        'expected_cash',
        'variance',
        'total_sales',
        'cash_sales',
        'mobile_money_sales',
        'card_sales',
        'credit_sales',
        'cash_paid_out',
        'cash_received',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_date' => 'datetime',
            'closing_date' => 'datetime',
            'opening_float' => 'decimal:2',
            'closing_count' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'variance' => 'decimal:2',
            'total_sales' => 'decimal:2',
            'cash_sales' => 'decimal:2',
            'mobile_money_sales' => 'decimal:2',
            'card_sales' => 'decimal:2',
            'credit_sales' => 'decimal:2',
            'cash_paid_out' => 'decimal:2',
            'cash_received' => 'decimal:2',
        ];
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TeamMember::class, 'opened_by_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TeamMember::class, 'closed_by_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function moneyAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\MoneyAccount::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    protected static function booted(): void
    {
        static::creating(function ($session) {
            if (empty($session->session_number)) {
                $session->session_number = static::generateSessionNumber($session->organization_id);
            }
        });
    }

    protected static function generateSessionNumber($organizationId): string
    {
        $date = now()->format('Ymd');
        $prefix = "REG-{$date}-";
        
        $lastSession = static::where('organization_id', $organizationId)
            ->where('session_number', 'like', $prefix . '%')
            ->orderBy('session_number', 'desc')
            ->first();

        if ($lastSession) {
            $lastNumber = (int) str_replace($prefix, '', $lastSession->session_number);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}

