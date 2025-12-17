<?php

namespace App\Modules\ZambianHR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grievance extends Model
{
    protected $table = 'hr_grievances';
    
    protected $fillable = [
        'employee_id',
        'organization_id',
        'grievance_number',
        'subject',
        'description',
        'grievance_category',
        'filed_against_employee_id',
        'filed_against_manager_id',
        'witnesses',
        'incident_date',
        'filed_date',
        'status',
        'priority',
        'assigned_to',
        'investigation_start_date',
        'investigation_notes',
        'investigation_findings',
        'resolution_date',
        'resolution_summary',
        'resolution_action_taken',
        'outcome',
        'appeal_filed',
        'appeal_date',
        'appeal_outcome',
        'is_confidential',
        'supporting_documents',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'filed_date' => 'date',
        'investigation_start_date' => 'date',
        'resolution_date' => 'date',
        'appeal_date' => 'date',
        'witnesses' => 'array',
        'supporting_documents' => 'array',
        'appeal_filed' => 'boolean',
        'is_confidential' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\HR\Models\Employee::class, 'employee_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(GrievanceMeeting::class, 'grievance_id');
    }
}

