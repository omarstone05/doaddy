<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CustomerController extends Controller
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

    public function index(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to access customers.');
        }

        $query = Customer::where('organization_id', $organizationId)
            ->orderBy('name');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(20);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Customers/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:255',
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
        ]);

        return redirect()->route('customers.index')->with('message', 'Customer created successfully');
    }

    public function show($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to view customers.');
        }

        $customer = Customer::where('organization_id', $organizationId)
            ->with(['attachments.uploadedBy'])
            ->findOrFail($id);

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
        ]);
    }

    public function edit($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to edit customers.');
        }

        $customer = Customer::where('organization_id', $organizationId)
            ->findOrFail($id);

        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, $id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to update customers.');
        }

        $customer = Customer::where('organization_id', $organizationId)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:255',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')->with('message', 'Customer updated successfully');
    }

    public function destroy($id)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to delete customers.');
        }

        $customer = Customer::where('organization_id', $organizationId)
            ->findOrFail($id);

        $customer->delete();

        return redirect()->route('customers.index')->with('message', 'Customer deleted successfully');
    }

    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return response()->json([]);
        }

        $customers = Customer::where('organization_id', $organizationId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        return response()->json($customers);
    }

    /**
     * Quick create customer via API (for inline creation in forms)
     */
    public function quickCreate(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return response()->json(['error' => 'You must belong to an organization'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:255',
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'customer' => $customer,
        ]);
    }
}
