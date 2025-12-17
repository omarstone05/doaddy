<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'maximum_days_per_year',
        'can_carry_forward',
        'max_carry_forward_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'maximum_days_per_year' => 'integer',
            'can_carry_forward' => 'boolean',
            'max_carry_forward_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}

