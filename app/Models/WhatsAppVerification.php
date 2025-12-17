<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class WhatsAppVerification extends Model
{
    protected $table = 'whatsapp_verifications';

    protected $fillable = [
        'phone_number',
        'code',
        'user_id',
        'expires_at',
        'verified',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified' => 'boolean',
    ];

    /**
     * Generate a 6-digit verification code
     */
    public static function generateCode(): string
    {
        return str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new verification code
     * Invalidates any existing unverified codes for the phone number
     */
    public static function createVerification(string $phoneNumber, ?string $userId = null): self
    {
        // Normalize phone number (remove non-numeric, ensure country code)
        $normalizedPhone = self::normalizePhoneNumber($phoneNumber);
        
        // Invalidate any existing unverified codes for this phone number
        self::where('phone_number', $normalizedPhone)
            ->where('verified', false)
            ->where('expires_at', '>', now())
            ->update(['verified' => true]);

        return self::create([
            'phone_number' => $normalizedPhone,
            'code' => self::generateCode(),
            'user_id' => $userId,
            'expires_at' => now()->addMinutes(10),
            'verified' => false,
        ]);
    }

    /**
     * Mark verification as verified
     */
    public function markAsVerified(): void
    {
        $this->update(['verified' => true]);
    }

    /**
     * Check if verification code is valid
     */
    public function isValid(): bool
    {
        return !$this->verified && $this->expires_at->isFuture();
    }

    /**
     * Get the user that owns the verification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Normalize phone number for storage (numeric only with country code)
     */
    protected static function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If it doesn't start with a country code, assume Zambia (+260)
        if (!preg_match('/^260/', $phoneNumber)) {
            $phoneNumber = ltrim($phoneNumber, '0');
            $phoneNumber = '260' . $phoneNumber;
        }
        
        return $phoneNumber;
    }
}
