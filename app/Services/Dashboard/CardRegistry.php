<?php

namespace App\Services\Dashboard;

/**
 * Card Registry - Central system for all dashboard cards
 * 
 * Each module registers its cards here
 * AI uses this to decide default layouts
 * Users can pick from available cards
 */
class CardRegistry
{
    protected static array $cards = [];
    protected static array $modules = [];

    /**
     * Register a card from a module
     */
    public static function register(string $moduleId, array $card): void
    {
        if (!isset(self::$modules[$moduleId])) {
            self::$modules[$moduleId] = [
                'cards' => [],
                'metadata' => [],
            ];
        }

        $cardId = $card['id'];
        
        // Store card with module reference
        self::$cards[$cardId] = array_merge($card, [
            'module_id' => $moduleId,
        ]);

        // Add to module's card list
        self::$modules[$moduleId]['cards'][] = $cardId;
    }

    /**
     * Register module metadata
     */
    public static function registerModule(string $moduleId, array $metadata): void
    {
        if (!isset(self::$modules[$moduleId])) {
            self::$modules[$moduleId] = ['cards' => []];
        }

        self::$modules[$moduleId]['metadata'] = $metadata;
    }

    /**
     * Get all available cards
     */
    public static function getAllCards(): array
    {
        return self::$cards;
    }

    /**
     * Get cards by module
     */
    public static function getModuleCards(string $moduleId): array
    {
        $moduleCardIds = self::$modules[$moduleId]['cards'] ?? [];
        
        return array_filter(self::$cards, function($card) use ($moduleCardIds) {
            return in_array($card['id'], $moduleCardIds);
        });
    }

    /**
     * Get card by ID
     */
    public static function getCard(string $cardId): ?array
    {
        return self::$cards[$cardId] ?? null;
    }

    /**
     * Get all modules
     */
    public static function getAllModules(): array
    {
        return array_map(function($moduleId, $data) {
            return array_merge($data['metadata'] ?? [], [
                'id' => $moduleId,
                'card_count' => count($data['cards'] ?? []),
            ]);
        }, array_keys(self::$modules), self::$modules);
    }

    /**
     * Get cards grouped by module
     */
    public static function getCardsGroupedByModule(): array
    {
        $grouped = [];

        foreach (self::$modules as $moduleId => $data) {
            $grouped[$moduleId] = [
                'metadata' => $data['metadata'] ?? [],
                'cards' => self::getModuleCards($moduleId),
            ];
        }

        return $grouped;
    }

    /**
     * Get cards by category
     */
    public static function getCardsByCategory(string $category): array
    {
        return array_filter(self::$cards, function($card) use ($category) {
            return ($card['category'] ?? null) === $category;
        });
    }

    /**
     * Search cards
     */
    public static function searchCards(string $query): array
    {
        $query = strtolower($query);

        return array_filter(self::$cards, function($card) use ($query) {
            return str_contains(strtolower($card['name'] ?? ''), $query) ||
                   str_contains(strtolower($card['description'] ?? ''), $query) ||
                   str_contains(strtolower($card['tags'] ?? ''), $query);
        });
    }

    /**
     * Get recommended cards for user
     */
    public static function getRecommendedCards($user, int $limit = 12): array
    {
        $businessType = $user->business_type ?? 'general';
        $allCards = self::$cards;

        // Score each card
        $scored = array_map(function($card) use ($user, $businessType) {
            $score = 0;

            // Business type match
            if (in_array($businessType, $card['suitable_for'] ?? [])) {
                $score += 10;
            }

            // Priority (set by module)
            $score += $card['priority'] ?? 0;
            
            return [
                'card' => $card,
                'score' => $score,
            ];
        }, $allCards);

        // Sort by score
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        // Return top cards
        return array_slice(array_column($scored, 'card'), 0, $limit);
    }

    /**
     * Clear all registered cards (for testing)
     */
    public static function clear(): void
    {
        self::$cards = [];
        self::$modules = [];
    }
}
