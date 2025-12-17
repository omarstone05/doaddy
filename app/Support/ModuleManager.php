<?php

namespace App\Support;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
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
                
                // Apply enabled-state override from storage if present
                $override = $this->getEnabledOverride($moduleName);
                if ($override !== null) {
                    $config['enabled'] = $override;
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
        return array_filter($this->all(), fn($module) => $this->isEnabled($module['name']));
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
        $module = $modules[$name] ?? null;
        if (!$module) {
            return false;
        }

        $organization = $this->getCurrentOrganization();
        if ($organization) {
            $enabledModules = $organization->enabled_modules ?? [];
            $alias = $module['config']['alias'] ?? $name;
            return in_array($alias, $enabledModules, true) || in_array($name, $enabledModules, true);
        }

        return $module['enabled'];
    }

    /**
     * Enable a module
     */
    public function enable(string $name): bool
    {
        if (!$this->exists($name)) {
            \Log::error("Module '{$name}' does not exist");
            return false;
        }

        // Ensure modules are loaded
        if (empty($this->modules)) {
            $this->discover();
        }

        $module = $this->modules[$name] ?? null;
        if (!$module) {
            \Log::error("Module '{$name}' not found in modules array");
            return false;
        }

        // Organization-scoped toggle
        $organization = $this->getCurrentOrganization();
        if ($organization) {
            $enabled = $organization->enabled_modules ?? [];
            $alias = $module['config']['alias'] ?? $name;
            if (!in_array($alias, $enabled, true)) {
                $enabled[] = $alias;
            }
            $organization->enabled_modules = array_values(array_unique($enabled));
            $organization->save();

            $this->modules = [];
            $this->discover();

            return true;
        }

        $configPath = $module['path'] . '/module.json';
        
        if (!File::exists($configPath)) {
            \Log::error("Module config file does not exist: {$configPath}");
            return false;
        }
        
        $config = json_decode(File::get($configPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::error("Failed to parse module.json for {$name}: " . json_last_error_msg());
            return false;
        }
        
        $config['enabled'] = true;
        
        // Try to write to module.json; if not possible, persist an override in storage
        try {
            // Atomic write with file locking to prevent race conditions
            $this->atomicWrite($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            \Log::info("Successfully enabled module '{$name}' by writing to {$configPath}");
        } catch (\Throwable $e) {
            \Log::warning("Failed to write module.json for {$name}, using storage override", [
                'error' => $e->getMessage(),
                'path' => $configPath,
            ]);
            // Fallback: store state override in storage
            $this->setEnabledOverride($name, true);
        }
        
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
            \Log::error("Module '{$name}' does not exist");
            return false;
        }

        // Ensure modules are loaded
        if (empty($this->modules)) {
            $this->discover();
        }

        $module = $this->modules[$name] ?? null;
        if (!$module) {
            \Log::error("Module '{$name}' not found in modules array");
            return false;
        }

        // Organization-scoped toggle
        $organization = $this->getCurrentOrganization();
        if ($organization) {
            $enabled = $organization->enabled_modules ?? [];
            $alias = $module['config']['alias'] ?? $name;
            $enabled = array_values(array_diff($enabled, [$alias, $name]));
            $organization->enabled_modules = $enabled;
            $organization->save();

            $this->modules = [];
            $this->discover();

            return true;
        }

        $configPath = $module['path'] . '/module.json';
        
        if (!File::exists($configPath)) {
            \Log::error("Module config file does not exist: {$configPath}");
            return false;
        }
        
        $config = json_decode(File::get($configPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::error("Failed to parse module.json for {$name}: " . json_last_error_msg());
            return false;
        }
        
        $config['enabled'] = false;
        
        // Try to write; fallback to storage override on failure
        try {
            // Atomic write with file locking to prevent race conditions
            $this->atomicWrite($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            \Log::info("Successfully disabled module '{$name}' by writing to {$configPath}");
        } catch (\Throwable $e) {
            \Log::warning("Failed to write module.json for {$name}, using storage override", [
                'error' => $e->getMessage(),
                'path' => $configPath,
            ]);
            $this->setEnabledOverride($name, false);
        }
        
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
     * Resolve path for module state overrides stored in storage/
     */
    protected function getOverridePath(string $name): string
    {
        $dir = storage_path('app/module_states');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        return $dir . '/' . $name . '.json';
    }

    /**
     * Persist enabled/disabled state override for a module
     */
    protected function setEnabledOverride(string $name, bool $enabled): void
    {
        $path = $this->getOverridePath($name);
        $payload = json_encode(['enabled' => $enabled], JSON_PRETTY_PRINT);
        // Best-effort write; ignore failures to avoid breaking UX
        @file_put_contents($path, $payload, LOCK_EX);
    }

    /**
     * Read enabled state override if it exists
     */
    protected function getEnabledOverride(string $name): ?bool
    {
        $path = $this->getOverridePath($name);
        if (File::exists($path)) {
            $data = json_decode(File::get($path), true);
            if (json_last_error() === JSON_ERROR_NONE && array_key_exists('enabled', $data)) {
                return (bool) $data['enabled'];
            }
        }
        return null;
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

    protected function getCurrentOrganization(): ?Organization
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $orgId = session('current_organization_id') ?? $user->current_organization_id;
        if ($orgId) {
            return Organization::find($orgId);
        }

        return null;
    }
}
