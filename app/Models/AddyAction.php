<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddyAction extends Model
{
    use HasFactory;
    protected $fillable = [
        'organization_id',
        'user_id',
        'chat_message_id',
        'action_type',
        'category',
        'status',
        'parameters',
        'preview_data',
        'result',
        'confirmed_at',
        'executed_at',
        'error_message',
        'was_successful',
        'user_rating',
    ];

    protected $casts = [
        'parameters' => 'array',
        'preview_data' => 'array',
        'result' => 'array',
        'confirmed_at' => 'datetime',
        'executed_at' => 'datetime',
        'was_successful' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chatMessage(): BelongsTo
    {
        return $this->belongsTo(AddyChatMessage::class, 'chat_message_id');
    }

    /**
     * Mark as confirmed
     */
    public function confirm(): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Mark as executed
     */
    public function markExecuted(array $result, bool $successful = true): void
    {
        $this->update([
            'status' => $successful ? 'executed' : 'failed',
            'executed_at' => now(),
            'result' => $result,
            'was_successful' => $successful,
        ]);
    }

    /**
     * Mark as failed
     */
    public function fail(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'was_successful' => false,
        ]);
    }

    /**
     * Cancel action
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}

