<?php

namespace App\Modules\Consulting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Vendor extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'consulting_vendors';

    protected $fillable = [
        'project_id',
        'organization_id',
        'name',
        'company_name',
        'email',
        'phone',
        'address',
        'service_type',
        'services_provided',
        'quotes',
        'total_contracted',
        'total_paid',
        'rating',
        'performance_notes',
        'status',
        'contacts',
        'documents',
    ];

    protected $casts = [
        'quotes' => 'array',
        'contacts' => 'array',
        'documents' => 'array',
        'total_contracted' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'rating' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

