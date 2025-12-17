<?php

namespace App\NeuroCore\Adapters;

use App\NeuroCore\Contracts\StorageInterface;
use Illuminate\Support\Facades\Cache;

/**
 * CacheStorage - Uses Laravel's cache for persistence
 * Simpler than database, good for development/testing
 * Data persists based on cache driver (file, redis, etc.)
 */
class CacheStorage implements StorageInterface
{
    private string $prefix;
    private int $defaultTtl;

    public function __construct(string $prefix = 'neuro', int $defaultTtl = 86400 * 30)
    {
        $this->prefix = $prefix;
        $this->defaultTtl = $defaultTtl; // 30 days default
    }

    /**
     * Store a value
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $fullKey = "{$this->prefix}:{$key}";
        $ttl = $ttl ?? $this->defaultTtl;

        Cache::put($fullKey, $value, $ttl);

        // Track key in registry for keys() lookup
        $this->registerKey($key);
    }

    /**
     * Retrieve a value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get("{$this->prefix}:{$key}", $default);
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        return Cache::has("{$this->prefix}:{$key}");
    }

    /**
     * Delete a key
     */
    public function delete(string $key): bool
    {
        $this->unregisterKey($key);
        return Cache::forget("{$this->prefix}:{$key}");
    }

    /**
     * Get all keys matching a pattern
     */
    public function keys(string $pattern): array
    {
        $registry = Cache::get("{$this->prefix}:__key_registry__", []);
        
        // Convert glob pattern to regex - escape special chars but keep * and ? for glob
        $escaped = preg_quote($pattern, '/');
        // Restore * and ? as regex wildcards (they were escaped by preg_quote)
        $regex = '/^' . str_replace(['\*', '\?'], ['.*', '.'], $escaped) . '$/';

        // Use array_values to reset indices after filtering
        return array_values(array_filter($registry, fn($key) => preg_match($regex, $key)));
    }

    /**
     * Store in a namespaced collection
     */
    public function setInNamespace(string $namespace, string $key, mixed $value): void
    {
        $this->set("{$namespace}:{$key}", $value);
    }

    /**
     * Get all items in a namespace
     */
    public function getNamespace(string $namespace): array
    {
        $keys = $this->keys("{$namespace}:*");
        $items = [];

        foreach ($keys as $key) {
            $shortKey = str_replace("{$namespace}:", '', $key);
            $items[$shortKey] = $this->get($key);
        }

        return $items;
    }

    /**
     * Register a key in the registry
     */
    private function registerKey(string $key): void
    {
        $registry = Cache::get("{$this->prefix}:__key_registry__", []);
        if (!in_array($key, $registry)) {
            $registry[] = $key;
            Cache::put("{$this->prefix}:__key_registry__", $registry, $this->defaultTtl);
        }
    }

    /**
     * Unregister a key from the registry
     */
    private function unregisterKey(string $key): void
    {
        $registry = Cache::get("{$this->prefix}:__key_registry__", []);
        $registry = array_filter($registry, fn($k) => $k !== $key);
        Cache::put("{$this->prefix}:__key_registry__", array_values($registry), $this->defaultTtl);
    }

    /**
     * Clear all keys with this prefix
     */
    public function flush(): void
    {
        $registry = Cache::get("{$this->prefix}:__key_registry__", []);
        foreach ($registry as $key) {
            Cache::forget("{$this->prefix}:{$key}");
        }
        Cache::forget("{$this->prefix}:__key_registry__");
    }
}

