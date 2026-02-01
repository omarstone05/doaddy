import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
  Receipt, 
  Download, 
  Calendar,
  TrendingUp,
  TrendingDown,
  DollarSign,
  FileText,
  ArrowLeft,
  Filter,
  RefreshCw,
  Printer,
} from 'lucide-react';

// Period Selector Component
const PeriodSelector = ({ value, onChange }) => (
  <select
    value={value}
    onChange={(e) => onChange(e.target.value)}
    className="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white"
  >
    <option value="this_month">This Month</option>
    <option value="last_month">Last Month</option>
    <option value="this_quarter">This Quarter</option>
    <option value="last_quarter">Last Quarter</option>
    <option value="this_year">This Year</option>
    <option value="last_year">Last Year</option>
    <option value="custom">Custom Range</option>
  </select>
);

// Summary Card Component
const SummaryCard = ({ title, value, subtitle, icon: Icon, color = 'gray' }) => {
  const colorClasses = {
    gray: 'bg-gray-50 text-gray-600',
    green: 'bg-green-50 text-green-600',
    red: 'bg-red-50 text-red-600',
    teal: 'bg-teal-50 text-teal-600',
    blue: 'bg-blue-50 text-blue-600',
  };

  return (
    <div className="bg-white rounded-xl border border-gray-100 p-5">
      <div className="flex items-center gap-3 mb-3">
        <div className={`w-10 h-10 rounded-lg flex items-center justify-center ${colorClasses[color]}`}>
          <Icon size={20} />
        </div>
        <span className="text-sm font-medium text-gray-500">{title}</span>
      </div>
      <p className="text-2xl font-bold text-gray-900">{value}</p>
      {subtitle && <p className="text-sm text-gray-500 mt-1">{subtitle}</p>}
    </div>
  );
};

