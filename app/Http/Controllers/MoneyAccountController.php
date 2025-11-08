<?php

namespace App\Http\Controllers;

use App\Models\MoneyAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MoneyAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = MoneyAccount::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return Inertia::render('Money/Accounts/Index', [
            'accounts' => $accounts,
        ]);
    }

    public function create()
    {
        return Inertia::render('Money/Accounts/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:bank,cash,mobile_money,card,other',
            'account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'currency' => 'required|string|size:3',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        $account = MoneyAccount::create([
            'id' => (string) Str::uuid(),
            'organization_id' => Auth::user()->organization_id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'account_number' => $validated['account_number'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'currency' => $validated['currency'],
            'opening_balance' => $validated['opening_balance'],
            'current_balance' => $validated['opening_balance'],
            'is_active' => true,
        ]);

        return redirect()->route('money.accounts.index')->with('message', 'Account created successfully');
    }

    public function show($id)
    {
        $account = MoneyAccount::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);
        
        return Inertia::render('Money/Accounts/Show', [
            'account' => $account,
        ]);
    }

    public function edit($id)
    {
        $account = MoneyAccount::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);
        
        return Inertia::render('Money/Accounts/Edit', [
            'account' => $account,
        ]);
    }

    public function update(Request $request, $id)
    {
        $account = MoneyAccount::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:bank,cash,mobile_money,card,other',
            'account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'currency' => 'required|string|size:3',
            'is_active' => 'boolean',
        ]);

        $account->update($validated);

        return redirect()->route('money.accounts.index')->with('message', 'Account updated successfully');
    }

    public function destroy($id)
    {
        $account = MoneyAccount::where('organization_id', Auth::user()->organization_id)
            ->findOrFail($id);
        
        $account->update(['is_active' => false]);

        return redirect()->route('money.accounts.index')->with('message', 'Account deactivated successfully');
    }
}
