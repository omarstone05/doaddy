<?php

namespace App\Modules\Consulting\Providers;

use App\Support\BaseModule;
use App\Modules\Consulting\Services\ProjectService;

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
        $this->app->singleton(ProjectService::class);
    }

    /**
     * Boot module
     */
    protected function bootModule(): void
    {
        // Register services
        $this->app->singleton(\App\Modules\Consulting\Services\ProjectService::class);
        
        // Register dashboard cards
        \App\Modules\Consulting\Cards\ConsultingCards::register();
        
        // Register policies (will be created later)
        // $this->registerPolicies();
        
        // Register events (will be created later)
        // $this->registerEvents();
        
        // Register observers (will be created later)
        // $this->registerObservers();
    }
}

