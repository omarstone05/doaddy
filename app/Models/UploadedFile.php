<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UploadedFile extends Model
{
    use HasUuids, BelongsToOrganization;

    protected $fillable = [
        'id',
        'organization_id',
        'user_id',
        'original_name',
        'file_name',
        'file_type',
        'mime_type',
        'file_size',
        'storage_driver',
        'storage_path',
        'metadata',
        'processed_at',
        'processing_result',
    ];

    protected $casts = [
        'metadata' => 'array',
        'processing_result' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get user who uploaded this file
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get human readable file size
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get download URL
     */
    public function getDownloadUrl(): string
    {
        return route('files.download', $this->id);
    }

    /**
     * Get view URL (for Google Drive files)
     */
    public function getViewUrl(): ?string
    {
        if ($this->storage_driver === 'google') {
            return "https://drive.google.com/file/d/{$this->storage_path}/view";
        }
        return null;
    }

    /**
     * Mark file as processed
     */
    public function markProcessed(array $result): void
    {
        $this->update([
            'processed_at' => now(),
            'processing_result' => $result,
        ]);
    }
}

