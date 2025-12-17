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
        
        // Load routes for ALL modules, not just enabled ones.
        // Module access is controlled at runtime via the 'module:ModuleName' middleware.
        // This is necessary because we can't determine org-specific module state at boot time.
        $allModules = $manager->all();

        foreach ($allModules as $name => $module) {
            // Load migrations for all modules (migrations need to exist regardless of enabled state)
            $this->loadModuleMigrations($name);
            
            // Load routes for all modules (access control handled by middleware)
            $this->loadModuleRoutes($name);
            
            // Load views for all modules
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
                try {
                    $this->app->register($providerClass);
                } catch (\Exception $e) {
                    \Log::error("Failed to register module service provider: {$name}", [
                        'provider_class' => $providerClass,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // Continue with other modules even if one fails
                }
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
     * 
     * Note: We load routes directly without adding a controller namespace,
     * since modules may have controllers in different locations.
     * The route files should use fully qualified controller class names.
     */
    protected function loadModuleRoutes(string $name): void
    {
        $manager = app(ModuleManager::class);
        $path = $manager->getPath($name);
        
        if ($path) {
            $routesPath = $path . '/Routes';
            
            // Web routes - apply web middleware group for session, CSRF, etc.
            if (is_file($routesPath . '/web.php')) {
                \Illuminate\Support\Facades\Route::middleware('web')
                    ->group($routesPath . '/web.php');
            }
            
            // API routes
            if (is_file($routesPath . '/api.php')) {
                \Illuminate\Support\Facades\Route::middleware('api')
                    ->prefix('api')
                    ->group($routesPath . '/api.php');
            }
        }
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

