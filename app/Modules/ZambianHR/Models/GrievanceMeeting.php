<?php

namespace App\Modules\ZambianHR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrievanceMeeting extends Model
{
    protected $table = 'hr_grievance_meetings';
    
    protected $fillable = [
        'grievance_id',
        'meeting_type',
        'meeting_date',
        'meeting_time',
        'location',
        'attendees',
        'chairperson_id',
        'minutes',
        'decisions_made',
        'action_items',
        'recording_file',
        'meeting_notes_file',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'meeting_time' => 'datetime',
        'attendees' => 'array',
        'action_items' => 'array',
    ];

    public function grievance(): BelongsTo
    {
        return $this->belongsTo(Grievance::class, 'grievance_id');
    }
}

