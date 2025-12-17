<?php

namespace App\NeuroCore\Adapters;

use App\NeuroCore\Contracts\StorageInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * DatabaseStorage - Uses Laravel's database + cache for persistence
 * This adapter stores Neuro data in a database table with caching
 */
class DatabaseStorage implements StorageInterface
{
    private string $table;
    private int $cacheTtl;

    public function __construct(string $table = 'neuro_storage', int $cacheTtl = 3600)
    {
        $this->table = $table;
        $this->cacheTtl = $cacheTtl;
    }

    /**
     * Store a value
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $serialized = json_encode($value);
        $expiresAt = $ttl ? now()->addSeconds($ttl) : null;

        DB::table($this->table)->updateOrInsert(
            ['key' => $key],
            [
                'value' => $serialized,
                'expires_at' => $expiresAt,
                'updated_at' => now(),
            ]
        );

        // Update cache
        $cacheTtl = $ttl ?? $this->cacheTtl;
        Cache::put("neuro:{$key}", $value, $cacheTtl);
    }

    /**
     * Retrieve a value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Try cache first
        if (Cache::has("neuro:{$key}")) {
            return Cache::get("neuro:{$key}");
        }

        // Get from database
        $row = DB::table($this->table)
            ->where('key', $key)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$row) {
            return $default;
        }

        $value = json_decode($row->value, true);

        // Warm cache
        Cache::put("neuro:{$key}", $value, $this->cacheTtl);

        return $value;
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        if (Cache::has("neuro:{$key}")) {
            return true;
        }

        return DB::table($this->table)
            ->where('key', $key)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Delete a key
     */
    public function delete(string $key): bool
    {
        Cache::forget("neuro:{$key}");

        return DB::table($this->table)
            ->where('key', $key)
            ->delete() > 0;
    }

    /**
     * Get all keys matching a pattern
     */
    public function keys(string $pattern): array
    {
        // Convert glob pattern to SQL LIKE
        $sqlPattern = str_replace('*', '%', $pattern);

        return DB::table($this->table)
            ->where('key', 'LIKE', $sqlPattern)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->pluck('key')
            ->toArray();
    }

    /**
     * Store in a namespaced collection
     */
    public function setInNamespace(string $namespace, string $key, mixed $value): void
    {
        $fullKey = "{$namespace}:{$key}";
        $this->set($fullKey, $value);
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
     * Clean up expired entries
     */
    public function cleanup(): int
    {
        return DB::table($this->table)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();
    }

    /**
     * Get the migration SQL for this storage
     */
    public static function getMigrationSql(): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS neuro_storage (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(500) NOT NULL UNIQUE,
    value LONGTEXT NOT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (`key`),
    INDEX idx_expires_at (expires_at)
);
SQL;
    }
}


