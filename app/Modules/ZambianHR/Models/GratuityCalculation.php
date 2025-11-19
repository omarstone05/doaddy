<?php

namespace App\Modules\ZambianHR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GratuityCalculation extends Model
{
    protected $table = 'hr_gratuity_calculations';
    
    protected $fillable = [
        'employee_id',
        'organization_id',
        'calculation_date',
        'employment_start_date',
        'employment_end_date',
        'years_of_service',
        'months_of_service',
        'base_salary_used',
        'gratuity_rate',
        'total_gratuity_amount',
        'prorated_amount',
        'status',
        'approved_by',
        'approved_at',
        'paid_at',
        'payment_reference',
        'calculation_formula',
        'calculation_breakdown',
        'deductions_amount',
        'deductions_reason',
        'net_gratuity_amount',
    ];

    protected $casts = [
        'calculation_date' => 'date',
        'employment_start_date' => 'date',
        'employment_end_date' => 'date',
        'years_of_service' => 'decimal:2',
        'base_salary_used' => 'decimal:2',
        'gratuity_rate' => 'decimal:4',
        'total_gratuity_amount' => 'decimal:2',
        'prorated_amount' => 'decimal:2',
        'deductions_amount' => 'decimal:2',
        'net_gratuity_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'calculation_breakdown' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\HR\Models\Employee::class, 'employee_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }
}

