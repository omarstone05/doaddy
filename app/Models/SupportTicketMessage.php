<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupportTicketMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'message',
        'is_internal_note',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_internal_note' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            // Update first_response_at if this is the first agent response
            $ticket = $message->ticket;
            
            if (!$ticket->first_response_at && 
                $message->user && 
                $message->user->isAdmin() && 
                !$message->is_internal_note) {
                $ticket->update(['first_response_at' => now()]);
            }
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFromAdmin(): bool
    {
        return $this->user && $this->user->isAdmin();
    }
}

