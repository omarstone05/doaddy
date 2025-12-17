<?php

namespace App\Modules\CRM\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineStage extends Model
{
    use HasUuid;

    protected $table = 'crm_pipeline_stages';

    protected $fillable = [
        'pipeline_id',
        'stage_name',
        'description',
        'stage_order',
        'probability',
        'is_closed_won',
        'is_closed_lost',
        'stage_type',
        'days_until_stale',
        'required_fields',
        'color',
    ];

    protected $casts = [
        'required_fields' => 'array',
        'is_closed_won' => 'boolean',
        'is_closed_lost' => 'boolean',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class, 'pipeline_id');
    }
}


