<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeyResultCheckIn extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'key_result_id',
        'current_value',
        'progress_percentage',
        'notes',
        'confidence',
        'checked_in_by_id',
    ];

    protected function casts(): array
    {
        return [
            'current_value' => 'decimal:2',
            'progress_percentage' => 'integer',
        ];
    }

    public function keyResult(): BelongsTo
    {
        return $this->belongsTo(KeyResult::class, 'key_result_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by_id');
    }
}

