<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetLine extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'category',
        'amount',
        'period', // monthly, quarterly, yearly
        'start_date',
        'end_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function getSpentAttribute(): float
    {
        return MoneyMovement::where('organization_id', $this->organization_id)
            ->where('category', $this->category)
            ->where('flow_type', 'expense')
            ->where('transaction_date', '>=', $this->start_date)
            ->where('transaction_date', '<=', $this->end_date)
            ->where('status', 'approved')
            ->sum('amount');
    }

    public function getRemainingAttribute(): float
    {
        return $this->amount - $this->spent;
    }

    public function getPercentageSpentAttribute(): float
    {
        if ($this->amount == 0) {
            return 0;
        }
        return ($this->spent / $this->amount) * 100;
    }
}
