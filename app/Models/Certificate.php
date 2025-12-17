<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Carbon\Carbon;

class Certificate extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'certificate_number',
        'category',
        'issuing_authority',
        'issue_date',
        'expiry_date',
        'status',
        'notes',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && Carbon::parse($this->expiry_date)->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && Carbon::parse($this->expiry_date)->isBetween(
            Carbon::now(),
            Carbon::now()->addDays($days)
        );
    }

    protected static function booted(): void
    {
        static::saving(function ($certificate) {
            // Auto-update status based on expiry date
            if ($certificate->expiry_date) {
                $expiryDate = Carbon::parse($certificate->expiry_date);
                if ($expiryDate->isPast()) {
                    $certificate->status = 'expired';
                } elseif ($certificate->status === 'expired') {
                    $certificate->status = 'active';
                }
            }
        });
    }
}

