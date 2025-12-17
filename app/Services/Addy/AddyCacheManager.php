<?php

namespace App\Services\Addy;

use Illuminate\Support\Facades\Cache;

class AddyCacheManager
{
    /**
     * Clear all Addy caches for an organization
     */
    public static function clearOrganization(int|string $organizationId): void
    {
        Cache::tags(["addy:org:{$organizationId}"])->flush();
    }

    /**
     * Clear all Addy caches globally
     */
    public static function clearAll(): void
    {
        Cache::tags(['addy'])->flush();
    }

    /**
     * Clear specific agent cache for organization
     */
    public static function clearAgent(string $agentName, int|string $organizationId): void
    {
        Cache::tags([
            'addy',
            "addy:{$agentName}",
            "addy:org:{$organizationId}",
        ])->flush();
    }

    /**
     * Warm up caches for an organization
     */
    public static function warmUp(int|string $organizationId): void
    {
        $organization = \App\Models\Organization::find($organizationId);
        
        if (!$organization) {
            return;
        }

        // Trigger perception for all agents (will cache results)
        $agents = [
            new \App\Services\Addy\Agents\MoneyAgent($organization),
            new \App\Services\Addy\Agents\SalesAgent($organization),
            new \App\Services\Addy\Agents\PeopleAgent($organization),
            new \App\Services\Addy\Agents\InventoryAgent($organization),
        ];

        foreach ($agents as $agent) {
            $agent->perceive();
        }
    }

    /**
     * Get cache statistics
     */
    public static function getStats(): array
    {
        // Note: This requires Redis commands
        // Only works with phpredis extension or predis
        try {
            $store = Cache::getStore();
            
            // Check if we're using Redis
            if (method_exists($store, 'connection')) {
                $connection = $store->connection();
                
                if (method_exists($connection, 'info')) {
                    $info = $connection->info();
                    
                    return [
                        'connected_clients' => $info['connected_clients'] ?? 0,
                        'used_memory_human' => $info['used_memory_human'] ?? 'N/A',
                        'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                        'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                        'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                        'hit_rate' => self::calculateHitRate($info),
                    ];
                }
            }
            
            return ['info' => 'Cache stats only available with Redis. Current driver: ' . config('cache.default')];
        } catch (\Exception $e) {
            return ['error' => 'Unable to get cache stats: ' . $e->getMessage()];
        }
    }

    protected static function calculateHitRate(array $info): string
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        if ($total === 0) {
            return '0%';
        }
        
        return number_format(($hits / $total) * 100, 2) . '%';
    }
}

