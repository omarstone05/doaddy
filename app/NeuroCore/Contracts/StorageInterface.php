<?php

namespace App\NeuroCore\Contracts;

/**
 * Interface for persisting Neuro data
 * Allows different storage backends (database, file, cache, etc.)
 */
interface StorageInterface
{
    /**
     * Store a value
     *
     * @param string $key Unique key
     * @param mixed $value Value to store (will be serialized)
     * @param int|null $ttl Time to live in seconds (null = forever)
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void;

    /**
     * Retrieve a value
     *
     * @param string $key The key to retrieve
     * @param mixed $default Default if not found
     * @return mixed The stored value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if key exists
     */
    public function has(string $key): bool;

    /**
     * Delete a key
     */
    public function delete(string $key): bool;

    /**
     * Get all keys matching a pattern
     *
     * @param string $pattern Pattern with * wildcards
     * @return array Array of matching keys
     */
    public function keys(string $pattern): array;

    /**
     * Store in a namespaced collection
     *
     * @param string $namespace The collection namespace
     * @param string $key Key within namespace
     * @param mixed $value Value to store
     */
    public function setInNamespace(string $namespace, string $key, mixed $value): void;

    /**
     * Get all items in a namespace
     *
     * @param string $namespace The collection namespace
     * @return array All items in namespace
     */
    public function getNamespace(string $namespace): array;
}


