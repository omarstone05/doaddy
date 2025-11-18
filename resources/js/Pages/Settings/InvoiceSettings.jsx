import React, { useEffect, useState } from 'react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Building2, FileText, CreditCard, Settings as SettingsIcon, Save, Info } from 'lucide-react';

export default function InvoiceSettings({ organization, invoiceSettings, bankDetails }) {
    const { flash } = usePage().props;
    const [activeTab, setActiveTab] = useState('company');

    const { data, setData, post, processing, errors, reset } = useForm({
        // Company Details
        company_name: invoiceSettings?.company_name || '',
        company_address: invoiceSettings?.company_address || '',
        company_city: invoiceSettings?.company_city || '',
        company_state: invoiceSettings?.company_state || '',
        company_postal_code: invoiceSettings?.company_postal_code || '',
        company_country: invoiceSettings?.company_country || '',
        company_phone: invoiceSettings?.company_phone || '',
        company_email: invoiceSettings?.company_email || '',
        company_website: invoiceSettings?.company_website || '',
        company_tax_id: invoiceSettings?.company_tax_id || '',
        company_registration_number: invoiceSettings?.company_registration_number || '',
        
        // Invoice Preferences
        invoice_prefix: invoiceSettings?.invoice_prefix || 'INV',
        quote_prefix: invoiceSettings?.quote_prefix || 'QUO',
        default_payment_terms: invoiceSettings?.default_payment_terms || 'Net 30',
        default_due_days: invoiceSettings?.default_due_days || 30,
        invoice_footer_text: invoiceSettings?.invoice_footer_text || '',
        invoice_notes: invoiceSettings?.invoice_notes || '',
        invoice_terms: invoiceSettings?.invoice_terms || '',
        
        // Quote Preferences
        quote_footer_text: invoiceSettings?.quote_footer_text || '',
        quote_notes: invoiceSettings?.quote_notes || '',
        quote_terms: invoiceSettings?.quote_terms || '',
        quote_validity_days: invoiceSettings?.quote_validity_days || 30,
        
        // Additional Details
        additional_details: invoiceSettings?.additional_details || '',
        show_tax_id: invoiceSettings?.show_tax_id ?? true,
        show_registration_number: invoiceSettings?.show_registration_number ?? false,
        show_website: invoiceSettings?.show_website ?? false,
        
        // Bank Details
        bank_name: bankDetails?.bank_name || '',
        account_name: bankDetails?.account_name || '',
        account_number: bankDetails?.account_number || '',
        routing_number: bankDetails?.routing_number || '',
        swift_code: bankDetails?.swift_code || '',
        iban: bankDetails?.iban || '',
        branch: bankDetails?.branch || '',
        branch_address: bankDetails?.branch_address || '',
        show_bank_name: bankDetails?.show_bank_name ?? true,
        show_account_name: bankDetails?.show_account_name ?? true,
        show_account_number: bankDetails?.show_account_number ?? true,
        show_routing_number: bankDetails?.show_routing_number ?? false,
        show_swift_code: bankDetails?.show_swift_code ?? false,
        show_iban: bankDetails?.show_iban ?? false,
        show_branch: bankDetails?.show_branch ?? false,
    });

    useEffect(() => {
        if (invoiceSettings || bankDetails) {
            reset({
                company_name: invoiceSettings?.company_name || '',
                company_address: invoiceSettings?.company_address || '',
                company_city: invoiceSettings?.company_city || '',
                company_state: invoiceSettings?.company_state || '',
                company_postal_code: invoiceSettings?.company_postal_code || '',
                company_country: invoiceSettings?.company_country || '',
                company_phone: invoiceSettings?.company_phone || '',
                company_email: invoiceSettings?.company_email || '',
                company_website: invoiceSettings?.company_website || '',
                company_tax_id: invoiceSettings?.company_tax_id || '',
                company_registration_number: invoiceSettings?.company_registration_number || '',
                invoice_prefix: invoiceSettings?.invoice_prefix || 'INV',
                quote_prefix: invoiceSettings?.quote_prefix || 'QUO',
                default_payment_terms: invoiceSettings?.default_payment_terms || 'Net 30',
                default_due_days: invoiceSettings?.default_due_days || 30,
                invoice_footer_text: invoiceSettings?.invoice_footer_text || '',
                invoice_notes: invoiceSettings?.invoice_notes || '',
                invoice_terms: invoiceSettings?.invoice_terms || '',
                quote_footer_text: invoiceSettings?.quote_footer_text || '',
                quote_notes: invoiceSettings?.quote_notes || '',
                quote_terms: invoiceSettings?.quote_terms || '',
                quote_validity_days: invoiceSettings?.quote_validity_days || 30,
                additional_details: invoiceSettings?.additional_details || '',
                show_tax_id: invoiceSettings?.show_tax_id ?? true,
                show_registration_number: invoiceSettings?.show_registration_number ?? false,
                show_website: invoiceSettings?.show_website ?? false,
                bank_name: bankDetails?.bank_name || '',
                account_name: bankDetails?.account_name || '',
                account_number: bankDetails?.account_number || '',
                routing_number: bankDetails?.routing_number || '',
                swift_code: bankDetails?.swift_code || '',
                iban: bankDetails?.iban || '',
                branch: bankDetails?.branch || '',
                branch_address: bankDetails?.branch_address || '',
                show_bank_name: bankDetails?.show_bank_name ?? true,
                show_account_name: bankDetails?.show_account_name ?? true,
                show_account_number: bankDetails?.show_account_number ?? true,
                show_routing_number: bankDetails?.show_routing_number ?? false,
                show_swift_code: bankDetails?.show_swift_code ?? false,
                show_iban: bankDetails?.show_iban ?? false,
                show_branch: bankDetails?.show_branch ?? false,
            });
        }
    }, [invoiceSettings, bankDetails]);

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/settings/invoices', {
            preserveScroll: true,
            onSuccess: () => {
                // Success handled by flash message
            },
        });
    };

    const tabs = [
        { id: 'company', name: 'Company Details', icon: Building2 },
        { id: 'invoice', name: 'Invoice Preferences', icon: FileText },
        { id: 'quote', name: 'Quote Preferences', icon: FileText },
        { id: 'banking', name: 'Banking Details', icon: CreditCard },
        { id: 'additional', name: 'Additional', icon: SettingsIcon },
    ];

    return (
        <SectionLayout sectionName="Settings">
            <Head title="Invoice Settings" />

            <div className="py-12">
                <div className="max-w-6xl mx-auto px-4">
                    <div className="bg-white rounded-lg shadow-lg">
                        {/* Header */}
                        <div className="border-b border-gray-200 px-6 py-4">
                            <h2 className="text-2xl font-bold text-gray-900">Invoice & Quote Settings</h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Configure your company details, invoice preferences, and banking information
                            </p>
                        </div>

                        {/* Success/Error Messages */}
                        {flash?.success && (
                            <div className="mx-6 mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
                                <p className="text-green-800 font-semibold">{flash.success}</p>
                            </div>
                        )}
                        
                        {errors && Object.keys(errors).length > 0 && (
                            <div className="mx-6 mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                                <p className="text-red-800 font-semibold mb-2">Please fix the following errors:</p>
                                <ul className="list-disc list-inside text-red-700">
                                    {Object.entries(errors).map(([key, message]) => (
                                        <li key={key}>{message}</li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {/* Tabs */}
                        <div className="border-b border-gray-200 px-6">
                            <nav className="-mb-px flex space-x-8">
                                {tabs.map((tab) => (
                                    <button
                                        key={tab.id}
                                        onClick={() => setActiveTab(tab.id)}
                                        className={`
                                            flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm
                                            ${activeTab === tab.id
                                                ? 'border-teal-500 text-teal-600'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            }
                                        `}
                                    >
                                        <tab.icon className="h-5 w-5" />
                                        {tab.name}
                                    </button>
                                ))}
                            </nav>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6">
                            {/* Company Details Tab */}
                            {activeTab === 'company' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-semibold mb-4">Company Information</h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="md:col-span-2">
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Company Name *
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.company_name}
                                                    onChange={(e) => setData('company_name', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                    required
                                                />
                                            </div>

                                            <div className="md:col-span-2">
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Address
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.company_address}
                                                    onChange={(e) => setData('company_address', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    City
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.company_city}
                                                    onChange={(e) => setData('company_city', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    State/Province
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.company_state}
                                                    onChange={(e) => setData('company_state', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Postal Code
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.company_postal_code}
                                                    onChange={(e) => setData('company_postal_code', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Country
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.company_country}
                                                    onChange={(e) => setData('company_country', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Phone
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.company_phone}
                                                    onChange={(e) => setData('company_phone', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Email
                                                </label>
                                                <input
                                                    type="email"
                                                    value={data.company_email}
                                                    onChange={(e) => setData('company_email', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Website
                                                </label>
                                                <input
                                                    type="url"
                                                    value={data.company_website}
                                                    onChange={(e) => setData('company_website', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                    placeholder="https://example.com"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Tax ID / VAT Number
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.company_tax_id}
                                                    onChange={(e) => setData('company_tax_id', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Registration Number
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.company_registration_number}
                                                    onChange={(e) => setData('company_registration_number', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Invoice Preferences Tab */}
                            {activeTab === 'invoice' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-semibold mb-4">Invoice Configuration</h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Invoice Prefix
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.invoice_prefix}
                                                    onChange={(e) => setData('invoice_prefix', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                    placeholder="INV"
                                                />
                                                <p className="text-xs text-gray-500 mt-1">Used in invoice numbering (e.g., INV-20250118-0001)</p>
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Default Payment Terms
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.default_payment_terms}
                                                    onChange={(e) => setData('default_payment_terms', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                    placeholder="Net 30"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Default Due Days
                                                </label>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    max="365"
                                                    value={data.default_due_days}
                                                    onChange={(e) => setData('default_due_days', parseInt(e.target.value) || 30)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                                <p className="text-xs text-gray-500 mt-1">Number of days until payment is due</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="text-lg font-semibold mb-4">Default Invoice Content</h3>
                                        <div className="space-y-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Footer Text
                                                </label>
                                                <textarea
                                                    value={data.invoice_footer_text}
                                                    onChange={(e) => setData('invoice_footer_text', e.target.value)}
                                                    rows={3}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                    placeholder="Thank you for your business!"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Default Notes
                                                </label>
                                                <textarea
                                                    value={data.invoice_notes}
                                                    onChange={(e) => setData('invoice_notes', e.target.value)}
                                                    rows={3}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                    placeholder="Additional notes to appear on invoices"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Terms & Conditions
                                                </label>
                                                <textarea
                                                    value={data.invoice_terms}
                                                    onChange={(e) => setData('invoice_terms', e.target.value)}
                                                    rows={4}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                    placeholder="Payment terms, late fees, etc."
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Quote Preferences Tab */}
                            {activeTab === 'quote' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-semibold mb-4">Quote Configuration</h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Quote Prefix
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.quote_prefix}
                                                    onChange={(e) => setData('quote_prefix', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                    placeholder="QUO"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Quote Validity (Days)
                                                </label>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    max="365"
                                                    value={data.quote_validity_days}
                                                    onChange={(e) => setData('quote_validity_days', parseInt(e.target.value) || 30)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                                <p className="text-xs text-gray-500 mt-1">Default number of days quotes remain valid</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="text-lg font-semibold mb-4">Default Quote Content</h3>
                                        <div className="space-y-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Footer Text
                                                </label>
                                                <textarea
                                                    value={data.quote_footer_text}
                                                    onChange={(e) => setData('quote_footer_text', e.target.value)}
                                                    rows={3}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                    placeholder="Thank you for considering our services!"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Default Notes
                                                </label>
                                                <textarea
                                                    value={data.quote_notes}
                                                    onChange={(e) => setData('quote_notes', e.target.value)}
                                                    rows={3}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                    placeholder="Additional notes to appear on quotes"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Terms & Conditions
                                                </label>
                                                <textarea
                                                    value={data.quote_terms}
                                                    onChange={(e) => setData('quote_terms', e.target.value)}
                                                    rows={4}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                    placeholder="Terms and conditions for quotes"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Banking Details Tab */}
                            {activeTab === 'banking' && (
                                <div className="space-y-6">
                                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                        <div className="flex items-start gap-2">
                                            <Info className="h-5 w-5 text-blue-600 mt-0.5" />
                                            <div>
                                                <p className="text-sm text-blue-800">
                                                    Banking details will appear on invoices and quotes based on the visibility settings below.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="text-lg font-semibold mb-4">Bank Account Information</h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Bank Name
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.bank_name}
                                                    onChange={(e) => setData('bank_name', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Account Name
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.account_name}
                                                    onChange={(e) => setData('account_name', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Account Number
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.account_number}
                                                    onChange={(e) => setData('account_number', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Routing Number / Sort Code
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.routing_number}
                                                    onChange={(e) => setData('routing_number', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    SWIFT Code
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.swift_code}
                                                    onChange={(e) => setData('swift_code', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    IBAN
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.iban}
                                                    onChange={(e) => setData('iban', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Branch Name
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.branch}
                                                    onChange={(e) => setData('branch', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div className="md:col-span-2">
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Branch Address
                                                </label>
                                                <textarea
                                                    value={data.branch_address}
                                                    onChange={(e) => setData('branch_address', e.target.value)}
                                                    rows={2}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="text-lg font-semibold mb-4">Visibility Settings</h3>
                                        <p className="text-sm text-gray-600 mb-4">Choose which banking details to display on invoices and quotes</p>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={data.show_bank_name}
                                                    onChange={(e) => setData('show_bank_name', e.target.checked)}
                                                    className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                                />
                                                <span className="text-sm text-gray-700">Show Bank Name</span>
                                            </label>

                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={data.show_account_name}
                                                    onChange={(e) => setData('show_account_name', e.target.checked)}
                                                    className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                                />
                                                <span className="text-sm text-gray-700">Show Account Name</span>
                                            </label>

                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={data.show_account_number}
                                                    onChange={(e) => setData('show_account_number', e.target.checked)}
                                                    className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                                />
                                                <span className="text-sm text-gray-700">Show Account Number</span>
                                            </label>

                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={data.show_routing_number}
                                                    onChange={(e) => setData('show_routing_number', e.target.checked)}
                                                    className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                                />
                                                <span className="text-sm text-gray-700">Show Routing Number</span>
                                            </label>

                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={data.show_swift_code}
                                                    onChange={(e) => setData('show_swift_code', e.target.checked)}
                                                    className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                                />
                                                <span className="text-sm text-gray-700">Show SWIFT Code</span>
                                            </label>

                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={data.show_iban}
                                                    onChange={(e) => setData('show_iban', e.target.checked)}
                                                    className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                                />
                                                <span className="text-sm text-gray-700">Show IBAN</span>
                                            </label>

                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={data.show_branch}
                                                    onChange={(e) => setData('show_branch', e.target.checked)}
                                                    className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                                />
                                                <span className="text-sm text-gray-700">Show Branch</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Additional Tab */}
                            {activeTab === 'additional' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-semibold mb-4">Additional Details</h3>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Additional Information
                                            </label>
                                            <textarea
                                                value={data.additional_details}
                                                onChange={(e) => setData('additional_details', e.target.value)}
                                                rows={5}
                                                className="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                placeholder="Any additional information to display on invoices and quotes"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="text-lg font-semibold mb-4">Display Preferences</h3>
                                        <div className="space-y-3">
                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={data.show_tax_id}
                                                    onChange={(e) => setData('show_tax_id', e.target.checked)}
                                                    className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                                />
                                                <span className="text-sm text-gray-700">Show Tax ID / VAT Number on invoices</span>
                                            </label>

                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={data.show_registration_number}
                                                    onChange={(e) => setData('show_registration_number', e.target.checked)}
                                                    className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                                />
                                                <span className="text-sm text-gray-700">Show Registration Number on invoices</span>
                                            </label>

                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={data.show_website}
                                                    onChange={(e) => setData('show_website', e.target.checked)}
                                                    className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                                />
                                                <span className="text-sm text-gray-700">Show Website on invoices</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Submit Button */}
                            <div className="mt-8 pt-6 border-t border-gray-200 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center gap-2 px-6 py-3 bg-teal-600 text-white font-medium rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                >
                                    <Save className="h-5 w-5" />
                                    {processing ? 'Saving...' : 'Save Settings'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </SectionLayout>
    );
}

