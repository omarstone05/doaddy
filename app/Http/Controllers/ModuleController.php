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
        ])->with([
            'modules' => $formattedModules, // Also make available for JSON response
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

