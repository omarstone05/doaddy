<?php

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LicenseController extends Controller
{
    public function index(Request $request)
    {
        $query = License::where('organization_id', Auth::user()->organization_id);

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('category') && $request->category !== '') {
            $query->where('category', $request->category);
        }

        if ($request->has('expiring') && $request->expiring === 'true') {
            $query->where('expiry_date', '<=', now()->addDays(30))
                  ->where('status', '!=', 'expired');
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('license_number', 'like', "%{$search}%")
                  ->orWhere('issuing_authority', 'like', "%{$search}%");
            });
        }

        $licenses = $query->with(['createdBy'])
            ->orderBy('expiry_date')
            ->paginate(20);

        // Get categories for filter
        $categories = License::where('organization_id', Auth::user()->organization_id)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('Compliance/Licenses/Index', [
            'licenses' => $licenses,
            'filters' => $request->only(['status', 'category', 'expiring', 'search']),
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return Inertia::render('Compliance/Licenses/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'license_number' => 'required|string|max:255|unique:licenses,license_number',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'issuing_authority' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'status' => 'required|in:active,expired,pending_renewal,suspended',
            'is_renewable' => 'boolean',
            'renewal_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $license = License::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'created_by_id' => Auth::id(),
            ...$validated,
        ]);

        return redirect()->route('compliance.licenses.index')->with('message', 'License created successfully');
    }

    public function edit($id)
    {
        $license = License::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        return Inertia::render('Compliance/Licenses/Edit', [
            'license' => $license,
        ]);
    }

    public function update(Request $request, $id)
    {
        $license = License::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'license_number' => 'required|string|max:255|unique:licenses,license_number,' . $license->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'issuing_authority' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'status' => 'required|in:active,expired,pending_renewal,suspended',
            'is_renewable' => 'boolean',
            'renewal_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $license->update($validated);

        return redirect()->route('compliance.licenses.index')->with('message', 'License updated successfully');
    }

    public function destroy($id)
    {
        $license = License::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $license->delete();

        return redirect()->route('compliance.licenses.index')->with('message', 'License deleted successfully');
    }
}

