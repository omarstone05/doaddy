<?php

namespace App\Http\Controllers;

use App\Support\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
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
        // Authorization check - only organization owners/admins can toggle modules
        $user = Auth::user();
        if (!$user) {
            return $this->errorResponse($request, 'Unauthorized: You must be logged in.', 403);
        }

        // Get current organization - try multiple methods
        $organization = null;
        $currentOrgId = session('current_organization_id') ?? $user->current_organization_id;
        
        if ($currentOrgId) {
            $organization = $user->organizations()->where('organizations.id', $currentOrgId)->first();
        }
        
        // Fallback to first organization
        if (!$organization) {
            $organization = $user->organizations()->first();
        }
        
        if (!$organization) {
            return $this->errorResponse($request, 'Unauthorized: You must belong to an organization.', 403);
        }

        // Check if user has permission to manage modules (organization owner or admin)
        $userRole = $user->getRoleInOrganization($organization->id);
        
        // Allow owners and admins to toggle modules
        if (!in_array($userRole, ['owner', 'admin'])) {
            \Log::warning('Module toggle unauthorized', [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'user_role' => $userRole,
            ]);
            return $this->errorResponse($request, 'You do not have permission to manage modules. Only organization owners and admins can toggle modules.', 403);
        }

        $module = $this->moduleManager->all()[$moduleName] ?? null;

        if (!$module) {
            return $this->errorResponse($request, 'Module not found', 404);
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
                $errorMessage = 'Cannot disable this module. The following modules depend on it: ' . implode(', ', $dependentModules);
                return $this->errorResponse($request, $errorMessage, 422);
            }
        } else {
            // Check if dependencies are satisfied
            $dependencies = $module['config']['dependencies'] ?? [];
            foreach ($dependencies as $dep) {
                if (!$this->moduleManager->isEnabled($dep)) {
                    $errorMessage = "Cannot enable this module. Required dependency '{$dep}' is not enabled.";
                    return $this->errorResponse($request, $errorMessage, 422);
                }
            }
        }

        try {
            if ($module['enabled']) {
                $this->moduleManager->disable($moduleName);
                $message = 'Module disabled successfully';
            } else {
                $this->moduleManager->enable($moduleName);
                $message = 'Module enabled successfully';
            }

            // Clear application cache to ensure fresh module state
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            
            // Return JSON for AJAX requests, redirect for form submissions
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'module' => [
                        'name' => $moduleName,
                        'enabled' => !$module['enabled'],
                    ],
                ]);
            }
            
            return back()->with('message', $message);
        } catch (\Exception $e) {
            $errorMessage = 'Failed to toggle module: ' . $e->getMessage();
            return $this->errorResponse($request, $errorMessage, 500);
        }
    }

    /**
     * Return error response in appropriate format
     */
    protected function errorResponse(Request $request, string $message, int $statusCode = 400)
    {
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => $message,
            ], $statusCode);
        }
        
        return back()->withErrors(['error' => $message])->withInput();
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
                   'HR' => '/hr/dashboard',
                   'ZambianHR' => '/zambian-hr/dashboard',
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
                   'HR' => 'people',
                   'ZambianHR' => 'people',
               ];

        return $icons[$name] ?? 'settings';
    }
}

