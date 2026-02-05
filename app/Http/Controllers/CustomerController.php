<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPersona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    protected function getOrganizationId(): ?string
    {
        $user = auth()->user();
        return session('current_organization_id')
            ?? $user->organization_id
            ?? $user->organizations()->first()?->id;
    }

    public function index(Request $request): Response
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization.');
        }
        $customers = Customer::where('organization_id', $organizationId)
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

        $personas = CustomerPersona::where('organization_id', $organizationId)
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
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization.');
        }
        $personas = CustomerPersona::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->get();

        return Inertia::render('Customers/Create', [
            'personas' => $personas,
        ]);
    }

    public function store(Request $request)
    {
        try {
            // Convert empty strings to null for optional fields (avoids validation failures)
            $request->merge([
                'customer_persona_id' => $request->filled('customer_persona_id') ? $request->customer_persona_id : null,
                'credit_limit' => $request->filled('credit_limit') ? $request->credit_limit : null,
                'custom_payment_days' => $request->filled('custom_payment_days') ? $request->custom_payment_days : null,
            ]);

            $organizationId = $this->getOrganizationId();
            if (!$organizationId) {
                return back()->withErrors([
                    'error' => 'You must belong to an organization to create customers.',
                ])->withInput();
            }

            $validated = $request->validate([
                'customer_persona_id' => [
                    'nullable',
                    'exists:customer_personas,id',
                    function ($attribute, $value, $fail) use ($organizationId) {
                        if ($value && !\App\Models\CustomerPersona::where('id', $value)->where('organization_id', $organizationId)->exists()) {
                            $fail('The selected persona does not belong to your organization.');
                        }
                    },
                ],
                'type' => 'required|in:individual,business',
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
                'website' => 'nullable|string|max:255',
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
                'organization_id' => $organizationId,
                'status' => 'active',
            ]));

            return redirect()->route('customers.show', $customer)
                ->with('success', 'Customer created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Database\QueryException $e) {
            $errorMessage = 'Failed to create customer due to a database error.';
            
            // Check for specific constraint violations
            if (str_contains($e->getMessage(), 'customer_code')) {
                $errorMessage = 'Customer code already exists. Please try again.';
            } elseif (str_contains($e->getMessage(), 'organization_id')) {
                $errorMessage = 'Invalid organization. Please ensure you belong to an organization.';
            } elseif (str_contains($e->getMessage(), 'foreign key')) {
                $errorMessage = 'Invalid reference. Please check your organization and persona settings.';
            }
            
            // In debug mode, show the actual error
            if (config('app.debug')) {
                $errorMessage .= ' Error: ' . $e->getMessage();
            }
            
            Log::error('Database error creating customer: ' . $e->getMessage(), [
                'exception' => $e,
                'sql' => $e->getSql() ?? null,
                'bindings' => $e->getBindings() ?? null,
                'request_data' => $request->except(['password']),
            ]);
            
            return back()->withErrors([
                'error' => $errorMessage,
            ])->withInput();
        } catch (\Exception $e) {
            $errorMessage = 'Failed to create customer. Please try again.';
            
            // In debug mode, show the actual error
            if (config('app.debug')) {
                $errorMessage .= ' Error: ' . $e->getMessage();
            }
            
            Log::error('Create customer failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password']),
            ]);
            
            return back()->withErrors([
                'error' => $errorMessage,
            ])->withInput();
        }
    }

    public function show(Customer $customer): Response
    {
        // Ensure customer belongs to user's organization
        if ($customer->organization_id !== $this->getOrganizationId()) {
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
        if ($customer->organization_id !== $this->getOrganizationId()) {
            abort(403, 'Unauthorized access to customer.');
        }

        $personas = CustomerPersona::where('organization_id', $this->getOrganizationId())
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
        if ($customer->organization_id !== $this->getOrganizationId()) {
            abort(403, 'Unauthorized access to customer.');
        }

        // Convert empty strings to null for optional fields
        $request->merge([
            'customer_persona_id' => $request->filled('customer_persona_id') ? $request->customer_persona_id : null,
            'credit_limit' => $request->filled('credit_limit') ? $request->credit_limit : null,
            'custom_payment_days' => $request->filled('custom_payment_days') ? $request->custom_payment_days : null,
        ]);

        $organizationId = $this->getOrganizationId();
        $validated = $request->validate([
            'customer_persona_id' => [
                'nullable',
                'exists:customer_personas,id',
                function ($attribute, $value, $fail) use ($organizationId) {
                    if ($value && $organizationId && !\App\Models\CustomerPersona::where('id', $value)->where('organization_id', $organizationId)->exists()) {
                        $fail('The selected persona does not belong to your organization.');
                    }
                },
            ],
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
        if ($customer->organization_id !== $this->getOrganizationId()) {
            abort(403, 'Unauthorized access to customer.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    public function personas(): Response
    {
        $personas = CustomerPersona::where('organization_id', $this->getOrganizationId())
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
            'organization_id' => $this->getOrganizationId(),
            'is_active' => true,
        ]));

        return redirect()->route('customers.personas')
            ->with('success', 'Customer persona created successfully.');
    }

    /**
     * Quick create customer via API (for modals and quick forms)
     */
    public function quickCreate(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
                'company_name' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'tax_id' => 'nullable|string|max:100',
            ]);

            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be authenticated to create customers.',
                ], 401);
            }

            $organizationId = $this->getOrganizationId();
            
            if (!$organizationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must belong to an organization to create customers.',
                ], 403);
            }

            // Determine customer type based on company_name
            $type = !empty($validated['company_name']) ? 'business' : 'individual';
            
            // Use company_name as name if provided, otherwise use name
            $customerName = !empty($validated['company_name']) 
                ? $validated['company_name'] 
                : $validated['name'];

            $customer = Customer::create([
                'organization_id' => $organizationId,
                'type' => $type,
                'name' => $customerName,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'tax_id' => $validated['tax_id'] ?? null,
                'billing_address' => $validated['address'] ?? null,
                'payment_terms' => 'net_30', // Default payment terms
                'currency' => 'ZMW', // Default currency
                'status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'customer' => $customer->load('persona'),
                'message' => 'Customer created successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            $errorMessage = 'Failed to create customer due to a database error.';
            
            // Check for specific constraint violations
            if (str_contains($e->getMessage(), 'customer_code')) {
                $errorMessage = 'Customer code already exists. Please try again.';
            } elseif (str_contains($e->getMessage(), 'organization_id')) {
                $errorMessage = 'Invalid organization. Please ensure you belong to an organization.';
            } elseif (str_contains($e->getMessage(), 'foreign key')) {
                $errorMessage = 'Invalid reference. Please check your organization settings.';
            }
            
            // In debug mode, show the actual error
            if (config('app.debug')) {
                $errorMessage .= ' Error: ' . $e->getMessage();
            }
            
            Log::error('Database error creating customer (quick): ' . $e->getMessage(), [
                'exception' => $e,
                'sql' => $e->getSql() ?? null,
                'bindings' => $e->getBindings() ?? null,
                'request_data' => $request->except(['password']),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        } catch (\Exception $e) {
            $errorMessage = 'Failed to create customer. Please try again.';
            
            // In debug mode, show the actual error
            if (config('app.debug')) {
                $errorMessage .= ' Error: ' . $e->getMessage();
            }
            
            Log::error('Quick create customer failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password']),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }

    /**
     * Search customers via API
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $organizationId = $this->getOrganizationId();

        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'You must belong to an organization to search customers.',
            ], 403);
        }

        $customers = Customer::where('organization_id', $organizationId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('customer_code', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'customers' => $customers,
        ]);
    }
}
