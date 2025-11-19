<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReceiptController extends Controller
{
    public function show($id)
    {
        $receipt = Receipt::where('organization_id', Auth::user()->organization_id)
            ->with(['payment.customer', 'payment.allocations.invoice', 'payment.organization'])
            ->findOrFail($id);

        // Get organization logo URL if available
        $organization = $receipt->payment->organization ?? Auth::user()->organization;
        $logoUrl = null;
        if ($organization->logo && \Storage::disk('public')->exists($organization->logo)) {
            $logoUrl = \Storage::disk('public')->url($organization->logo);
        }

        return Inertia::render('Receipts/Show', [
            'receipt' => $receipt,
            'organization' => $organization,
            'logoUrl' => $logoUrl,
        ]);
    }
}
