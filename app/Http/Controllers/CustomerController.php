<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPersona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $customers = Customer::where('organization_id', auth()->user()->organization_id)
            ->with(['persona'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('customer_code', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->persona, function ($query, $persona) {
                $query->where('customer_persona_id', $persona);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $personas = CustomerPersona::where('organization_id', auth()->user()->organization_id)
            ->where('is_active', true)
            ->get();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'personas' => $personas,
            'filters' => $request->only(['search', 'status', 'persona']),
        ]);
    }

    public function create(): Response
    {
        $personas = CustomerPersona::where('organization_id', auth()->user()->organization_id)
            ->where('is_active', true)
            ->get();

        return Inertia::render('Customers/Create', [
            'personas' => $personas,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_persona_id' => 'nullable|exists:customer_personas,id',
            'type' => 'required|in:individual,business',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'tax_id' => 'nullable|string|max:100',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'required|in:immediate,net_15,net_30,net_60,net_90,custom',
            'custom_payment_days' => 'nullable|integer|min:1',
            'currency' => 'required|string|max:3',
            'primary_contact_name' => 'nullable|string|max:255',
            'primary_contact_email' => 'nullable|email|max:255',
            'primary_contact_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $customer = Customer::create(array_merge($validated, [
            'organization_id' => auth()->user()->organization_id,
            'status' => 'active',
        ]));

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer): Response
    {
        // Ensure customer belongs to user's organization
        if ($customer->organization_id !== Auth::user()->organization_id) {
            abort(403, 'Unauthorized access to customer.');
        }

        $customer->load([
            'persona',
            'invoices' => fn($q) => $q->latest()->limit(10),
            'quotations' => fn($q) => $q->latest()->limit(5),
        ]);

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
        ]);
    }

    public function edit(Customer $customer): Response
    {
        // Ensure customer belongs to user's organization
        if ($customer->organization_id !== Auth::user()->organization_id) {
            abort(403, 'Unauthorized access to customer.');
        }

        $personas = CustomerPersona::where('organization_id', auth()->user()->organization_id)
            ->where('is_active', true)
            ->get();

        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
            'personas' => $personas,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        // Ensure customer belongs to user's organization
        if ($customer->organization_id !== Auth::user()->organization_id) {
            abort(403, 'Unauthorized access to customer.');
        }

        $validated = $request->validate([
            'customer_persona_id' => 'nullable|exists:customer_personas,id',
            'type' => 'required|in:individual,business',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'tax_id' => 'nullable|string|max:100',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'required|in:immediate,net_15,net_30,net_60,net_90,custom',
            'custom_payment_days' => 'nullable|integer|min:1',
            'currency' => 'required|string|max:3',
            'status' => 'required|in:active,inactive,blocked',
            'primary_contact_name' => 'nullable|string|max:255',
            'primary_contact_email' => 'nullable|email|max:255',
            'primary_contact_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        // Ensure customer belongs to user's organization
        if ($customer->organization_id !== Auth::user()->organization_id) {
            abort(403, 'Unauthorized access to customer.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    public function personas(): Response
    {
        $personas = CustomerPersona::where('organization_id', auth()->user()->organization_id)
            ->withCount('customers')
            ->get();

        return Inertia::render('Customers/Personas', [
            'personas' => $personas,
        ]);
    }

    public function storePersona(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'industry' => 'nullable|string|max:100',
            'size' => 'required|in:small,medium,large,enterprise',
            'payment_behavior' => 'required|in:excellent,good,fair,poor',
            'color' => 'required|string|max:7',
            'icon' => 'nullable|string|max:10',
        ]);

        CustomerPersona::create(array_merge($validated, [
            'organization_id' => auth()->user()->organization_id,
            'is_active' => true,
        ]));

        return redirect()->route('customers.personas')
            ->with('success', 'Customer persona created successfully.');
    }
}
