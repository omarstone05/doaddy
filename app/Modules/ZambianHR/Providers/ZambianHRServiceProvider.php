<?php

namespace App\Modules\ZambianHR\Providers;

use App\Support\BaseModule;

/**
 * Zambian HR Module Service Provider
 * 
 * Bootstraps the Zambian HR compliance module with Zambian labor law specific features
 */
class ZambianHRServiceProvider extends BaseModule
{
    /**
     * Module name
     */
    protected string $name = 'Zambian HR';

    /**
     * Module version
     */
    protected string $version = '1.0.0';

    /**
     * Module description
     */
    protected string $description = 'Zambian labor law compliant HR management';

    /**
     * Register module services
     */
    protected function registerServices(): void
    {
        // Register Zambian HR services here if needed
    }

    /**
     * Boot module
     */
    protected function bootModule(): void
    {
        // Load module routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        
        // Load module views
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'zambian-hr');
        
        // Register dashboard cards if needed
        // \App\Modules\ZambianHR\Cards\ZambianHRCards::register();
    }
}

