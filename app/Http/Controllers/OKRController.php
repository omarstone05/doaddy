<?php

namespace App\Http\Controllers;

use App\Models\OKR;
use App\Models\KeyResult;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OKRController extends Controller
{
    public function index(Request $request)
    {
        $query = OKR::where('organization_id', Auth::user()->organization_id)
            ->with(['owner', 'keyResults']);

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('quarter') && $request->quarter !== '') {
            $query->where('quarter', $request->quarter);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $okrs = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get unique quarters for filter
        $quarters = OKR::where('organization_id', Auth::user()->organization_id)
            ->distinct()
            ->pluck('quarter')
            ->sort()
            ->values();

        return Inertia::render('Decisions/OKRs/Index', [
            'okrs' => $okrs,
            'filters' => $request->only(['status', 'quarter', 'search']),
            'quarters' => $quarters,
        ]);
    }

    public function create()
    {
        $users = User::where('organization_id', Auth::user()->organization_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Decisions/OKRs/Create', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quarter' => 'required|string|max:255',
            'status' => 'required|in:draft,active,completed,cancelled',
            'owner_id' => 'nullable|uuid|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        $okr = OKR::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'created_by_id' => Auth::id(),
            ...$validated,
        ]);

        return redirect()->route('decisions.okrs.show', $okr->id)->with('message', 'OKR created successfully');
    }

    public function show($id)
    {
        $okr = OKR::where('organization_id', Auth::user()->organization_id)
            ->with(['owner', 'createdBy', 'keyResults'])
            ->findOrFail($id);

        return Inertia::render('Decisions/OKRs/Show', [
            'okr' => $okr,
        ]);
    }

    public function edit($id)
    {
        $okr = OKR::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $users = User::where('organization_id', Auth::user()->organization_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Decisions/OKRs/Edit', [
            'okr' => $okr,
            'users' => $users,
        ]);
    }

    public function update(Request $request, $id)
    {
        $okr = OKR::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quarter' => 'required|string|max:255',
            'status' => 'required|in:draft,active,completed,cancelled',
            'owner_id' => 'nullable|uuid|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        $okr->update($validated);

        return redirect()->route('decisions.okrs.show', $okr->id)->with('message', 'OKR updated successfully');
    }

    public function destroy($id)
    {
        $okr = OKR::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $okr->delete();

        return redirect()->route('decisions.okrs.index')->with('message', 'OKR deleted successfully');
    }

    public function addKeyResult(Request $request, $id)
    {
        $okr = OKR::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:number,percentage,currency,boolean',
            'target_value' => 'required|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:255',
        ]);

        $keyResult = KeyResult::create([
            'id' => (string) Str::uuid(),
            'okr_id' => $okr->id,
            'current_value' => $validated['current_value'] ?? 0,
            'display_order' => $okr->keyResults()->max('display_order') + 1,
            ...$validated,
        ]);

        $keyResult->updateProgress();

        return back()->with('message', 'Key result added successfully');
    }

    public function updateKeyResult(Request $request, $okrId, $keyResultId)
    {
        $okr = OKR::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($okrId);

        $keyResult = KeyResult::where('okr_id', $okr->id)
            ->findOrFail($keyResultId);

        $validated = $request->validate([
            'current_value' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $keyResult->update([
            'current_value' => $validated['current_value'],
        ]);

        $keyResult->updateProgress();

        return back()->with('message', 'Key result updated successfully');
    }
}

