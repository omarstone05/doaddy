<?php

namespace App\Modules\ZambianHR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuneralGrant extends Model
{
    protected $table = 'hr_funeral_grants';
    
    protected $fillable = [
        'employee_id',
        'organization_id',
        'deceased_person',
        'deceased_name',
        'relationship_to_employee',
        'date_of_death',
        'death_certificate_file',
        'grant_amount',
        'currency',
        'calculation_basis',
        'payment_method',
        'paid_to_beneficiary_id',
        'paid_to_name',
        'paid_at',
        'payment_reference',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'supporting_documents',
        'notes',
    ];

    protected $casts = [
        'date_of_death' => 'date',
        'grant_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
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

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(EmployeeBeneficiary::class, 'paid_to_beneficiary_id');
    }
}

