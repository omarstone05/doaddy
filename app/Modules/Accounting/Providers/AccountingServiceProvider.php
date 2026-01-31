<?php

namespace App\Modules\Accounting\Providers;

use App\Support\BaseModule;

/**
 * Accounting Module Service Provider
 * 
 * Bootstraps the Accounting module for advanced accounting features
 */
class AccountingServiceProvider extends BaseModule
{
    /**
     * Module name
     */
    protected string $name = 'Accounting';

    /**
     * Module version
     */
    protected string $version = '1.0.0';

    /**
     * Module description
     */
    protected string $description = 'Advanced accounting system with chart of accounts, double-entry bookkeeping, journal entries, and financial statements';

    /**
     * Register module services
     */
    protected function registerServices(): void
    {
        // Register accounting services
        $this->app->singleton(\App\Modules\Accounting\Services\AccountingService::class);
        $this->app->singleton(\App\Modules\Accounting\Services\JournalEntryService::class);
        $this->app->singleton(\App\Modules\Accounting\Services\FinancialStatementService::class);
    }

    /**
     * Boot module
     * 
     * Note: Routes are automatically loaded by ModuleServiceProvider
     * for all modules. Access is controlled by the 'module:Accounting' middleware.
     */
    protected function bootModule(): void
    {
        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\Accounting\Console\Commands\SeedDefaultAccounts::class,
            ]);
        }
        
        // Register dashboard cards if needed
        // \App\Modules\Accounting\Cards\AccountingCards::register();
        
        // Register policies if needed
        // $this->registerPolicies();
        
        // Register events/listeners if needed
        // Event::listen(Event::class, Listener::class);
    }
}

