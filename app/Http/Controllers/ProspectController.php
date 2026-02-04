<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProspectController extends Controller
{
    protected function getOrganizationId(): ?string
    {
        $user = Auth::user();
        return session('current_organization_id')
            ?? $user->organization_id
            ?? $user->organizations()->first()?->id;
    }

    public function index(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            abort(403, 'You must belong to an organization.');
        }
        
        $prospects = Prospect::where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Prospects/Index', [
            'prospects' => $prospects,
        ]);
    }

    public function create()
    {
        $organizationId = $this->getOrganizationId();
        $organization = Auth::user()->organizations()->where('organizations.id', $organizationId)->first();
        
        if (!$organization) {
            return redirect()->route('prospects.index')
                ->withErrors(['error' => 'No organization found. Please ensure you are part of an organization.']);
        }
        
        return Inertia::render('Prospects/Create', [
            'organizationCurrency' => $organization->currency ?? 'ZMW',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'stage' => 'required|string',
            'estimated_value' => 'nullable|numeric',
            'probability' => 'nullable|integer|min:0|max:100',
            'currency' => 'nullable|string|max:3',
        ]);

        $organizationId = $this->getOrganizationId();
        $organization = Auth::user()->organizations()->where('organizations.id', $organizationId)->first();
        
        if (!$organization) {
            return redirect()->back()
                ->withErrors(['error' => 'No organization found. Please ensure you are part of an organization.'])
                ->withInput();
        }
        
        $validated['organization_id'] = $organization->id;
        
        // Set currency from organization if not provided
        if (empty($validated['currency'])) {
            $validated['currency'] = $organization->currency ?? 'ZMW';
        }
        if (!array_key_exists('probability', $validated) || $validated['probability'] === null) {
            $validated['probability'] = 0;
        }

        try {
            Prospect::create($validated);

            return redirect()->route('prospects.index')
                ->with('success', 'Prospect created successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle unique constraint violations (e.g., duplicate prospect_code)
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                \Log::error('Prospect creation failed: duplicate prospect_code', [
                    'error' => $e->getMessage(),
                    'organization_id' => $organization->id,
                ]);
                
                // Retry once with a new code
                try {
                    // Force regeneration of prospect_code
                    unset($validated['prospect_code']);
                    Prospect::create($validated);
                    
                    return redirect()->route('prospects.index')
                        ->with('success', 'Prospect created successfully.');
                } catch (\Exception $retryException) {
                    \Log::error('Prospect creation retry failed', [
                        'error' => $retryException->getMessage(),
                    ]);
                    
                    return redirect()->back()
                        ->withErrors(['error' => 'Failed to create prospect. Please try again.'])
                        ->withInput();
                }
            }
            
            \Log::error('Prospect creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'An error occurred while creating the prospect. Please try again.'])
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Prospect creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'An error occurred while creating the prospect: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(Prospect $prospect)
    {
        return Inertia::render('Prospects/Show', [
            'prospect' => $prospect,
        ]);
    }

    public function edit(Prospect $prospect)
    {
        return Inertia::render('Prospects/Edit', [
            'prospect' => $prospect,
        ]);
    }

    public function update(Request $request, Prospect $prospect)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'stage' => 'required|string',
            'estimated_value' => 'nullable|numeric',
            'probability' => 'nullable|integer|min:0|max:100',
            'currency' => 'nullable|string|max:3',
        ]);

        $organizationId = $this->getOrganizationId();
        $organization = Auth::user()->organizations()->where('organizations.id', $organizationId)->first();
        
        // Set currency from organization if not provided
        if (empty($validated['currency'])) {
            $validated['currency'] = $organization?->currency ?? 'ZMW';
        }
        if (!array_key_exists('probability', $validated) || $validated['probability'] === null) {
            $validated['probability'] = 0;
        }

        $prospect->update($validated);

        return redirect()->route('prospects.index')
            ->with('success', 'Prospect updated successfully.');
    }

    public function destroy(Prospect $prospect)
    {
        $prospect->delete();

        return redirect()->route('prospects.index')
            ->with('success', 'Prospect deleted successfully.');
    }

    public function convertToCustomer(Request $request, Prospect $prospect)
    {
        $validated = $request->validate([
            'payment_terms' => 'nullable|string',
            'credit_limit' => 'nullable|numeric',
        ]);

        $customer = $prospect->convertToCustomer($validated);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Prospect converted to customer successfully.');
    }

    public function updateStage(Request $request, Prospect $prospect)
    {
        $validated = $request->validate([
            'stage' => 'required|string',
        ]);

        $prospect->moveToStage($validated['stage']);

        return redirect()->back()
            ->with('success', 'Prospect stage updated successfully.');
    }
}

