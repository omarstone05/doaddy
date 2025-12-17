<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamificationXP extends Model
{
    use HasUuids;

    protected $table = 'gamification_xp';

    protected $fillable = [
        'user_id',
        'organization_id',
        'xp_amount',
        'reason',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
        'xp_amount' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

