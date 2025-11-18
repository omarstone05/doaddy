<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\DashboardLayout;

/**
 * Dashboard Layout Manager
 * 
 * Manages user's personalized dashboard layout
 * Stores which cards are visible, their positions, and sizes
 */
class DashboardLayoutManager
{
    /**
     * Get user's current dashboard layout
     */
    public function getUserLayout(User $user): array
    {
        $organizationId = session('current_organization_id') 
            ?? ($user->attributes['organization_id'] ?? null)
            ?? $user->organizations()->first()?->id;
        
        if (!$organizationId) {
            return $this->generateRecommendedLayout($user);
        }
        
        $saved = DashboardLayout::where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->first();

        if ($saved) {
            return is_array($saved->layout) ? $saved->layout : json_decode($saved->layout, true);
        }

        // No saved layout - generate AI-recommended layout
        return $this->generateRecommendedLayout($user);
    }

    /**
     * Generate AI-recommended layout based on user's business
     */
    public function generateRecommendedLayout(User $user): array
    {
        $recommendedCards = CardRegistry::getRecommendedCards($user, 12);

        // Build layout grid
        $layout = [
            'rows' => []
        ];

        // Row 1: Top metric cards (4 small cards)
        $row1Cards = array_slice(
            array_filter($recommendedCards, fn($c) => ($c['category'] ?? '') === 'metric'),
            0,
            4
        );

        if (count($row1Cards) > 0) {
            $layout['rows'][] = [
                'id' => 'row-1',
                'type' => 'metrics',
                'cards' => array_map(fn($card, $index) => [
                    'id' => $card['id'],
                    'position' => $index,
                    'size' => $card['size'] ?? 'small',
                    'pinned' => false,
                ], $row1Cards, array_keys($row1Cards)),
            ];
        }

        // Row 2: Main content (1 large chart + 1 medium card)
        $chartCards = array_filter($recommendedCards, fn($c) => ($c['category'] ?? '') === 'chart');
        
        if (count($chartCards) > 0) {
            $mainChart = array_values($chartCards)[0];
            $sideCard = array_values(array_filter($recommendedCards, fn($c) => 
                ($c['category'] ?? '') === 'progress' || ($c['category'] ?? '') === 'list'
            ))[0] ?? null;

            $row2Cards = [
                [
                    'id' => $mainChart['id'],
                    'position' => 0,
                    'size' => $mainChart['size'] ?? 'large',
                    'pinned' => false,
                ]
            ];

            if ($sideCard) {
                $row2Cards[] = [
                    'id' => $sideCard['id'],
                    'position' => 1,
                    'size' => $sideCard['size'] ?? 'medium',
                    'pinned' => false,
                ];
            }

            $layout['rows'][] = [
                'id' => 'row-2',
                'type' => 'main',
                'cards' => $row2Cards,
            ];
        }

        // Row 3: Secondary content (2-3 medium cards)
        $usedCardIds = array_merge(
            array_column($layout['rows'][0]['cards'] ?? [], 'id'),
            array_column($layout['rows'][1]['cards'] ?? [], 'id')
        );
        
        $remaining = array_filter($recommendedCards, fn($c) => !in_array($c['id'], $usedCardIds));

        $row3Cards = array_slice(array_values($remaining), 0, 3);
        
        if (count($row3Cards) > 0) {
            $layout['rows'][] = [
                'id' => 'row-3',
                'type' => 'secondary',
                'cards' => array_map(fn($card, $index) => [
                    'id' => $card['id'],
                    'position' => $index,
                    'size' => $card['size'] ?? 'medium',
                    'pinned' => false,
                ], $row3Cards, array_keys($row3Cards)),
            ];
        }

        return $layout;
    }

