import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
  Receipt, 
  Globe, 
  TrendingUp, 
  TrendingDown, 
  AlertCircle,
  CheckCircle2,
  RefreshCw,
  ArrowRight,
  Settings,
  FileText,
  DollarSign,
  Calendar,
} from 'lucide-react';

// Stat Card Component
const StatCard = ({ title, value, subtitle, icon: Icon, trend, color = 'teal' }) => {
  const colorClasses = {
    teal: 'bg-teal-50 text-teal-600 border-teal-100',
    green: 'bg-green-50 text-green-600 border-green-100',
    red: 'bg-red-50 text-red-600 border-red-100',
    blue: 'bg-blue-50 text-blue-600 border-blue-100',
  };

  return (
    <div className="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg transition-shadow">
      <div className="flex items-start justify-between">
        <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${colorClasses[color]}`}>
          <Icon size={24} />
        </div>
        {trend !== undefined && (
          <div className={`flex items-center gap-1 text-sm ${trend >= 0 ? 'text-green-600' : 'text-red-600'}`}>
            {trend >= 0 ? <TrendingUp size={16} /> : <TrendingDown size={16} />}
            <span>{Math.abs(trend)}%</span>
          </div>
        )}
      </div>
      <div className="mt-4">
        <h3 className="text-sm font-medium text-gray-500">{title}</h3>
        <p className="text-2xl font-bold text-gray-900 mt-1">{value}</p>
        {subtitle && <p className="text-sm text-gray-500 mt-1">{subtitle}</p>}
      </div>
    </div>
  );
};

// Tax Rate Row Component
const TaxRateRow = ({ rate, onEdit }) => (
  <tr className="border-b border-gray-50 hover:bg-gray-50 transition-colors">
    <td className="py-4 px-4">
      <div className="flex items-center gap-3">
        <div className={`w-2 h-2 rounded-full ${rate.is_active ? 'bg-green-500' : 'bg-gray-300'}`} />
        <div>
          <p className="font-medium text-gray-900">{rate.name}</p>
          <p className="text-xs text-gray-500">{rate.code}</p>
        </div>
      </div>
    </td>
    <td className="py-4 px-4 text-right">
      <span className="font-semibold text-gray-900">{rate.rate}%</span>
    </td>
    <td className="py-4 px-4">
      <span className="text-sm text-gray-600">{rate.tax_type || 'VAT'}</span>
    </td>
    <td className="py-4 px-4">
      <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
        rate.is_default ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-600'
      }`}>
        {rate.is_default ? 'Default' : 'Standard'}
      </span>
    </td>
  </tr>
);

