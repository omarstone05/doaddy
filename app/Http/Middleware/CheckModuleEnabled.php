<?php

namespace App\Http\Middleware;

use App\Support\ModuleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if a module is enabled for the current organization
 */
class CheckModuleEnabled
{
    protected ModuleManager $moduleManager;

    public function __construct(ModuleManager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (!$this->moduleManager->isEnabled($module)) {
            // Module is not enabled for this organization
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'This module is not enabled for your organization.',
                    'module' => $module,
                ], 403);
            }

            // Redirect to dashboard with error message
            return redirect()->route('dashboard')
                ->with('error', "The {$module} module is not enabled for your organization. You can enable it in Settings > Modules.");
        }

        return $next($request);
    }
}

