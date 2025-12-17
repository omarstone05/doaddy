import React from 'react';
import { Head } from '@inertiajs/react';

export default function TaxIndex({ modules }) {
  return (
    <div className="max-w-5xl mx-auto px-4 py-8">
      <Head title="Tax Module" />
      <div className="bg-white shadow rounded-lg p-6 border border-gray-200">
        <h1 className="text-2xl font-semibold text-gray-900 mb-2">Tax Module</h1>
        <p className="text-gray-600 mb-4">
          Manage tax configuration and Smart Invoice dependencies for this organization.
        </p>
        <div className="space-y-2 text-sm text-gray-700">
          <div className="flex items-center justify-between">
            <span>Tax module enabled</span>
            <span className="font-semibold text-green-600">Yes</span>
          </div>
          <div className="flex items-center justify-between">
            <span>Smart Invoice module enabled</span>
            <span className="font-semibold">{modules?.smart_invoice_enabled ? 'Yes' : 'No'}</span>
          </div>
        </div>
      </div>
    </div>
  );
}
