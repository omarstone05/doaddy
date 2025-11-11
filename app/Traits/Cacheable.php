<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait Cacheable
{
    /**
     * Cache TTL in seconds (5 minutes default)
     */
    protected int $cacheTtl = 300;

    /**
     * Remember data with automatic cache key generation
     */
    protected function remember(string $key, callable $callback)
    {
        $fullKey = $this->getCacheKey($key);
        
        // Check if cache driver supports tagging
        $store = Cache::getStore();
        if (method_exists($store, 'getTags') || method_exists($store, 'tags')) {
            try {
                return Cache::tags($this->getCacheTags())->remember(
                    $fullKey,
                    $this->cacheTtl,
                    $callback
                );
            } catch (\BadMethodCallException $e) {
                // Fall back to regular caching if tagging not supported
            }
        }
        
        // Fallback to regular caching without tags
        return Cache::remember($fullKey, $this->cacheTtl, $callback);
    }

    /**
     * Get cache key with proper namespacing
     */
    protected function getCacheKey(string $key): string
    {
        $base = class_basename($this);
        $orgId = $this->getOrganizationId();
        
        return "addy:{$base}:{$orgId}:{$key}";
    }

    /**
     * Get cache tags for this instance
     */
    protected function getCacheTags(): array
    {
        $base = class_basename($this);
        $orgId = $this->getOrganizationId();
        
        return [
            'addy',
            "addy:{$base}",
            "addy:org:{$orgId}",
        ];
    }

    /**
     * Clear cache for this instance
     */
    public function clearCache(): void
    {
        $store = Cache::getStore();
        if (method_exists($store, 'getTags') || method_exists($store, 'tags')) {
            try {
                Cache::tags($this->getCacheTags())->flush();
                return;
            } catch (\BadMethodCallException $e) {
                // Fall back to regular cache clearing
            }
        }
        
        // Fallback: clear by key pattern (limited support)
        $fullKey = $this->getCacheKey('*');
        Cache::forget($fullKey);
    }

    /**
     * Clear cache by specific key
     */
    protected function forgetCache(string $key): void
    {
        $fullKey = $this->getCacheKey($key);
        
        $store = Cache::getStore();
        if (method_exists($store, 'getTags') || method_exists($store, 'tags')) {
            try {
                Cache::tags($this->getCacheTags())->forget($fullKey);
                return;
            } catch (\BadMethodCallException $e) {
                // Fall back to regular cache forgetting
            }
        }
        
        Cache::forget($fullKey);
    }

    /**
     * Get organization ID (must be implemented by using class)
     */
    abstract protected function getOrganizationId(): int|string;
}

