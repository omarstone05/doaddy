<?php

namespace App\Modules\ZambianHR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBeneficiary extends Model
{
    protected $table = 'hr_employee_beneficiaries';
    
    protected $fillable = [
        'employee_id',
        'organization_id',
        'beneficiary_name',
        'relationship',
        'date_of_birth',
        'nrc_number',
        'phone',
        'address',
        'percentage',
        'is_primary',
        'priority_order',
        'eligible_for_funeral_grant',
        'is_active',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'percentage' => 'decimal:2',
        'is_primary' => 'boolean',
        'eligible_for_funeral_grant' => 'boolean',
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
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

