<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::where('organization_id', Auth::user()->organization_id);

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
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $documents = $query->with(['createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get categories for filter
        $categories = Document::where('organization_id', Auth::user()->organization_id)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('Compliance/Documents/Index', [
            'documents' => $documents,
            'filters' => $request->only(['status', 'category', 'search']),
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return Inertia::render('Compliance/Documents/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'status' => 'required|in:draft,active,archived',
        ]);

        $document = Document::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'created_by_id' => Auth::id(),
            ...$validated,
        ]);

        return redirect()->route('compliance.documents.show', $document->id)->with('message', 'Document created successfully');
    }

    public function show($id)
    {
        $document = Document::where('organization_id', Auth::user()->organization_id)
            ->with(['createdBy', 'versions', 'attachments'])
            ->findOrFail($id);

        return Inertia::render('Compliance/Documents/Show', [
            'document' => $document,
        ]);
    }

    public function edit($id)
    {
        $document = Document::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        return Inertia::render('Compliance/Documents/Edit', [
            'document' => $document,
        ]);
    }

    public function update(Request $request, $id)
    {
        $document = Document::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'status' => 'required|in:draft,active,archived',
        ]);

        $document->update($validated);

        return redirect()->route('compliance.documents.show', $document->id)->with('message', 'Document updated successfully');
    }

    public function destroy($id)
    {
        $document = Document::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $document->delete();

        return redirect()->route('compliance.documents.index')->with('message', 'Document deleted successfully');
    }
}

