<?php

namespace App\Modules\CRM\Providers;

use App\Support\BaseModule;

/**
 * CRM Module Service Provider
 * 
 * Bootstraps the complete CRM module with lead management, opportunities, quotes, and sales automation
 */
class CRMServiceProvider extends BaseModule
{
    /**
     * Module name
     */
    protected string $name = 'CRM';

    /**
     * Module version
     */
    protected string $version = '1.0.0';

    /**
     * Module description
     */
    protected string $description = 'Complete Customer Relationship Management system';

    /**
     * Register module services
     */
    protected function registerServices(): void
    {
        // Register CRM services here if needed
        // $this->app->singleton(\App\Modules\CRM\Services\LeadService::class);
    }

    /**
     * Boot module
     */
    protected function bootModule(): void
    {
        // Register dashboard cards
        \App\Modules\CRM\Cards\CRMCards::register();
        
        // Register policies (will be created later)
        // $this->registerPolicies();
        
        // Register events (will be created later)
        // $this->registerEvents();
        
        // Register observers (will be created later)
        // $this->registerObservers();
    }
}

