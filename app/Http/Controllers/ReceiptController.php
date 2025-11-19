<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReceiptController extends Controller
{
    public function show($id)
    {
        $receipt = Receipt::where('organization_id', Auth::user()->organization_id)
            ->with(['payment.customer', 'payment.allocations.invoice'])
            ->findOrFail($id);

        return Inertia::render('Receipts/Show', [
            'receipt' => $receipt,
        ]);
    }
}
