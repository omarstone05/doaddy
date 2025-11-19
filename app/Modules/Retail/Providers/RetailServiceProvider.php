<?php

namespace App\Modules\Retail\Providers;

use App\Support\BaseModule;

/**
 * Retail Module Service Provider
 * 
 * Bootstraps the retail/POS module with sales, returns, and register management
 */
class RetailServiceProvider extends BaseModule
{
    /**
     * Module name
     */
    protected string $name = 'Retail';

    /**
     * Module version
     */
    protected string $version = '1.0.0';

    /**
     * Module description
     */
    protected string $description = 'Point of Sale and retail management system';

    /**
     * Register module services
     */
    protected function registerServices(): void
    {
        // Register any services here if needed
    }

    /**
     * Boot module
     */
    protected function bootModule(): void
    {
        // Register dashboard cards if needed
        // \App\Modules\Retail\Cards\RetailCards::register();
    }
}

