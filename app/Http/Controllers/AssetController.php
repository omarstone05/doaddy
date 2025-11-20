<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AssetController extends Controller
{
    /**
     * Get current organization ID
     */
    protected function getOrganizationId()
    {
        $user = Auth::user();
        $currentOrgId = session('current_organization_id') ?? $user->current_organization_id;
        
        if ($currentOrgId) {
            return $currentOrgId;
        }
        
        // Fallback to first organization
        return $user->organizations()->first()?->id;
    }

    /**
     * Display a listing of assets
     */
    public function index(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to access assets.');
        }

        $query = Asset::where('organization_id', $organizationId)
            ->with(['assignedToUser', 'assignedToDepartment']);

        // Filters
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('category') && $request->category !== '') {
            $query->where('category', $request->category);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_number', 'like', "%{$search}%")
                  ->orWhere('asset_tag', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $assets = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get stats
        $stats = [
            'total_assets' => Asset::where('organization_id', $organizationId)->count(),
            'active_assets' => Asset::where('organization_id', $organizationId)->where('status', 'active')->count(),
            'total_value' => Asset::where('organization_id', $organizationId)->sum('current_value') ?? 0,
            'needs_maintenance' => Asset::where('organization_id', $organizationId)
                ->where('next_maintenance_date', '<=', now())
                ->where('status', 'active')
                ->count(),
        ];

        return Inertia::render('Inventory/Assets/Index', [
            'assets' => $assets,
            'stats' => $stats,
            'filters' => $request->only(['status', 'category', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new asset
     */
    public function create()
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to create assets.');
        }

        $users = User::whereHas('organizations', function ($q) use ($organizationId) {
            $q->where('organizations.id', $organizationId);
        })->get();

        $departments = Department::where('organization_id', $organizationId)->get();

        return Inertia::render('Inventory/Assets/Create', [
            'users' => $users,
            'departments' => $departments,
        ]);
    }

    /**
     * Store a newly created asset
     */
    public function store(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found for the current user.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_number' => 'nullable|string|max:255|unique:assets,asset_number',
            'asset_tag' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'purchase_order_number' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'assigned_to_user_id' => 'nullable|uuid|exists:users,id',
            'assigned_to_department_id' => 'nullable|uuid|exists:departments,id',
            'status' => 'required|in:active,inactive,maintenance,retired,disposed,lost',
            'condition' => 'required|in:excellent,good,fair,poor,needs_repair',
            'warranty_expiry' => 'nullable|date',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date',
            'maintenance_notes' => 'nullable|string',
            'depreciation_method' => 'nullable|in:straight_line,declining_balance,none',
            'useful_life_years' => 'nullable|integer|min:1',
            'salvage_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Generate asset number if not provided
        if (empty($validated['asset_number'])) {
            $validated['asset_number'] = 'AST-' . strtoupper(Str::random(8));
        }

        // Set current value to purchase price if not provided
        if (empty($validated['current_value']) && !empty($validated['purchase_price'])) {
            $validated['current_value'] = $validated['purchase_price'];
        }

        DB::beginTransaction();
        try {
            $asset = Asset::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organizationId,
                ...$validated,
            ]);

            DB::commit();

            return redirect()->route('assets.show', $asset->id)->with('message', 'Asset created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create asset', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to create asset: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified asset
     */
    public function show($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to view assets.');
        }

        $asset = Asset::where('organization_id', $organizationId)
            ->with(['assignedToUser', 'assignedToDepartment'])
            ->findOrFail($id);

        return Inertia::render('Inventory/Assets/Show', [
            'asset' => $asset,
        ]);
    }

    /**
     * Show the form for editing the specified asset
     */
    public function edit($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to edit assets.');
        }

        $asset = Asset::where('organization_id', $organizationId)->findOrFail($id);

        $users = User::whereHas('organizations', function ($q) use ($organizationId) {
            $q->where('organizations.id', $organizationId);
        })->get();

        $departments = Department::where('organization_id', $organizationId)->get();

        return Inertia::render('Inventory/Assets/Edit', [
            'asset' => $asset,
            'users' => $users,
            'departments' => $departments,
        ]);
    }

    /**
     * Update the specified asset
     */
    public function update(Request $request, $id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found for the current user.']);
        }

        $asset = Asset::where('organization_id', $organizationId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_number' => 'nullable|string|max:255|unique:assets,asset_number,' . $id,
            'asset_tag' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'purchase_order_number' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'assigned_to_user_id' => 'nullable|uuid|exists:users,id',
            'assigned_to_department_id' => 'nullable|uuid|exists:departments,id',
            'status' => 'required|in:active,inactive,maintenance,retired,disposed,lost',
            'condition' => 'required|in:excellent,good,fair,poor,needs_repair',
            'warranty_expiry' => 'nullable|date',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date',
            'maintenance_notes' => 'nullable|string',
            'depreciation_method' => 'nullable|in:straight_line,declining_balance,none',
            'useful_life_years' => 'nullable|integer|min:1',
            'salvage_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $asset->update($validated);

            DB::commit();

            return redirect()->route('assets.show', $asset->id)->with('message', 'Asset updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update asset', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to update asset: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified asset
     */
    public function destroy($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return back()->withErrors(['error' => 'No organization found for the current user.']);
        }

        $asset = Asset::where('organization_id', $organizationId)->findOrFail($id);

        DB::beginTransaction();
        try {
            $asset->delete();

            DB::commit();

            return redirect()->route('assets.index')->with('message', 'Asset deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete asset', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Failed to delete asset: ' . $e->getMessage()]);
        }
    }
}
