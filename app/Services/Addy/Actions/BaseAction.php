<?php

namespace App\Services\Addy\Actions;

use App\Models\Organization;
use App\Models\User;

abstract class BaseAction
{
    protected Organization $organization;
    protected User $user;
    protected array $parameters;

    public function __construct(Organization $organization, User $user, array $parameters = [])
    {
        $this->organization = $organization;
        $this->user = $user;
        $this->parameters = $parameters;
    }

    /**
     * Validate parameters
     */
    abstract public function validate(): bool;

    /**
     * Generate preview of what will happen
     */
    abstract public function preview(): array;

    /**
     * Execute the action
     */
    abstract public function execute(): array;

    /**
     * Can this action be undone?
     */
    public function canUndo(): bool
    {
        return false;
    }

    /**
     * Undo the action
     */
    public function undo(array $result): array
    {
        throw new \Exception('This action cannot be undone');
    }

    /**
     * Get estimated impact
     */
    public function getImpact(): string
    {
        return 'medium'; // low, medium, high
    }

    /**
     * Get required permissions
     */
    public function getRequiredPermissions(): array
    {
        return [];
    }

    /**
     * Check if user has permissions
     */
    public function hasPermissions(): bool
    {
        $required = $this->getRequiredPermissions();
        
        if (empty($required)) {
            return true;
        }

        // Check user permissions
        // Implement based on your permission system
        return true;
    }
}