export default function TaxIndex({ 
  user,
  organization,
  taxRates = [], 
  taxSummary = {},
  supportedCountries = [],
  currentCountry = null,
  modules = {},
}) {
  const [selectedCountry, setSelectedCountry] = useState(currentCountry || 'ZM');
  const [isAutoPopulating, setIsAutoPopulating] = useState(false);

  const formatCurrency = (amount, currency = organization?.currency || 'ZMW') => {
    return new Intl.NumberFormat('en-ZM', {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: 2,
    }).format(amount || 0);
  };

  const handleAutoPopulate = async () => {
    setIsAutoPopulating(true);
    try {
      await router.post('/tax/auto-populate', { country_code: selectedCountry }, {
        preserveScroll: true,
        onSuccess: () => {
          setIsAutoPopulating(false);
        },
        onError: () => {
          setIsAutoPopulating(false);
        },
      });
    } catch (error) {
      setIsAutoPopulating(false);
    }
  };

  return (
    <AuthenticatedLayout user={user}>
      <Head title="Tax Overview" />
      
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Tax Overview</h1>
            <p className="text-gray-600 mt-1">Manage tax rates and view tax summary</p>
          </div>
          <div className="flex items-center gap-3 mt-4 md:mt-0">
            <button
              onClick={() => router.visit('/reports/tax')}
              className="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium text-gray-700"
            >
              <FileText size={18} />
              Tax Reports
            </button>
            <button
              onClick={() => router.visit('/settings?section=tax')}
              className="flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors text-sm font-medium"
            >
              <Settings size={18} />
              Tax Settings
            </button>
          </div>
        </div>

        {/* Smart Invoice Status */}
        {modules?.smart_invoice_enabled && (
          <div className="mb-6 bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center justify-between">
            <div className="flex items-center gap-3">
              <CheckCircle2 className="text-emerald-600" size={24} />
              <div>
                <p className="font-medium text-emerald-900">Smart Invoice Enabled</p>
                <p className="text-sm text-emerald-700">Invoices will include ZRA verification QR codes</p>
              </div>
            </div>
            <button
              onClick={() => router.visit('/settings?section=tax')}
              className="text-sm text-emerald-700 hover:text-emerald-800 font-medium flex items-center gap-1"
            >
              Configure <ArrowRight size={16} />
            </button>
          </div>
        )}

        {/* Tax Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <StatCard
            title="Tax Collected"
            value={formatCurrency(taxSummary?.tax_collected || 0)}
            subtitle="From invoices this period"
            icon={TrendingUp}
            color="green"
          />
          <StatCard
            title="Tax Paid"
            value={formatCurrency(taxSummary?.tax_paid || 0)}
            subtitle="On expenses this period"
            icon={TrendingDown}
            color="red"
          />
          <StatCard
            title="Net Tax Liability"
            value={formatCurrency(taxSummary?.net_liability || 0)}
            subtitle={(taxSummary?.net_liability || 0) >= 0 ? 'Owed to tax authority' : 'Refund due'}
            icon={DollarSign}
            color={(taxSummary?.net_liability || 0) >= 0 ? 'teal' : 'blue'}
          />
        </div>

        {/* Country Selection & Auto-populate */}
        <div className="bg-white rounded-2xl border border-gray-100 p-6 mb-8">
          <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">
                <Globe className="text-blue-600" size={24} />
              </div>
              <div>
                <h3 className="font-semibold text-gray-900">Tax Region</h3>
                <p className="text-sm text-gray-500">Select your country to auto-populate tax rates</p>
              </div>
            </div>
            <div className="flex items-center gap-3">
              <select
                value={selectedCountry}
                onChange={(e) => setSelectedCountry(e.target.value)}
                className="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent"
              >
                <option value="ZM">Zambia (VAT 16%)</option>
                <option value="ZA">South Africa (VAT 15%)</option>
                <option value="KE">Kenya (VAT 16%)</option>
                <option value="GB">United Kingdom (VAT 20%)</option>
                <option value="NG">Nigeria (VAT 7.5%)</option>
                <option value="GH">Ghana (VAT 15%)</option>
                <option value="BW">Botswana (VAT 14%)</option>
                <option value="US">United States (Sales Tax varies)</option>
              </select>
              <button
                onClick={handleAutoPopulate}
                disabled={isAutoPopulating}
                className="flex items-center gap-2 px-4 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors text-sm font-medium disabled:opacity-50"
              >
                {isAutoPopulating ? (
                  <>
                    <RefreshCw size={16} className="animate-spin" />
                    Populating...
                  </>
                ) : (
                  <>
                    <RefreshCw size={16} />
                    Auto-populate Rates
                  </>
                )}
              </button>
            </div>
          </div>
        </div>

        {/* Tax Rates Table */}
        <div className="bg-white rounded-2xl border border-gray-100 overflow-hidden">
          <div className="p-6 border-b border-gray-100">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-lg font-semibold text-gray-900">Tax Rates</h3>
                <p className="text-sm text-gray-500 mt-1">
                  {taxRates.length} rate{taxRates.length !== 1 ? 's' : ''} configured
                </p>
              </div>
              <button
                onClick={() => router.visit('/settings?section=tax')}
                className="text-sm text-teal-600 hover:text-teal-700 font-medium"
              >
                Manage Rates
              </button>
            </div>
          </div>
          
          {taxRates.length > 0 ? (
            <table className="w-full">
              <thead>
                <tr className="bg-gray-50 text-left">
                  <th className="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                  <th className="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Rate</th>
                  <th className="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                  <th className="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody>
                {taxRates.map((rate) => (
                  <TaxRateRow key={rate.id} rate={rate} />
                ))}
              </tbody>
            </table>
          ) : (
            <div className="p-12 text-center">
              <div className="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <Receipt className="text-gray-400" size={28} />
              </div>
              <h3 className="text-lg font-medium text-gray-900 mb-2">No Tax Rates Configured</h3>
              <p className="text-gray-500 mb-4">
                Select your country above and click "Auto-populate Rates" to get started.
              </p>
            </div>
          )}
        </div>

        {/* Period Info */}
        <div className="mt-6 flex items-center justify-center gap-2 text-sm text-gray-500">
          <Calendar size={16} />
          <span>
            Showing data for {taxSummary?.period?.start || 'current month'} to {taxSummary?.period?.end || 'now'}
          </span>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
