<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Finance\Cards\FinanceCards;
use App\Modules\ProjectManagement\Cards\ProjectManagementCards;

class DashboardServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Dashboard Layout Manager
        $this->app->singleton(\App\Services\Dashboard\DashboardLayoutManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register all module cards
        $this->registerModuleCards();
    }

    /**
     * Register cards from all modules
     */
    protected function registerModuleCards(): void
    {
        // Core Finance Module - Always available
        FinanceCards::register();

        // Project Management Module - Available if module is enabled
        if (config('modules.project_management.enabled', false)) {
            ProjectManagementCards::register();
        }
    }
}

