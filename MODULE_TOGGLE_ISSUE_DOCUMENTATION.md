# Module Toggle Issue - External Assistance Documentation

## Problem Statement

When a user toggles a module to disabled in the Settings → Modules page, the toggle switch immediately shows it as disabled, but then it reverts back to enabled within a few seconds. The module does not stay disabled.

## Expected Behavior

1. User clicks toggle switch to disable a module
2. Module should stay disabled
3. Navigation should update immediately to remove the module
4. Module state should persist across page refreshes

## Current Behavior

1. User clicks toggle switch to disable a module
2. Module shows as disabled briefly
3. Module reverts back to enabled automatically
4. Navigation may or may not update correctly

## Architecture Overview

- **Backend**: Laravel PHP with ModuleManager service
- **Frontend**: React with Inertia.js
- **State Management**: React useState with optimistic updates
- **Module Storage**: JSON files in `app/Modules/{ModuleName}/module.json`

## Relevant Code Files

### 1. Backend Controller: `app/Http/Controllers/ModuleController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Support\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class ModuleController extends Controller
{
    protected ModuleManager $moduleManager;

    public function __construct(ModuleManager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * Get all modules for display
     */
    public function index()
    {
        $modules = $this->moduleManager->all();
        
        // Format modules for frontend
        $formattedModules = [];
        foreach ($modules as $name => $module) {
            $formattedModules[] = [
                'name' => $name,
                'display_name' => $module['config']['name'] ?? $name,
                'description' => $module['config']['description'] ?? '',
                'version' => $module['version'],
                'enabled' => $module['enabled'],
                'author' => $module['config']['author'] ?? 'Unknown',
                'features' => $module['config']['features'] ?? [],
                'suitable_for' => $module['config']['suitable_for'] ?? [],
                'dependencies' => $module['config']['dependencies'] ?? [],
            ];
        }

        return Inertia::render('Settings/Modules', [
            'modules' => $formattedModules,
        ]);
    }

    /**
     * Toggle module enable/disable
     */
    public function toggle(Request $request, $moduleName)
    {
        $module = $this->moduleManager->all()[$moduleName] ?? null;

        if (!$module) {
            return back()->withErrors(['error' => 'Module not found']);
        }

        // Check dependencies if disabling
        if ($module['enabled']) {
            // Check if other modules depend on this one
            $dependentModules = [];
            foreach ($this->moduleManager->all() as $name => $otherModule) {
                $deps = $otherModule['config']['dependencies'] ?? [];
                if (in_array($moduleName, $deps) && $otherModule['enabled']) {
                    $dependentModules[] = $otherModule['config']['name'] ?? $name;
                }
            }

            if (!empty($dependentModules)) {
                return back()->withErrors([
                    'error' => 'Cannot disable this module. The following modules depend on it: ' . implode(', ', $dependentModules)
                ]);
            }
        } else {
            // Check if dependencies are satisfied
            $dependencies = $module['config']['dependencies'] ?? [];
            foreach ($dependencies as $dep) {
                if (!$this->moduleManager->isEnabled($dep)) {
                    return back()->withErrors([
                        'error' => "Cannot enable this module. Required dependency '{$dep}' is not enabled."
                    ]);
                }
            }
        }

        try {
            if ($module['enabled']) {
                $this->moduleManager->disable($moduleName);
                $message = 'Module disabled successfully. The page will refresh to apply changes.';
            } else {
                $this->moduleManager->enable($moduleName);
                $message = 'Module enabled successfully. The page will refresh to apply changes.';
            }

            // Clear application cache to ensure fresh module state
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            
            return back()->with('message', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to toggle module: ' . $e->getMessage()]);
        }
    }

    /**
     * Get all modules as JSON (for API calls)
     */
    public function getAllModules()
    {
        // Force fresh discovery by clearing cache first
        $this->moduleManager->discover();
        $modules = $this->moduleManager->all();
        
        $formattedModules = [];
        foreach ($modules as $name => $module) {
            $formattedModules[] = [
                'name' => $name,
                'display_name' => $module['config']['name'] ?? $name,
                'description' => $module['config']['description'] ?? '',
                'version' => $module['version'],
                'enabled' => $module['enabled'],
                'author' => $module['config']['author'] ?? 'Unknown',
                'features' => $module['config']['features'] ?? [],
                'suitable_for' => $module['config']['suitable_for'] ?? [],
                'dependencies' => $module['config']['dependencies'] ?? [],
            ];
        }

        return response()->json(['modules' => $formattedModules]);
    }

    /**
     * Get modules for navigation dropdown
     */
    public function getModulesForNavigation()
    {
        $modules = $this->moduleManager->enabled();
        
        $formattedModules = [];
        foreach ($modules as $name => $module) {
            $config = $module['config'];
            
            // Determine the main route for each module
            $route = $this->getModuleRoute($name, $config);
            
            if ($route) {
                $formattedModules[] = [
                    'name' => $config['name'] ?? $name,
                    'description' => $config['description'] ?? '',
                    'route' => $route,
                    'icon' => $this->getModuleIcon($name),
                ];
            }
        }

        return response()->json($formattedModules);
    }

    /**
     * Get the main route for a module
     */
    protected function getModuleRoute(string $name, array $config): ?string
    {
        // Define module routes - check config first, then fallback to defaults
        if (isset($config['main_route'])) {
            return $config['main_route'];
        }
        
        $routes = [
            'Retail' => '/pos',
            'Consulting' => '/consulting/projects',
            'Finance' => '/money',
            'ProjectManagement' => '/consulting/projects', // Merged into Consulting
        ];

        return $routes[$name] ?? null;
    }

    /**
     * Get icon for module
     */
    protected function getModuleIcon(string $name): string
    {
        $icons = [
            'Retail' => 'sales',
            'Consulting' => 'consulting',
            'Finance' => 'money',
            'ProjectManagement' => 'consulting',
        ];

        return $icons[$name] ?? 'settings';
    }
}
```

### 2. Module Manager Service: `app/Support/ModuleManager.php`

```php
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
        
        File::put($configPath, json_encode($config, JSON_PRETTY_PRINT));
        
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
        
        File::put($configPath, json_encode($config, JSON_PRETTY_PRINT));
        
        // Clear cache and re-discover to ensure fresh state
        $this->modules = [];
        $this->discover();
        
        return true;
    }

    // ... other methods ...
}
```

### 3. Frontend Component: `resources/js/Pages/Settings/Modules.jsx`

```jsx
import { Head, router, usePage } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card } from '@/Components/ui/Card';
import { Toggle } from '@/Components/ui/Toggle';
import { Package, CheckCircle2, XCircle, Info } from 'lucide-react';
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function ModulesSettings({ modules: initialModules }) {
    const { flash } = usePage().props;
    const [modules, setModules] = useState(initialModules);
    const [togglingModules, setTogglingModules] = useState({});
    const [lastToggledModule, setLastToggledModule] = useState(null);
    const [lastToggledTime, setLastToggledTime] = useState(0);

    // Update modules when props change, but skip if we just toggled a module
    useEffect(() => {
        // Don't reset if we just toggled a module (within last 2 seconds)
        const timeSinceToggle = Date.now() - lastToggledTime;
        if (timeSinceToggle < 2000 && lastToggledModule) {
            return;
        }
        setModules(initialModules);
    }, [initialModules, lastToggledModule, lastToggledTime]);

    const handleToggle = async (moduleName) => {
        const module = modules.find(m => m.name === moduleName);
        if (!module) return;

        const newEnabledState = !module.enabled;
        
        // Track which module we're toggling and when
        setLastToggledModule(moduleName);
        setLastToggledTime(Date.now());
        
        // Optimistically update UI
        setModules(prev => prev.map(m => 
            m.name === moduleName ? { ...m, enabled: newEnabledState } : m
        ));
        setTogglingModules(prev => ({ ...prev, [moduleName]: true }));
        
        try {
            // Use axios directly instead of router.post to avoid Inertia reloading props
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            await axios.post(`/modules/${moduleName}/toggle`, {}, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            // Dispatch custom event to notify navigation
            window.dispatchEvent(new CustomEvent('moduleToggled', {
                detail: { moduleName, enabled: newEnabledState }
            }));

            // Small delay to ensure file write completes
            await new Promise(resolve => setTimeout(resolve, 100));

            // Fetch all modules (not just enabled) to get accurate state
            const response = await axios.get('/api/modules/all', {
                params: { _t: Date.now() } // Cache busting
            });
            if (response.data && response.data.modules) {
                setModules(response.data.modules);
            }
        } catch (error) {
            // Revert on error
            setModules(prev => prev.map(m => 
                m.name === moduleName ? { ...m, enabled: !newEnabledState } : m
            ));
            console.error('Failed to toggle module:', error);
            // Clear toggle tracking on error so state can reset
            setLastToggledModule(null);
            setLastToggledTime(0);
        } finally {
            setTogglingModules(prev => ({ ...prev, [moduleName]: false }));
        }
    };

    const successMessage = flash?.message;
    const errorMessage = flash?.error;

    return (
        <SectionLayout sectionName="Settings">
            <Head title="Modules" />
            <div className="max-w-6xl mx-auto">
                {/* ... UI code ... */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {modules.map((module) => (
                        <Card key={module.name} className="p-6 hover:shadow-lg transition-shadow">
                            {/* ... module card content ... */}
                            
                            {/* Toggle Switch */}
                            <div className="pt-4 border-t border-gray-200">
                                <div className="flex items-center justify-between">
                                    <div className="flex-1">
                                        <label className="text-sm font-medium text-gray-700 cursor-pointer" onClick={() => !togglingModules[module.name] && handleToggle(module.name)}>
                                            {module.enabled ? 'Enabled' : 'Disabled'}
                                        </label>
                                        {togglingModules[module.name] && (
                                            <p className="text-xs text-gray-500 mt-1">Updating...</p>
                                        )}
                                    </div>
                                    <Toggle
                                        checked={module.enabled}
                                        onChange={() => handleToggle(module.name)}
                                        disabled={togglingModules[module.name]}
                                    />
                                </div>
                            </div>
                        </Card>
                    ))}
                </div>
            </div>
        </SectionLayout>
    );
}
```

### 4. Routes: `routes/web.php` (relevant section)

```php
// Module Settings
Route::get('/settings/modules', [\App\Http\Controllers\ModuleController::class, 'index'])->name('settings.modules');
Route::post('/modules/{module}/toggle', [\App\Http\Controllers\ModuleController::class, 'toggle'])->name('modules.toggle');
Route::get('/api/modules/all', [\App\Http\Controllers\ModuleController::class, 'getAllModules'])->name('api.modules.all');
Route::get('/api/modules/navigation', [\App\Http\Controllers\ModuleController::class, 'getModulesForNavigation'])->name('api.modules.navigation');
```

### 5. Toggle Component: `resources/js/Components/ui/Toggle.jsx`

```jsx
import { cn } from '@/lib/utils';

export function Toggle({ checked, onChange, disabled = false, className, ...props }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            disabled={disabled}
            onClick={onChange}
            className={cn(
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                checked ? 'bg-teal-600' : 'bg-gray-200',
                className
            )}
            {...props}
        >
            <span
                className={cn(
                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                    checked ? 'translate-x-6' : 'translate-x-1'
                )}
            />
        </button>
    );
}
```

### 6. Example Module JSON: `app/Modules/Retail/module.json`

```json
{
  "name": "Retail",
  "alias": "retail",
  "description": "Point of Sale (POS) and retail management system with sales, returns, and register sessions",
  "version": "1.0.0",
  "enabled": true,
  "dependencies": [],
  "author": "Penda Digital",
  "main_route": "/pos",
  "keywords": [
    "pos",
    "retail",
    "sales",
    "point-of-sale",
    "register",
    "cash-register",
    "sales-returns",
    "inventory"
  ],
  "providers": [
    "App\\Modules\\Retail\\Providers\\RetailServiceProvider"
  ],
  "features": [
    "point_of_sale",
    "sales_management",
    "sales_returns",
    "register_sessions",
    "receipts",
    "barcode_scanning",
    "product_search",
    "customer_search",
    "quick_expense"
  ],
  "suitable_for": [
    "retail",
    "stores",
    "shops",
    "furniture",
    "electronics",
    "clothing",
    "grocery",
    "pharmacy"
  ]
}
```

## Debugging Steps Taken

1. ✅ Added cache clearing in ModuleManager after enable/disable
2. ✅ Added forced fresh discovery in getAllModules()
3. ✅ Changed from router.post to axios.post to avoid Inertia prop reloading
4. ✅ Added toggle tracking to prevent useEffect from resetting state
5. ✅ Added delay before fetching modules to ensure file write completes
6. ✅ Added cache busting parameter to API request
7. ✅ Added JSON parsing error handling
8. ✅ Cleared Laravel config and route cache after toggle

## Potential Issues

1. **File Permissions**: The web server might not have write permissions to `app/Modules/{ModuleName}/module.json`
2. **File System Caching**: PHP's `file_get_contents()` might be caching file contents
3. **Race Condition**: The frontend might be fetching modules before the file write completes
4. **Inertia.js Interference**: Even with axios, Inertia might be reloading props somehow
5. **ModuleManager Instance**: Different instances might be caching different states
6. **OPcache**: PHP OPcache might be caching the file contents

## Testing Commands

```bash
# Check file permissions
ls -la app/Modules/Retail/module.json

# Check if file is actually being written
cat app/Modules/Retail/module.json | grep enabled

# Test toggle via tinker
php artisan tinker
>>> $mm = new \App\Support\ModuleManager();
>>> $mm->disable('Retail');
>>> $mm->all()['Retail']['enabled']; // Should return false
```

## Questions for External Assistance

1. Why might the module state revert after being disabled?
2. Is there a better way to handle state persistence in this architecture?
3. Should we use a database instead of JSON files for module state?
4. Are there any Laravel/Inertia.js specific issues that could cause this?
5. How can we ensure file writes are fully completed before reading?

## Environment

- Laravel 11.x
- Inertia.js (React)
- PHP 8.2+
- File-based module configuration (JSON files)

