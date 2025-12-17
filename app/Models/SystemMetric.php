<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemMetric extends Model
{
    protected $fillable = [
        'date',
        'active_organizations',
        'active_users',
        'new_organizations',
        'new_users',
        'support_tickets_opened',
        'support_tickets_resolved',
        'emails_sent',
        'api_requests',
        'avg_response_time',
        'errors_count',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'metadata' => 'array',
        'avg_response_time' => 'decimal:2',
    ];
}

