<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'project_id',
        'user_id',
        'organization_id',
        'role',
        'permissions',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'joined_at' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

