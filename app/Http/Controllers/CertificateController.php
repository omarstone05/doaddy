<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = Certificate::where('organization_id', Auth::user()->organization_id);

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
                  ->orWhere('certificate_number', 'like', "%{$search}%")
                  ->orWhere('issuing_authority', 'like', "%{$search}%");
            });
        }

        $certificates = $query->with(['createdBy'])
            ->orderBy('expiry_date')
            ->paginate(20);

        // Get categories for filter
        $categories = Certificate::where('organization_id', Auth::user()->organization_id)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('Compliance/Certificates/Index', [
            'certificates' => $certificates,
            'filters' => $request->only(['status', 'category', 'expiring', 'search']),
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return Inertia::render('Compliance/Certificates/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'certificate_number' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'issuing_authority' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'status' => 'required|in:active,expired,pending_renewal',
            'notes' => 'nullable|string',
        ]);

        $certificate = Certificate::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'created_by_id' => Auth::id(),
            ...$validated,
        ]);

        return redirect()->route('compliance.certificates.index')->with('message', 'Certificate created successfully');
    }

    public function edit($id)
    {
        $certificate = Certificate::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        return Inertia::render('Compliance/Certificates/Edit', [
            'certificate' => $certificate,
        ]);
    }

    public function update(Request $request, $id)
    {
        $certificate = Certificate::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'certificate_number' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'issuing_authority' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'status' => 'required|in:active,expired,pending_renewal',
            'notes' => 'nullable|string',
        ]);

        $certificate->update($validated);

        return redirect()->route('compliance.certificates.index')->with('message', 'Certificate updated successfully');
    }

    public function destroy($id)
    {
        $certificate = Certificate::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $certificate->delete();

        return redirect()->route('compliance.certificates.index')->with('message', 'Certificate deleted successfully');
    }
}

