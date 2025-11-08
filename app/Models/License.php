<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Carbon\Carbon;

class License extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'license_number',
        'name',
        'description',
        'category',
        'issuing_authority',
        'issue_date',
        'expiry_date',
        'status',
        'is_renewable',
        'renewal_date',
        'notes',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'renewal_date' => 'date',
            'is_renewable' => 'boolean',
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
        static::saving(function ($license) {
            // Auto-update status based on expiry date
            if ($license->expiry_date) {
                $expiryDate = Carbon::parse($license->expiry_date);
                if ($expiryDate->isPast()) {
                    $license->status = 'expired';
                } elseif ($license->renewal_date && Carbon::parse($license->renewal_date)->isPast()) {
                    $license->status = 'pending_renewal';
                } elseif ($license->status === 'expired' || $license->status === 'pending_renewal') {
                    $license->status = 'active';
                }
            }
        });
    }
}

