<?php

namespace App\Modules\HR\Providers;

use App\Support\BaseModule;

/**
 * HR Module Service Provider
 * 
 * Bootstraps the HR module with comprehensive employee lifecycle management
 */
class HRServiceProvider extends BaseModule
{
    /**
     * Module name
     */
    protected string $name = 'HR';

    /**
     * Module version
     */
    protected string $version = '1.0.0';

    /**
     * Module description
     */
    protected string $description = 'Complete HR management system';

    /**
     * Register module services
     */
    protected function registerServices(): void
    {
        // Register HR services here if needed
    }

    /**
     * Boot module
     */
    protected function bootModule(): void
    {
        // Load module routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        
        // Load module views
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'hr');
        
        // Register dashboard cards if needed
        // \App\Modules\HR\Cards\HRCards::register();
    }
}

