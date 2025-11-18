<?php

namespace App\Modules\Consulting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Risk extends Model
{
    use HasUuids;

    protected $table = 'consulting_risks';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'category',
        'probability',
        'impact',
        'risk_score',
        'owner_id',
        'mitigation_plan',
        'status',
        'identified_date',
        'review_date',
        'closed_date',
        'custom_fields',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'identified_date' => 'date',
        'review_date' => 'date',
        'closed_date' => 'date',
        'custom_fields' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['identified', 'monitoring', 'mitigating']);
    }

    public function scopeHighRisk($query)
    {
        return $query->where('risk_score', '>=', 6);
    }
}

