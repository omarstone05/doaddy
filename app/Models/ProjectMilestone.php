<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'project_id',
        'organization_id',
        'name',
        'description',
        'target_date',
        'completed_date',
        'status',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'completed_date' => 'date',
            'order' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

