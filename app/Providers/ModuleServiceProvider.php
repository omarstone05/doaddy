<?php

namespace App\Providers;

use App\Support\ModuleManager;
use Illuminate\Support\ServiceProvider;

/**
 * Module Service Provider
 * 
 * Bootstraps and loads all enabled modules
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register Module Manager as singleton
        $this->app->singleton(ModuleManager::class, function ($app) {
            return new ModuleManager();
        });

        // Register each enabled module's service provider
        $this->registerModuleProviders();
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        $manager = app(ModuleManager::class);
        $enabledModules = $manager->enabled();

        foreach ($enabledModules as $name => $module) {
            // Load migrations
            $this->loadModuleMigrations($name);
            
            // Load routes
            $this->loadModuleRoutes($name);
            
            // Load views
            $this->loadModuleViews($name);
            
            // Load translations (if any)
            $this->loadModuleTranslations($name);
        }
    }

    /**
     * Register module service providers
     */
    protected function registerModuleProviders(): void
    {
        $manager = app(ModuleManager::class);
        $enabledModules = $manager->enabled();

        foreach ($enabledModules as $name => $module) {
            $providerClass = "App\\Modules\\{$name}\\Providers\\{$name}ServiceProvider";
            
            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }

    /**
     * Load module migrations
     */
    protected function loadModuleMigrations(string $name): void
    {
        $manager = app(ModuleManager::class);
        $path = $manager->getPath($name);
        
        if ($path) {
            $migrationsPath = $path . '/Database/Migrations';
            
            if (is_dir($migrationsPath)) {
                $this->loadMigrationsFrom($migrationsPath);
            }
        }
    }

    /**
     * Load module routes
     */
    protected function loadModuleRoutes(string $name): void
    {
        $manager = app(ModuleManager::class);
        $manager->loadRoutes($name);
    }

    /**
     * Load module views
     */
    protected function loadModuleViews(string $name): void
    {
        $manager = app(ModuleManager::class);
        $manager->loadViews($name);
    }

    /**
     * Load module translations
     */
    protected function loadModuleTranslations(string $name): void
    {
        $manager = app(ModuleManager::class);
        $path = $manager->getPath($name);
        
        if ($path) {
            $translationsPath = $path . '/Resources/lang';
            
            if (is_dir($translationsPath)) {
                $this->loadTranslationsFrom($translationsPath, strtolower($name));
            }
        }
    }
}

