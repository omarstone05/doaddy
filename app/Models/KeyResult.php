<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KeyResult extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'okr_id',
        'title',
        'description',
        'type',
        'target_value',
        'current_value',
        'unit',
        'progress_percentage',
        'status',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'current_value' => 'decimal:2',
            'progress_percentage' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function okr(): BelongsTo
    {
        return $this->belongsTo(OKR::class, 'okr_id');
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(KeyResultCheckIn::class, 'key_result_id')->orderBy('created_at', 'desc');
    }

    public function updateProgress(): void
    {
        if ($this->target_value == 0) {
            $this->progress_percentage = 0;
        } else {
            $this->progress_percentage = (int) round(($this->current_value / $this->target_value) * 100);
        }

        // Update status based on progress
        if ($this->progress_percentage >= 100) {
            $this->status = 'completed';
        } elseif ($this->progress_percentage > 0) {
            $this->status = 'in_progress';
        } else {
            $this->status = 'not_started';
        }

        $this->save();

        // Update parent OKR progress
        if ($this->okr) {
            $this->okr->updateProgress();
        }
    }
}

