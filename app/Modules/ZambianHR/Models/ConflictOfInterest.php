<?php

namespace App\Modules\ZambianHR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConflictOfInterest extends Model
{
    protected $table = 'hr_conflict_of_interest_declarations';
    
    protected $fillable = [
        'employee_id',
        'organization_id',
        'declaration_type',
        'organization_name',
        'position_held',
        'nature_of_interest',
        'start_date',
        'end_date',
        'is_ongoing',
        'monetary_value',
        'ownership_percentage',
        'status',
        'declared_date',
        'reviewed_by',
        'reviewed_at',
        'approval_decision',
        'conditions_of_approval',
        'requires_annual_renewal',
        'last_renewed_date',
        'next_renewal_due',
        'supporting_documents',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'declared_date' => 'date',
        'last_renewed_date' => 'date',
        'next_renewal_due' => 'date',
        'reviewed_at' => 'datetime',
        'is_ongoing' => 'boolean',
        'requires_annual_renewal' => 'boolean',
        'monetary_value' => 'decimal:2',
        'ownership_percentage' => 'decimal:2',
        'supporting_documents' => 'array',
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

