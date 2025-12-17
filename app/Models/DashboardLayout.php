<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardLayout extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'layout',
    ];

    protected $casts = [
        'layout' => 'array',
    ];

    /**
     * Get the user that owns the layout
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all card IDs in layout
     */
    public function getCardIds(): array
    {
        $cardIds = [];
        
        foreach ($this->layout['rows'] ?? [] as $row) {
            foreach ($row['cards'] ?? [] as $card) {
                $cardIds[] = $card['id'] ?? '';
            }
        }

        return $cardIds;
    }

    /**
     * Check if layout contains card
     */
    public function hasCard(string $cardId): bool
    {
        return in_array($cardId, $this->getCardIds());
    }

    /**
     * Get pinned cards
     */
    public function getPinnedCards(): array
    {
        $pinned = [];
        
        foreach ($this->layout['rows'] ?? [] as $row) {
            foreach ($row['cards'] ?? [] as $card) {
                if ($card['pinned'] ?? false) {
                    $pinned[] = $card['id'] ?? '';
                }
            }
        }

        return $pinned;
    }
}

