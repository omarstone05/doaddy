<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DocumentProcessingJob extends Model
{
    use HasUuids, BelongsToOrganization;

    protected $fillable = [
        'id',
        'organization_id',
        'user_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'status',
        'status_message',
        'started_at',
        'completed_at',
        'metadata',
        'result',
        'error',
    ];

    protected $casts = [
        'metadata' => 'array',
        'result' => 'array',
        'error' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get user who created this job
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if job is complete
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if job failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if job is processing
     */
    public function isProcessing(): bool
    {
        return !in_array($this->status, ['completed', 'failed', 'pending']);
    }

    /**
     * Get processing time in seconds
     */
    public function getProcessingTimeAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * Get progress percentage
     */
    public function getProgressAttribute(): int
    {
        $stages = [
            'pending' => 0,
            'extracting' => 20,
            'analyzing' => 40,
            'validating' => 60,
            'fixing' => 70,
            'analyzing_confidence' => 80,
            'importing' => 90,
            'completed' => 100,
            'failed' => 0,
        ];

        return $stages[$this->status] ?? 0;
    }
}

