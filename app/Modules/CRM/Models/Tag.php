<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    use HasUuid, BelongsToOrganization;

    protected $table = 'crm_tags';

    protected $fillable = [
        'organization_id',
        'name',
        'color',
        'tag_category',
        'usage_count',
    ];

    public function taggables(): HasMany
    {
        return $this->hasMany(Taggable::class, 'tag_id');
    }
}


