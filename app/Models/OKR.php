<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OKR extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $table = 'okrs';

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'quarter',
        'status',
        'owner_id',
        'start_date',
        'end_date',
        'progress_percentage',
        'notes',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'progress_percentage' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function keyResults(): HasMany
    {
        return $this->hasMany(KeyResult::class, 'okr_id')->orderBy('display_order');
    }

    public function updateProgress(): void
    {
        $keyResults = $this->keyResults;
        if ($keyResults->isEmpty()) {
            $this->progress_percentage = 0;
        } else {
            $totalProgress = $keyResults->sum('progress_percentage');
            $this->progress_percentage = (int) round($totalProgress / $keyResults->count());
        }
        $this->save();
    }
}

