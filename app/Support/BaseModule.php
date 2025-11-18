<?php

namespace App\Support;

use Illuminate\Support\ServiceProvider;

/**
 * Base Module Class
 * 
 * All module service providers extend this class
 */
abstract class BaseModule extends ServiceProvider
{
    /**
     * Module name
     */
    protected string $name;

    /**
     * Module version
     */
    protected string $version = '1.0.0';

    /**
     * Module description
     */
    protected string $description;

    /**
     * Module dependencies
     */
    protected array $dependencies = [];

    /**
     * Register module
     */
    public function register(): void
    {
        // Register module config
        $this->registerConfig();
        
        // Register module services
        $this->registerServices();
    }

    /**
     * Boot module
     */
    public function boot(): void
    {
        // Boot module-specific logic
        $this->bootModule();
    }

    /**
     * Register module config
     */
    protected function registerConfig(): void
    {
        $configPath = $this->getModulePath() . '/Config/config.php';
        
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, strtolower($this->name));
        }
    }

    /**
     * Register module services
     */
    abstract protected function registerServices(): void;

    /**
     * Boot module-specific logic
     */
    abstract protected function bootModule(): void;

    /**
     * Get module path
     */
    protected function getModulePath(): string
    {
        return app_path("Modules/{$this->name}");
    }

    /**
     * Get module name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get module version
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get module description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get module dependencies
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
}

