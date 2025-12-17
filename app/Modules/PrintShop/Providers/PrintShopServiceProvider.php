<?php

namespace App\Modules\PrintShop\Providers;

use App\Support\BaseModule;

/**
 * PrintShop Module Service Provider
 * 
 * Bootstraps the print shop module with cost calculator, materials, and job management
 */
class PrintShopServiceProvider extends BaseModule
{
    /**
     * Module name
     */
    protected string $name = 'PrintShop';

    /**
     * Module version
     */
    protected string $version = '1.0.0';

    /**
     * Module description
     */
    protected string $description = 'Print shop cost calculator and job management system';

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
        // Load routes from the module's Routes directory
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }
}

