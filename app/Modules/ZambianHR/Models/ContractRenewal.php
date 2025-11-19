<?php

namespace App\Modules\ZambianHR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractRenewal extends Model
{
    protected $table = 'hr_contract_renewals';
    
    protected $fillable = [
        'employee_id',
        'organization_id',
        'current_contract_start',
        'current_contract_end',
        'renewal_status',
        'renewal_offered_date',
        'renewal_deadline',
        'new_contract_start',
        'new_contract_end',
        'new_contract_type',
        'new_salary',
        'new_job_title',
        'changes_summary',
        'employee_response',
        'employee_response_date',
        'employee_comments',
        'renewal_offer_file',
        'new_contract_file',
        'initiated_by',
    ];

    protected $casts = [
        'current_contract_start' => 'date',
        'current_contract_end' => 'date',
        'renewal_offered_date' => 'date',
        'renewal_deadline' => 'date',
        'new_contract_start' => 'date',
        'new_contract_end' => 'date',
        'employee_response_date' => 'date',
        'new_salary' => 'decimal:2',
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

