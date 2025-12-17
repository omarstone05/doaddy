<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $table = 'crm_workflows';

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'workflow_type',
        'trigger_type',
        'trigger_object',
        'trigger_conditions',
        'actions',
        'is_active',
        'execution_order',
        'run_once',
        'last_executed_at',
        'execution_count',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'actions' => 'array',
        'last_executed_at' => 'datetime',
        'is_active' => 'boolean',
        'run_once' => 'boolean',
    ];

    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class, 'workflow_id');
    }
}