    /**
     * Save user's layout
     */
    public function saveLayout(User $user, array $layout): void
    {
        $organizationId = session('current_organization_id') 
            ?? ($user->attributes['organization_id'] ?? null)
            ?? $user->organizations()->first()?->id;
        
        if (!$organizationId) {
            return;
        }
        
        DashboardLayout::updateOrCreate(
            [
                'user_id' => $user->id,
                'organization_id' => $organizationId,
            ],
            [
                'layout' => $layout,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Add card to user's dashboard
     */
    public function addCard(User $user, string $cardId, ?string $rowId = null, ?int $position = null): array
    {
        $layout = $this->getUserLayout($user);
        $card = CardRegistry::getCard($cardId);

        if (!$card) {
            throw new \Exception('Card not found');
        }

        // If no row specified, add to appropriate row based on card size
        if (!$rowId) {
            $rowId = $this->findBestRowForCard($layout, $card);
        }

        // Find or create row
        $rowIndex = array_search($rowId, array_column($layout['rows'], 'id'));
        
        if ($rowIndex === false) {
            // Create new row
            $layout['rows'][] = [
                'id' => $rowId ?: 'row-' . (count($layout['rows']) + 1),
                'type' => 'custom',
                'cards' => [],
            ];
            $rowIndex = count($layout['rows']) - 1;
        }

        // Add card
        $layout['rows'][$rowIndex]['cards'][] = [
            'id' => $cardId,
            'position' => $position ?? count($layout['rows'][$rowIndex]['cards']),
            'size' => $card['size'] ?? 'medium',
            'pinned' => false,
        ];

        $this->saveLayout($user, $layout);

        return $layout;
    }

    /**
     * Remove card from dashboard
     */
    public function removeCard(User $user, string $cardId): array
    {
        $layout = $this->getUserLayout($user);

        foreach ($layout['rows'] as $rowIndex => $row) {
            $layout['rows'][$rowIndex]['cards'] = array_values(
                array_filter($row['cards'], fn($c) => ($c['id'] ?? '') !== $cardId)
            );

            // Remove empty rows
            if (empty($layout['rows'][$rowIndex]['cards'])) {
                unset($layout['rows'][$rowIndex]);
            }
        }

        $layout['rows'] = array_values($layout['rows']);

        $this->saveLayout($user, $layout);

        return $layout;
    }

    /**
     * Pin/unpin card
     */
    public function togglePin(User $user, string $cardId): array
    {
        $layout = $this->getUserLayout($user);

        foreach ($layout['rows'] as $rowIndex => $row) {
            foreach ($row['cards'] as $cardIndex => $card) {
                if (($card['id'] ?? '') === $cardId) {
                    $layout['rows'][$rowIndex]['cards'][$cardIndex]['pinned'] = 
                        !($card['pinned'] ?? false);
                }
            }
        }

        $this->saveLayout($user, $layout);

        return $layout;
    }

    /**
     * Move card within or between rows
     */
    public function moveCard(
        User $user,
        string $cardId,
        string $targetRowId,
        int $targetPosition
    ): array {
        $layout = $this->getUserLayout($user);

        // Find and remove card from current position
        $card = null;
        foreach ($layout['rows'] as $rowIndex => $row) {
            foreach ($row['cards'] as $cardIndex => $c) {
                if (($c['id'] ?? '') === $cardId) {
                    $card = $c;
                    unset($layout['rows'][$rowIndex]['cards'][$cardIndex]);
                    $layout['rows'][$rowIndex]['cards'] = array_values($layout['rows'][$rowIndex]['cards']);
                    break 2;
                }
            }
        }

        if (!$card) {
            throw new \Exception('Card not found in layout');
        }

        // Find target row
        $targetRowIndex = array_search($targetRowId, array_column($layout['rows'], 'id'));
        
        if ($targetRowIndex === false) {
            throw new \Exception('Target row not found');
        }

        // Insert card at new position
        array_splice(
            $layout['rows'][$targetRowIndex]['cards'],
            $targetPosition,
            0,
            [$card]
        );

        // Update positions
        foreach ($layout['rows'][$targetRowIndex]['cards'] as $index => $c) {
            $layout['rows'][$targetRowIndex]['cards'][$index]['position'] = $index;
        }

        $this->saveLayout($user, $layout);

        return $layout;
    }

    /**
     * Reset to default AI-recommended layout
     */
    public function resetToDefault(User $user): array
    {
        $layout = $this->generateRecommendedLayout($user);
        $this->saveLayout($user, $layout);
        return $layout;
    }

    /**
     * Find best row for card based on size
     */
    protected function findBestRowForCard(array $layout, array $card): string
    {
        $cardSize = $card['size'] ?? 'medium';

        // Small cards go in metrics row
        if ($cardSize === 'small') {
            foreach ($layout['rows'] as $row) {
                if (($row['type'] ?? '') === 'metrics') {
                    return $row['id'];
                }
            }
            return 'row-1'; // Create new metrics row
        }

        // Large cards go in main row
        if ($cardSize === 'large' || $cardSize === 'wide') {
            foreach ($layout['rows'] as $row) {
                if (($row['type'] ?? '') === 'main') {
                    return $row['id'];
                }
            }
            return 'row-2'; // Create new main row
        }

        // Medium cards go in secondary row
        foreach ($layout['rows'] as $row) {
            if (($row['type'] ?? '') === 'secondary') {
                return $row['id'];
            }
        }
        
        return 'row-3'; // Create new secondary row
    }

    /**
     * Get available cards not in user's layout
     */
    public function getAvailableCards(User $user): array
    {
        $layout = $this->getUserLayout($user);
        $usedCardIds = [];

        foreach ($layout['rows'] ?? [] as $row) {
            foreach ($row['cards'] ?? [] as $card) {
                $usedCardIds[] = $card['id'] ?? '';
            }
        }

        $allCards = CardRegistry::getAllCards();

        return array_values(array_filter($allCards, fn($card) => !in_array($card['id'] ?? '', $usedCardIds)));
    }
}