// Tax Transaction Row Component
const TaxTransactionRow = ({ transaction, currency }) => {
  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-ZM', {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: 2,
    }).format(amount || 0);
  };

  return (
    <tr className="border-b border-gray-50 hover:bg-gray-50 transition-colors">
      <td className="py-4 px-4">
        <span className="text-sm text-gray-600">
          {new Date(transaction.date).toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
          })}
        </span>
      </td>
      <td className="py-4 px-4">
        <p className="font-medium text-gray-900">{transaction.description}</p>
        <p className="text-xs text-gray-500">{transaction.reference}</p>
      </td>
      <td className="py-4 px-4">
        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
          transaction.type === 'collected' 
            ? 'bg-green-100 text-green-700' 
            : 'bg-red-100 text-red-700'
        }`}>
          {transaction.type === 'collected' ? 'Collected' : 'Paid'}
        </span>
      </td>
      <td className="py-4 px-4 text-right">
        <span className="text-sm text-gray-600">{transaction.tax_rate}%</span>
      </td>
      <td className="py-4 px-4 text-right">
        <span className={`font-semibold ${
          transaction.type === 'collected' ? 'text-green-600' : 'text-red-600'
        }`}>
          {transaction.type === 'collected' ? '+' : '-'}{formatCurrency(transaction.tax_amount)}
        </span>
      </td>
    </tr>
  );
};

export default function TaxReports({ 
  user,
  organization,
  taxSummary = {},
  taxTransactions = [],
  period = 'this_month',
  periodLabel = 'This Month',
}) {
  const [selectedPeriod, setSelectedPeriod] = useState(period);
  const [isExporting, setIsExporting] = useState(false);

  const currency = organization?.currency || 'ZMW';

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-ZM', {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: 2,
    }).format(amount || 0);
  };

  const handlePeriodChange = (newPeriod) => {
    setSelectedPeriod(newPeriod);
    router.get('/reports/tax', { period: newPeriod }, { preserveState: true });
  };

  const handleExport = async (format) => {
    setIsExporting(true);
    try {
      window.location.href = `/reports/tax/export?period=${selectedPeriod}&format=${format}`;
    } finally {
      setTimeout(() => setIsExporting(false), 2000);
    }
  };

  return (
    <AuthenticatedLayout user={user}>
      <Head title="Tax Reports" />
      
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
          <div className="flex items-center gap-4">
            <button
              onClick={() => router.visit('/tax')}
              className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <ArrowLeft size={20} className="text-gray-600" />
            </button>
            <div>
              <h1 className="text-3xl font-bold text-gray-900">Tax Reports</h1>
              <p className="text-gray-600 mt-1">Tax collected and paid summary</p>
            </div>
          </div>
          <div className="flex items-center gap-3 mt-4 md:mt-0">
            <PeriodSelector value={selectedPeriod} onChange={handlePeriodChange} />
            <div className="flex items-center border border-gray-200 rounded-lg overflow-hidden">
              <button
                onClick={() => handleExport('csv')}
                disabled={isExporting}
                className="px-3 py-2 hover:bg-gray-50 transition-colors text-sm font-medium text-gray-700 flex items-center gap-1"
              >
                <Download size={16} />
                CSV
              </button>
              <div className="w-px h-8 bg-gray-200" />
              <button
                onClick={() => handleExport('pdf')}
                disabled={isExporting}
                className="px-3 py-2 hover:bg-gray-50 transition-colors text-sm font-medium text-gray-700 flex items-center gap-1"
              >
                <FileText size={16} />
                PDF
              </button>
            </div>
          </div>
        </div>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          <SummaryCard
            title="Tax Collected"
            value={formatCurrency(taxSummary?.tax_collected || 0)}
            subtitle="From invoices"
            icon={TrendingUp}
            color="green"
          />
          <SummaryCard
            title="Tax Paid"
            value={formatCurrency(taxSummary?.tax_paid || 0)}
            subtitle="On expenses"
            icon={TrendingDown}
            color="red"
          />
          <SummaryCard
            title="Net Liability"
            value={formatCurrency(taxSummary?.net_liability || 0)}
            subtitle={(taxSummary?.net_liability || 0) >= 0 ? 'Amount owed' : 'Refund due'}
            icon={DollarSign}
            color={(taxSummary?.net_liability || 0) >= 0 ? 'teal' : 'blue'}
          />
          <SummaryCard
            title="Transactions"
            value={taxTransactions?.length || 0}
            subtitle="With tax this period"
            icon={Receipt}
            color="gray"
          />
        </div>

        {/* Tax Breakdown by Rate */}
        {taxSummary?.breakdown && taxSummary.breakdown.length > 0 && (
          <div className="bg-white rounded-2xl border border-gray-100 p-6 mb-8">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Tax Breakdown by Rate</h3>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              {taxSummary.breakdown.map((item, index) => (
                <div key={index} className="bg-gray-50 rounded-xl p-4">
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-sm font-medium text-gray-600">{item.name}</span>
                    <span className="text-sm font-semibold text-gray-900">{item.rate}%</span>
                  </div>
                  <p className="text-xl font-bold text-gray-900">{formatCurrency(item.amount)}</p>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Transactions Table */}
        <div className="bg-white rounded-2xl border border-gray-100 overflow-hidden">
          <div className="p-6 border-b border-gray-100">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-lg font-semibold text-gray-900">Tax Transactions</h3>
                <p className="text-sm text-gray-500 mt-1">{periodLabel}</p>
              </div>
              <button
                onClick={() => window.print()}
                className="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors"
              >
                <Printer size={16} />
                Print
              </button>
            </div>
          </div>
          
          {taxTransactions && taxTransactions.length > 0 ? (
            <table className="w-full">
              <thead>
                <tr className="bg-gray-50 text-left">
                  <th className="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                  <th className="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                  <th className="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                  <th className="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Rate</th>
                  <th className="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Tax Amount</th>
                </tr>
              </thead>
              <tbody>
                {taxTransactions.map((transaction, index) => (
                  <TaxTransactionRow key={index} transaction={transaction} currency={currency} />
                ))}
              </tbody>
              <tfoot>
                <tr className="bg-gray-50 font-semibold">
                  <td colSpan="4" className="py-4 px-4 text-right text-gray-700">
                    Net Total:
                  </td>
                  <td className="py-4 px-4 text-right">
                    <span className={`text-lg ${(taxSummary?.net_liability || 0) >= 0 ? 'text-teal-600' : 'text-blue-600'}`}>
                      {formatCurrency(taxSummary?.net_liability || 0)}
                    </span>
                  </td>
                </tr>
              </tfoot>
            </table>
          ) : (
            <div className="p-12 text-center">
              <div className="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <Receipt className="text-gray-400" size={28} />
              </div>
              <h3 className="text-lg font-medium text-gray-900 mb-2">No Tax Transactions</h3>
              <p className="text-gray-500">
                No invoices or expenses with tax were recorded in this period.
              </p>
            </div>
          )}
        </div>

        {/* Period Info */}
        <div className="mt-6 flex items-center justify-center gap-2 text-sm text-gray-500">
          <Calendar size={16} />
          <span>
            Report period: {taxSummary?.period?.start || 'Start'} to {taxSummary?.period?.end || 'End'}
          </span>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
