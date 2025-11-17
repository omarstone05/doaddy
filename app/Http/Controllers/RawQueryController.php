<?php

namespace App\Http\Controllers;

use App\Services\RawQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RawQueryController extends Controller
{
    protected RawQueryService $rawQueryService;

    public function __construct(RawQueryService $rawQueryService)
    {
        $this->rawQueryService = $rawQueryService;
    }

    /**
     * Show raw query management interface
     */
    public function index()
    {
        $user = Auth::user();

        // Check permissions - super admin or organization owner
        $orgId = $user->organization_id ?? session('current_organization_id');
        if (!$user->isSuperAdmin() && !($orgId && $user->isOwnerOf($orgId))) {
            abort(403, 'Access denied. Super admin or organization owner privileges required.');
        }

        $tableStructures = $this->rawQueryService->getTableStructure();

        return Inertia::render('Admin/RawQuery/Index', [
            'tableStructures' => $tableStructures,
            'organizationId' => $user->organization_id ?? session('current_organization_id'),
        ]);
    }

    /**
     * Execute a read-only query
     */
    public function execute(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:10000',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
        ]);

        $user = Auth::user();

        // Check permissions
        $orgId = $request->organization_id ?? $user->organization_id ?? session('current_organization_id');
        if (!$user->isSuperAdmin() && !($orgId && $user->isOwnerOf($orgId))) {
            return response()->json([
                'success' => false,
                'error' => 'Access denied. Super admin or organization owner privileges required.',
            ], 403);
        }

        try {
            $result = $this->rawQueryService->executeQuery(
                $request->query,
                $request->organization_id
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Execute a write operation (INSERT, UPDATE, DELETE)
     */
    public function executeWrite(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:10000',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
            'confirm' => 'required|boolean|accepted',
        ]);

        $user = Auth::user();

        // Check permissions - only super admins or organization owners
        if (!$user->isSuperAdmin() && !$user->isOwnerOfOrganization($request->organization_id ?? $user->organization_id)) {
            return response()->json([
                'success' => false,
                'error' => 'Access denied. Super admin or organization owner privileges required.',
            ], 403);
        }

        try {
            $result = $this->rawQueryService->executeWriteQuery(
                $request->query,
                $request->organization_id
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get table structure
     */
    public function getTableStructure(Request $request)
    {
        $user = Auth::user();

        // Check permissions
        if (!$user->isSuperAdmin() && !$user->isOwnerOfOrganization($user->organization_id)) {
            return response()->json([
                'success' => false,
                'error' => 'Access denied.',
            ], 403);
        }

        try {
            $structures = $this->rawQueryService->getTableStructure($request->organization_id);

            return response()->json([
                'success' => true,
                'structures' => $structures,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}

