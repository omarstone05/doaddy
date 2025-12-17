<?php

namespace App\Modules\Compliance\Providers;

use App\Support\BaseModule;

/**
 * Compliance Module Service Provider
 * 
 * Bootstraps the Compliance module for document, license, tax, and audit management
 */
class ComplianceServiceProvider extends BaseModule
{
    /**
     * Module name
     */
    protected string $name = 'Compliance';

    /**
     * Module version
     */
    protected string $version = '1.0.0';

    /**
     * Module description
     */
    protected string $description = 'Compliance management system';

    /**
     * Register module services
     */
    protected function registerServices(): void
    {
        // Register Compliance services here if needed
    }

    /**
     * Boot module
     */
    protected function bootModule(): void
    {
        // Load module routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        
        // Load module views if they exist
        if (is_dir(__DIR__ . '/../Resources/views')) {
            $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'compliance');
        }
    }
}

