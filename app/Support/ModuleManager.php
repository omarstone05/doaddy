<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Module Manager
 * 
 * Handles loading, enabling, disabling, and managing all Addy modules
 */
class ModuleManager
{
    protected string $modulePath;
    protected array $modules = [];
    protected array $enabledModules = [];

    public function __construct()
    {
        $this->modulePath = app_path('Modules');
    }

    /**
     * Scan and discover all modules
     */
    public function discover(): array
    {
        if (!File::exists($this->modulePath)) {
            File::makeDirectory($this->modulePath, 0755, true);
        }

        // Clear existing modules cache
        $this->modules = [];
        
        $modules = [];
        $directories = File::directories($this->modulePath);

        foreach ($directories as $directory) {
            $moduleName = basename($directory);
            $configPath = $directory . '/module.json';

            if (File::exists($configPath)) {
                // Read file fresh each time - don't rely on cache
                $configContent = File::get($configPath);
                $config = json_decode($configContent, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    \Log::warning("Failed to parse module.json for {$moduleName}: " . json_last_error_msg());
                    continue;
                }
                
                $modules[$moduleName] = [
                    'name' => $moduleName,
                    'path' => $directory,
                    'config' => $config,
                    'enabled' => $config['enabled'] ?? false,
                    'version' => $config['version'] ?? '1.0.0',
                ];
            }
        }

        $this->modules = $modules;
        return $modules;
    }

    /**
     * Get all modules
     */
    public function all(): array
    {
        if (empty($this->modules)) {
            $this->discover();
        }

        return $this->modules;
    }

    /**
     * Get enabled modules only
     */
    public function enabled(): array
    {
        return array_filter($this->all(), fn($module) => $module['enabled']);
    }

    /**
     * Check if module exists
     */
    public function exists(string $name): bool
    {
        return isset($this->all()[$name]);
    }

    /**
     * Check if module is enabled
     */
    public function isEnabled(string $name): bool
    {
        $modules = $this->all();
        return isset($modules[$name]) && $modules[$name]['enabled'];
    }

    /**
     * Enable a module
     */
    public function enable(string $name): bool
    {
        if (!$this->exists($name)) {
            return false;
        }

        $module = $this->modules[$name];
        $configPath = $module['path'] . '/module.json';
        
        $config = json_decode(File::get($configPath), true);
        $config['enabled'] = true;
        
        // Atomic write with file locking to prevent race conditions
        $this->atomicWrite($configPath, json_encode($config, JSON_PRETTY_PRINT));
        
        // Clear cache and re-discover to ensure fresh state
        $this->modules = [];
        $this->discover();
        
        return true;
    }

    /**
     * Disable a module
     */
    public function disable(string $name): bool
    {
        if (!$this->exists($name)) {
            return false;
        }

        $module = $this->modules[$name];
        $configPath = $module['path'] . '/module.json';
        
        $config = json_decode(File::get($configPath), true);
        $config['enabled'] = false;
        
        // Atomic write with file locking to prevent race conditions
        $this->atomicWrite($configPath, json_encode($config, JSON_PRETTY_PRINT));
        
        // Clear cache and re-discover to ensure fresh state
        $this->modules = [];
        $this->discover();
        
        return true;
    }

    /**
     * Atomically write file content with locking to prevent race conditions
     */
    protected function atomicWrite(string $path, string $content): void
    {
        // Ensure directory exists and is writable
        $directory = dirname($path);
        if (!is_dir($directory)) {
            throw new \RuntimeException("Module directory does not exist: {$directory}");
        }
        
        if (!is_writable($directory)) {
            throw new \RuntimeException("Module directory is not writable: {$directory}. Please check file permissions.");
        }
        
        // Write to temporary file first
        $tempPath = $path . '.tmp';
        
        // Use file_put_contents with LOCK_EX for atomic write
        $result = @file_put_contents($tempPath, $content, LOCK_EX);
        
        if ($result === false) {
            $error = error_get_last();
            $errorMsg = $error ? $error['message'] : 'Unknown error';
            throw new \RuntimeException("Failed to write module configuration to temporary file: {$tempPath}. Error: {$errorMsg}");
        }
        
        // Atomically rename temp file to final location
        if (!@rename($tempPath, $path)) {
            // Clean up temp file if rename fails
            @unlink($tempPath);
            $error = error_get_last();
            $errorMsg = $error ? $error['message'] : 'Unknown error';
            throw new \RuntimeException("Failed to rename temporary module configuration file: {$tempPath} to {$path}. Error: {$errorMsg}");
        }
    }

    /**
     * Get module path
     */
    public function getPath(string $name): ?string
    {
        $modules = $this->all();
        return $modules[$name]['path'] ?? null;
    }

    /**
     * Get module config
     */
    public function getConfig(string $name): ?array
    {
        $modules = $this->all();
        return $modules[$name]['config'] ?? null;
    }

    /**
     * Get module version
     */
    public function getVersion(string $name): ?string
    {
        $modules = $this->all();
        return $modules[$name]['version'] ?? null;
    }

    /**
     * Load module migrations
     */
    public function loadMigrations(string $name): void
    {
        $path = $this->getPath($name);
        
        if ($path) {
            $migrationsPath = $path . '/Database/Migrations';
            
            if (File::exists($migrationsPath)) {
                app('migrator')->path($migrationsPath);
            }
        }
    }

    /**
     * Load module routes
     */
    public function loadRoutes(string $name): void
    {
        $path = $this->getPath($name);
        
        if ($path) {
            $routesPath = $path . '/Routes';
            
            // API routes
            if (File::exists($routesPath . '/api.php')) {
                app('router')->group([
                    'middleware' => 'api',
                    'prefix' => 'api',
                    'namespace' => "App\\Modules\\{$name}\\Http\\Controllers",
                ], function ($router) use ($routesPath) {
                    require $routesPath . '/api.php';
                });
            }

            // Web routes
            if (File::exists($routesPath . '/web.php')) {
                app('router')->group([
                    'middleware' => 'web',
                    'namespace' => "App\\Modules\\{$name}\\Http\\Controllers",
                ], function ($router) use ($routesPath) {
                    require $routesPath . '/web.php';
                });
            }
        }
    }

    /**
     * Load module views
     */
    public function loadViews(string $name): void
    {
        $path = $this->getPath($name);
        
        if ($path) {
            $viewsPath = $path . '/Resources/views';
            
            if (File::exists($viewsPath)) {
                app('view')->addNamespace(strtolower($name), $viewsPath);
            }
        }
    }

    /**
     * Get module dependencies
     */
    public function getDependencies(string $name): array
    {
        $config = $this->getConfig($name);
        return $config['dependencies'] ?? [];
    }

    /**
     * Check if module dependencies are satisfied
     */
    public function checkDependencies(string $name): bool
    {
        $dependencies = $this->getDependencies($name);

        foreach ($dependencies as $dependency) {
            if (!$this->isEnabled($dependency)) {
                return false;
            }
        }

        return true;
    }
}

