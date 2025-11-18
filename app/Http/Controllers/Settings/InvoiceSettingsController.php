<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InvoiceSettingsController extends Controller
{
    /**
     * Show invoice settings page
     */
    public function index()
    {
        $user = Auth::user();
        $organizationId = session('current_organization_id') 
            ?? ($user->attributes['organization_id'] ?? null)
            ?? $user->organizations()->first()?->id;

        if (!$organizationId) {
            return redirect()->route('onboarding');
        }

        $organization = Organization::findOrFail($organizationId);

        // Get invoice settings from organization settings JSON
        $settings = $organization->settings ?? [];
        $invoiceSettings = $settings['invoice'] ?? [];
        $bankDetails = $settings['bank_details'] ?? [];

        return Inertia::render('Settings/InvoiceSettings', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'logo' => $organization->logo,
            ],
            'invoiceSettings' => [
                // Company/Business Details
                'company_name' => $invoiceSettings['company_name'] ?? $organization->name,
                'company_address' => $invoiceSettings['company_address'] ?? '',
                'company_city' => $invoiceSettings['company_city'] ?? '',
                'company_state' => $invoiceSettings['company_state'] ?? '',
                'company_postal_code' => $invoiceSettings['company_postal_code'] ?? '',
                'company_country' => $invoiceSettings['company_country'] ?? '',
                'company_phone' => $invoiceSettings['company_phone'] ?? '',
                'company_email' => $invoiceSettings['company_email'] ?? '',
                'company_website' => $invoiceSettings['company_website'] ?? '',
                'company_tax_id' => $invoiceSettings['company_tax_id'] ?? '',
                'company_registration_number' => $invoiceSettings['company_registration_number'] ?? '',
                
                // Invoice Preferences
                'invoice_prefix' => $invoiceSettings['invoice_prefix'] ?? 'INV',
                'quote_prefix' => $invoiceSettings['quote_prefix'] ?? 'QUO',
                'default_payment_terms' => $invoiceSettings['default_payment_terms'] ?? 'Net 30',
                'default_due_days' => $invoiceSettings['default_due_days'] ?? 30,
                'invoice_footer_text' => $invoiceSettings['invoice_footer_text'] ?? '',
                'invoice_notes' => $invoiceSettings['invoice_notes'] ?? '',
                'invoice_terms' => $invoiceSettings['invoice_terms'] ?? '',
                
                // Quote Preferences
                'quote_footer_text' => $invoiceSettings['quote_footer_text'] ?? '',
                'quote_notes' => $invoiceSettings['quote_notes'] ?? '',
                'quote_terms' => $invoiceSettings['quote_terms'] ?? '',
                'quote_validity_days' => $invoiceSettings['quote_validity_days'] ?? 30,
                
                // Additional Details
                'additional_details' => $invoiceSettings['additional_details'] ?? '',
                'show_tax_id' => $invoiceSettings['show_tax_id'] ?? true,
                'show_registration_number' => $invoiceSettings['show_registration_number'] ?? false,
                'show_website' => $invoiceSettings['show_website'] ?? false,
            ],
            'bankDetails' => [
                'bank_name' => $bankDetails['bank_name'] ?? '',
                'account_name' => $bankDetails['account_name'] ?? '',
                'account_number' => $bankDetails['account_number'] ?? '',
                'routing_number' => $bankDetails['routing_number'] ?? '',
                'swift_code' => $bankDetails['swift_code'] ?? '',
                'iban' => $bankDetails['iban'] ?? '',
                'branch' => $bankDetails['branch'] ?? '',
                'branch_address' => $bankDetails['branch_address'] ?? '',
                'show_bank_name' => $bankDetails['show_bank_name'] ?? true,
                'show_account_name' => $bankDetails['show_account_name'] ?? true,
                'show_account_number' => $bankDetails['show_account_number'] ?? true,
                'show_routing_number' => $bankDetails['show_routing_number'] ?? false,
                'show_swift_code' => $bankDetails['show_swift_code'] ?? false,
                'show_iban' => $bankDetails['show_iban'] ?? false,
                'show_branch' => $bankDetails['show_branch'] ?? false,
            ],
        ]);
    }

    /**
     * Update invoice settings
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $organizationId = session('current_organization_id') 
            ?? ($user->attributes['organization_id'] ?? null)
            ?? $user->organizations()->first()?->id;

        if (!$organizationId) {
            return response()->json(['error' => 'Organization not found'], 404);
        }

        // Verify user has access
        if (!$user->belongsToOrganization($organizationId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $organization = Organization::findOrFail($organizationId);

        $validated = $request->validate([
            // Company Details
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string|max:500',
            'company_city' => 'nullable|string|max:100',
            'company_state' => 'nullable|string|max:100',
            'company_postal_code' => 'nullable|string|max:20',
            'company_country' => 'nullable|string|max:100',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'company_website' => 'nullable|url|max:255',
            'company_tax_id' => 'nullable|string|max:100',
            'company_registration_number' => 'nullable|string|max:100',
            
            // Invoice Preferences
            'invoice_prefix' => 'nullable|string|max:10',
            'quote_prefix' => 'nullable|string|max:10',
            'default_payment_terms' => 'nullable|string|max:100',
            'default_due_days' => 'nullable|integer|min:1|max:365',
            'invoice_footer_text' => 'nullable|string|max:1000',
            'invoice_notes' => 'nullable|string|max:2000',
            'invoice_terms' => 'nullable|string|max:2000',
            
            // Quote Preferences
            'quote_footer_text' => 'nullable|string|max:1000',
            'quote_notes' => 'nullable|string|max:2000',
            'quote_terms' => 'nullable|string|max:2000',
            'quote_validity_days' => 'nullable|integer|min:1|max:365',
            
            // Additional Details
            'additional_details' => 'nullable|string|max:2000',
            'show_tax_id' => 'boolean',
            'show_registration_number' => 'boolean',
            'show_website' => 'boolean',
            
            // Bank Details
            'bank_name' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'routing_number' => 'nullable|string|max:50',
            'swift_code' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:50',
            'branch' => 'nullable|string|max:255',
            'branch_address' => 'nullable|string|max:500',
            'show_bank_name' => 'boolean',
            'show_account_name' => 'boolean',
            'show_account_number' => 'boolean',
            'show_routing_number' => 'boolean',
            'show_swift_code' => 'boolean',
            'show_iban' => 'boolean',
            'show_branch' => 'boolean',
        ]);

        // Get existing settings
        $settings = $organization->settings ?? [];

        // Update invoice settings
        $settings['invoice'] = [
            'company_name' => $validated['company_name'] ?? null,
            'company_address' => $validated['company_address'] ?? null,
            'company_city' => $validated['company_city'] ?? null,
            'company_state' => $validated['company_state'] ?? null,
            'company_postal_code' => $validated['company_postal_code'] ?? null,
            'company_country' => $validated['company_country'] ?? null,
            'company_phone' => $validated['company_phone'] ?? null,
            'company_email' => $validated['company_email'] ?? null,
            'company_website' => $validated['company_website'] ?? null,
            'company_tax_id' => $validated['company_tax_id'] ?? null,
            'company_registration_number' => $validated['company_registration_number'] ?? null,
            'invoice_prefix' => $validated['invoice_prefix'] ?? null,
            'quote_prefix' => $validated['quote_prefix'] ?? null,
            'default_payment_terms' => $validated['default_payment_terms'] ?? null,
            'default_due_days' => $validated['default_due_days'] ?? null,
            'invoice_footer_text' => $validated['invoice_footer_text'] ?? null,
            'invoice_notes' => $validated['invoice_notes'] ?? null,
            'invoice_terms' => $validated['invoice_terms'] ?? null,
            'quote_footer_text' => $validated['quote_footer_text'] ?? null,
            'quote_notes' => $validated['quote_notes'] ?? null,
            'quote_terms' => $validated['quote_terms'] ?? null,
            'quote_validity_days' => $validated['quote_validity_days'] ?? null,
            'additional_details' => $validated['additional_details'] ?? null,
            'show_tax_id' => $validated['show_tax_id'] ?? true,
            'show_registration_number' => $validated['show_registration_number'] ?? false,
            'show_website' => $validated['show_website'] ?? false,
        ];

        // Update bank details
        $settings['bank_details'] = [
            'bank_name' => $validated['bank_name'] ?? null,
            'account_name' => $validated['account_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'routing_number' => $validated['routing_number'] ?? null,
            'swift_code' => $validated['swift_code'] ?? null,
            'iban' => $validated['iban'] ?? null,
            'branch' => $validated['branch'] ?? null,
            'branch_address' => $validated['branch_address'] ?? null,
            'show_bank_name' => $validated['show_bank_name'] ?? true,
            'show_account_name' => $validated['show_account_name'] ?? true,
            'show_account_number' => $validated['show_account_number'] ?? true,
            'show_routing_number' => $validated['show_routing_number'] ?? false,
            'show_swift_code' => $validated['show_swift_code'] ?? false,
            'show_iban' => $validated['show_iban'] ?? false,
            'show_branch' => $validated['show_branch'] ?? false,
        ];

        // Save settings
        $organization->settings = $settings;
        $organization->save();

        return redirect()->back()->with('success', 'Invoice settings updated successfully.');
    }
}
