<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

    public function downloadPdf($id)
    {
        $receipt = Receipt::where('organization_id', Auth::user()->organization_id)
            ->with(['payment.customer', 'payment.allocations.invoice', 'payment.organization'])
            ->findOrFail($id);

        $organization = $receipt->payment->organization ?? Auth::user()->organization;
        $payment = $receipt->payment;
        
        // Convert logo to base64 for PDF compatibility
        $logoBase64 = null;
        if ($organization->logo && \Storage::disk('public')->exists($organization->logo)) {
            try {
                $logoPath = \Storage::disk('public')->path($organization->logo);
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $mimeType = mime_content_type($logoPath) ?: 'image/png';
                    $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to load organization logo for receipt PDF', [
                    'organization_id' => $organization->id,
                    'logo' => $organization->logo,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $pdfService = new \App\Services\PDF\PdfService();
        $filename = 'Receipt-' . $receipt->receipt_number . '.pdf';

        return $pdfService->download('pdf.receipt', [
            'receipt' => $receipt,
            'payment' => $payment,
            'organization' => $organization,
            'logoUrl' => $logoBase64,
        ], $filename);
    }
}
