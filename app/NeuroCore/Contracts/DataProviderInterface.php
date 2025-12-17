<?php

namespace App\NeuroCore\Contracts;

/**
 * Interface for getting data from host system
 * Each system (Addy, Projjo, etc.) implements this to provide context
 */
interface DataProviderInterface
{
    /**
     * Get the system identifier
     */
    public function getSystemId(): string;

    /**
     * Get user's data summary from this system
     * Used to enrich user profile with system-specific context
     *
     * @param string $userId The user ID
     * @return array Summary data about user's activity in this system
     */
    public function getUserDataSummary(string $userId): array;

    /**
     * Get relevant context for a query
     * Called when processing a chat message to get system-specific data
     *
     * @param string $userId The user ID
     * @param string $query The user's query/message
     * @param array $entities Entities extracted from query
     * @return array Relevant data from this system
     */
    public function getRelevantContext(string $userId, string $query, array $entities = []): array;

    /**
     * Get user's recent activity in this system
     *
     * @param string $userId The user ID
     * @param int $limit Number of activities
     * @return array Recent activities
     */
    public function getRecentActivity(string $userId, int $limit = 10): array;

    /**
     * Search system data based on query
     *
     * @param string $userId The user ID
     * @param string $searchTerm What to search for
     * @param array $filters Optional filters
     * @return array Search results
     */
    public function search(string $userId, string $searchTerm, array $filters = []): array;

    /**
     * Get available actions user can take in this system
     * Used to suggest possible next steps
     *
     * @param string $userId The user ID
     * @return array Available actions with descriptions
     */
    public function getAvailableActions(string $userId): array;
}


