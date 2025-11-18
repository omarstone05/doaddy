<?php

namespace App\Modules\Consulting\Providers;

use App\Support\BaseModule;

/**
 * Consulting Module Service Provider
 * 
 * Bootstraps the complete consulting/project management module
 */
class ConsultingServiceProvider extends BaseModule
{
    /**
     * Module name
     */
    protected string $name = 'Consulting';

    /**
     * Module version
     */
    protected string $version = '1.0.0';

    /**
     * Module description
     */
    protected string $description = 'Complete project management system';

    /**
     * Register module services
     */
    protected function registerServices(): void
    {
        // Register services (will be created later)
        // $this->app->singleton(ProjectService::class);
        // $this->app->singleton(DeliverableService::class);
        // $this->app->singleton(TimeTrackingService::class);
    }

    /**
     * Boot module
     */
    protected function bootModule(): void
    {
        // Register policies (will be created later)
        // $this->registerPolicies();
        
        // Register events (will be created later)
        // $this->registerEvents();
        
        // Register observers (will be created later)
        // $this->registerObservers();
    }
}

