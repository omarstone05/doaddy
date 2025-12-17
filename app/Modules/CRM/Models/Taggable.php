<?php

namespace App\Modules\CRM\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Taggable extends Model
{
    use HasUuid;

    protected $table = 'crm_taggables';

    protected $fillable = [
        'tag_id',
        'taggable_type',
        'taggable_id',
    ];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }

    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }
}


