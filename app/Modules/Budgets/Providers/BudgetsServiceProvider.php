<?php

namespace App\Modules\Budgets\Providers;

use App\Support\BaseModule;

/**
 * Budgets Module Service Provider
 * 
 * Bootstraps the Budgets module for budget management and spending tracking
 */
class BudgetsServiceProvider extends BaseModule
{
    /**
     * Module name
     */
    protected string $name = 'Budgets';

    /**
     * Module version
     */
    protected string $version = '1.0.0';

    /**
     * Module description
     */
    protected string $description = 'Budget management system for tracking spending against budgets';

    /**
     * Register module services
     */
    protected function registerServices(): void
    {
        // Register Budget services here if needed
    }

    /**
     * Boot module
     */
    protected function bootModule(): void
    {
        // Load module routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        
        // Load module views if needed
        // $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'budgets');
        
        // Register dashboard cards if needed
        // \App\Modules\Budgets\Cards\BudgetsCards::register();
    }
}

