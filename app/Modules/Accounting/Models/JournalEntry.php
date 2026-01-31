<?php

namespace App\Modules\Accounting\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class JournalEntry extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'entry_number',
        'entry_date',
        'description',
        'reference',
        'status',
        'type',
        'posted_by',
        'posted_at',
        'reversed_by',
        'reversed_at',
        'reversing_entry_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($entry) {
            if (!$entry->entry_number) {
                $entry->entry_number = static::generateEntryNumber($entry->organization_id);
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('line_number');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'posted_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reversed_by');
    }

    public function reversingEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversing_entry_id');
    }

    /**
     * Generate unique journal entry number
     */
    protected static function generateEntryNumber(string $organizationId): string
    {
        $prefix = 'JE';
        $year = date('Y');
        $month = date('m');
        
        $lastEntry = static::where('organization_id', $organizationId)
            ->where('entry_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('entry_number', 'desc')
            ->first();
        
        if ($lastEntry) {
            $lastNumber = (int) substr($lastEntry->entry_number, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
        
        return "{$prefix}-{$year}{$month}-{$nextNumber}";
    }

    /**
     * Check if entry is balanced (debits = credits)
     */
    public function isBalanced(): bool
    {
        $debits = $this->lines()->where('type', 'debit')->sum('amount');
        $credits = $this->lines()->where('type', 'credit')->sum('amount');
        
        return abs($debits - $credits) < 0.01; // Allow for floating point precision
    }

    /**
     * Get total debits
     */
    public function getTotalDebitsAttribute(): float
    {
        return $this->lines()->where('type', 'debit')->sum('amount');
    }

    /**
     * Get total credits
     */
    public function getTotalCreditsAttribute(): float
    {
        return $this->lines()->where('type', 'credit')->sum('amount');
    }
}

