<?php

namespace App\Modules\ZambianHR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Termination extends Model
{
    protected $table = 'hr_terminations';
    
    protected $fillable = [
        'employee_id',
        'organization_id',
        'termination_type',
        'termination_date',
        'last_working_day',
        'notice_required_days',
        'notice_served_days',
        'notice_payment_in_lieu',
        'reason_category',
        'reason_details',
        'severance_type',
        'severance_calculation_basis',
        'severance_amount',
        'medical_discharge_months_per_year',
        'medical_discharge_total',
        'redundancy_months_per_year',
        'redundancy_total',
        'gratuity_amount',
        'gratuity_prorated',
        'leave_days_outstanding',
        'leave_payout_amount',
        'other_amounts_due',
        'other_amounts_description',
        'amounts_to_recover',
        'recovery_reason',
        'total_gross_amount',
        'total_deductions',
        'net_settlement_amount',
        'settlement_paid',
        'settlement_paid_date',
        'payment_method',
        'payment_reference',
        'exit_interview_completed',
        'exit_interview_date',
        'exit_interview_notes',
        'clearance_form_completed',
        'all_property_returned',
        'property_return_checklist',
        'eligible_for_rehire',
        'rehire_notes',
        'initiated_by',
        'approved_by',
        'approved_at',
        'termination_letter_file',
        'settlement_letter_file',
        'supporting_documents',
    ];

    protected $casts = [
        'termination_date' => 'date',
        'last_working_day' => 'date',
        'settlement_paid_date' => 'date',
        'exit_interview_date' => 'date',
        'approved_at' => 'datetime',
        'notice_payment_in_lieu' => 'decimal:2',
        'severance_amount' => 'decimal:2',
        'medical_discharge_months_per_year' => 'decimal:2',
        'medical_discharge_total' => 'decimal:2',
        'redundancy_months_per_year' => 'decimal:2',
        'redundancy_total' => 'decimal:2',
        'gratuity_amount' => 'decimal:2',
        'leave_days_outstanding' => 'decimal:2',
        'leave_payout_amount' => 'decimal:2',
        'other_amounts_due' => 'decimal:2',
        'amounts_to_recover' => 'decimal:2',
        'total_gross_amount' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_settlement_amount' => 'decimal:2',
        'gratuity_prorated' => 'boolean',
        'settlement_paid' => 'boolean',
        'exit_interview_completed' => 'boolean',
        'clearance_form_completed' => 'boolean',
        'all_property_returned' => 'boolean',
        'eligible_for_rehire' => 'boolean',
        'property_return_checklist' => 'array',
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

