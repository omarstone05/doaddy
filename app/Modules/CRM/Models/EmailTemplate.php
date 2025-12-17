<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $table = 'crm_email_templates';

    protected $fillable = [
        'organization_id',
        'name',
        'category',
        'subject',
        'body_html',
        'body_text',
        'available_variables',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'available_variables' => 'array',
        'is_active' => 'boolean',
    ];
}


