<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowExecution extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $table = 'crm_workflow_executions';

    protected $fillable = [
        'workflow_id',
        'organization_id',
        'record_type',
        'record_id',
        'status',
        'error_message',
        'actions_performed',
        'started_at',
        'completed_at',
        'execution_time_ms',
    ];

    protected $casts = [
        'actions_performed' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }
}


