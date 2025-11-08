<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'payroll_run_id',
        'team_member_id',
        'basic_salary',
        'allowances',
        'deductions',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'payment_method',
        'payment_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'allowances' => 'array',
            'deductions' => 'array',
            'gross_pay' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function calculatePay(): void
    {
        // Calculate gross pay
        $allowancesTotal = collect($this->allowances ?? [])->sum('amount');
        $this->gross_pay = $this->basic_salary + $allowancesTotal;
        
        // Calculate total deductions
        $this->total_deductions = collect($this->deductions ?? [])->sum('amount');
        
        // Calculate net pay
        $this->net_pay = $this->gross_pay - $this->total_deductions;
        
        $this->save();
    }
}

