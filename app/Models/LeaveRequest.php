<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class LeaveRequest extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'team_member_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'number_of_days',
        'reason',
        'status',
        'approved_by_id',
        'approved_at',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'number_of_days' => 'integer',
            'status' => 'string',
            'approved_at' => 'datetime',
        ];
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function calculateDays(): int
    {
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);
        
        // Calculate business days (excluding weekends)
        $days = 0;
        $current = $start->copy();
        
        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $days++;
            }
            $current->addDay();
        }
        
        return $days;
    }
}

