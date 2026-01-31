import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { ArrowLeft, Download, DollarSign, FileText, TrendingUp } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, Legend } from 'recharts';
import { TimePeriodSelector } from '@/Components/ui/TimePeriodSelector';
import { StatCard } from '@/Components/ui';
import { exportToPdf } from '@/utils/exportPdf';
import { useCurrency } from '@/hooks/useCurrency';

export default function ReportsProjectedIncome({ 
    totalProjectedIncome, 
    invoiceProjected, 
    quotationProjected,
    invoices,
    quotations,
    incomeByCustomer,
    timelineData,
    projected30Days,
    projected60Days,
    projected90Days,
    period = 'month', 
    filters 
}) {
    const [currentPeriod, setCurrentPeriod] = useState(period);
    const [isExporting, setIsExporting] = useState(false);
    const { formatCurrency, formatNumber, symbol } = useCurrency();

    // Better color palette for pie chart
    const COLORS = [
        '#14b8a6', // Teal
        '#3b82f6', // Blue
        '#8b5cf6', // Purple
        '#f59e0b', // Amber
        '#10b981', // Green
        '#ef4444', // Red
        '#06b6d4', // Cyan
        '#a855f7', // Violet
        '#f97316', // Orange
        '#84cc16', // Lime
    ];

    const handlePeriodChange = (newPeriod) => {
        setCurrentPeriod(newPeriod);
        if (newPeriod !== 'custom') {
            router.get('/reports/projected-income', { period: newPeriod }, { preserveState: true, preserveScroll: true });
        }
    };

    const handleCustomDateChange = (field, value) => {
        const params = {
            period: 'custom',
            date_from: field === 'date_from' ? value : filters.date_from,
            date_to: field === 'date_to' ? value : filters.date_to,
        };
        router.get('/reports/projected-income', params, { preserveState: true, preserveScroll: true });
    };

    const handleExport = () => {
        setIsExporting(true);
        exportToPdf('report-content', `projected-income-report-${currentPeriod}-${new Date().toISOString().split('T')[0]}`);
        setTimeout(() => setIsExporting(false), 2000);
    };

    const getPeriodLabel = () => {
        switch (currentPeriod) {
            case 'week': return 'This week';
            case 'month': return 'This month';
            case 'year': return 'This year';
            case 'custom': return `${filters.date_from} - ${filters.date_to}`;
            default: return 'This month';
        }
    };

    return (
        <SectionLayout sectionName="Reports">
            <Head title="Projected Income Report" />
            <div className="max-w-7xl mx-auto" id="report-content">
                {/* Header */}
                <div className="mb-8">
                    <Link href="/reports" className="inline-flex items-center gap-2 text-gray-600 hover:text-teal-600 mb-4 font-medium transition-colors">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Reports
                    </Link>
                    <div className="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h1 className="text-3xl font-black text-gray-900 tracking-tight">Projected Income Report</h1>
                            <p className="text-gray-500 mt-1">Expected income from invoices and quotations - {getPeriodLabel()}</p>
                        </div>
                        <div className="flex items-center gap-4">
                            <TimePeriodSelector
                                selected={currentPeriod}
                                onChange={handlePeriodChange}
                                dateFrom={filters.date_from}
                                dateTo={filters.date_to}
                                onCustomDateChange={handleCustomDateChange}
                            />
                            <button
                                onClick={handleExport}
                                disabled={isExporting}
                                className="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors flex items-center gap-2 disabled:opacity-50"
                            >
                                <Download className="h-4 w-4" />
                                {isExporting ? 'Exporting...' : 'Export PDF'}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Stat Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <StatCard
                        title="Total Projected Income"
                        value={formatCurrency(totalProjectedIncome)}
                        subtitle={`${invoices.length + quotations.length} items`}
                        icon={TrendingUp}
                        className="border-teal-200"
                    />
                    <StatCard
                        title="From Invoices"
                        value={formatCurrency(invoiceProjected)}
                        subtitle={`${invoices.length} invoices`}
                        icon={FileText}
                        className="border-blue-200"
                    />
                    <StatCard
                        title="From Quotations"
                        value={formatCurrency(quotationProjected)}
                        subtitle={`${quotations.length} quotations`}
                        icon={DollarSign}
                        className="border-green-200"
                    />
                </div>

                {/* 30/60/90 Day Timeline */}
                <div className="bg-gradient-to-br from-teal-50 to-blue-50 rounded-2xl border border-teal-200/50 p-6 mb-8">
                    <h3 className="text-lg font-bold text-gray-900 mb-6">Projected Income Timeline</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="bg-white rounded-xl p-6 shadow-sm border border-teal-100">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-sm font-medium text-gray-600">30 Days</span>
                                <div className="w-3 h-3 rounded-full bg-teal-500"></div>
                            </div>
                            <p className="text-3xl font-black text-gray-900 mb-1">
                                {formatCurrency(projected30Days || 0)}
                            </p>
                            <p className="text-xs text-gray-500">
                                {((projected30Days / totalProjectedIncome) * 100).toFixed(1)}% of total
                            </p>
                        </div>
                        <div className="bg-white rounded-xl p-6 shadow-sm border border-teal-100">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-sm font-medium text-gray-600">60 Days</span>
                                <div className="w-3 h-3 rounded-full bg-blue-500"></div>
                            </div>
                            <p className="text-3xl font-black text-gray-900 mb-1">
                                {formatCurrency(projected60Days || 0)}
                            </p>
                            <p className="text-xs text-gray-500">
                                {((projected60Days / totalProjectedIncome) * 100).toFixed(1)}% of total
                            </p>
                        </div>
                        <div className="bg-white rounded-xl p-6 shadow-sm border border-teal-100">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-sm font-medium text-gray-600">90 Days</span>
                                <div className="w-3 h-3 rounded-full bg-purple-500"></div>
                            </div>
                            <p className="text-3xl font-black text-gray-900 mb-1">
                                {formatCurrency(projected90Days || 0)}
                            </p>
                            <p className="text-xs text-gray-500">
                                {((projected90Days / totalProjectedIncome) * 100).toFixed(1)}% of total
                            </p>
                        </div>
                    </div>
                </div>

                {/* Charts */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    {/* Timeline Chart */}
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">12-Week Income Projection</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <BarChart data={timelineData}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                <XAxis dataKey="week" stroke="#6b7280" fontSize={12} />
                                <YAxis stroke="#6b7280" fontSize={12} />
                                <Tooltip 
                                    formatter={(value) => `K ${formatFullAmount(value)}`}
                                    contentStyle={{ 
                                        backgroundColor: 'white', 
                                        border: '1px solid #e5e7eb',
                                        borderRadius: '8px',
                                        padding: '8px'
                                    }}
                                />
                                <Bar dataKey="amount" fill="#14b8a6" radius={[8, 8, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Income by Customer - Improved Pie Chart */}
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Projected Income by Customer</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <PieChart>
                                <Pie
                                    data={incomeByCustomer}
                                    dataKey="total_due"
                                    nameKey="customer_name"
                                    cx="50%"
                                    cy="50%"
                                    outerRadius={90}
                                    innerRadius={30}
                                    paddingAngle={2}
                                    label={({ percent }) => `${(percent * 100).toFixed(0)}%`}
                                    labelLine={false}
                                >
                                    {incomeByCustomer.map((entry, index) => (
                                        <Cell 
                                            key={`cell-${index}`} 
                                            fill={COLORS[index % COLORS.length]}
                                            stroke="#fff"
                                            strokeWidth={2}
                                        />
                                    ))}
                                </Pie>
                                <Tooltip 
                                    formatter={(value, name) => [
                                        `K ${formatFullAmount(value)}`,
                                        name
                                    ]}
                                    contentStyle={{ 
                                        backgroundColor: 'white', 
                                        border: '1px solid #e5e7eb',
                                        borderRadius: '8px',
                                        padding: '8px'
                                    }}
                                />
                                <Legend 
                                    verticalAlign="bottom" 
                                    height={36}
                                    formatter={(value) => {
                                        const entry = incomeByCustomer.find(e => e.customer_name === value);
                                        return entry ? `${value}: ${formatCurrency(entry.total_due)}` : value;
                                    }}
                                    wrapperStyle={{ fontSize: '12px', paddingTop: '20px' }}
                                />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Invoices Table */}
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6 mb-8">
                    <h3 className="text-lg font-bold text-gray-900 mb-4">Pending Invoices</h3>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Invoice Number</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Customer</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Due Date</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Amount Due</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {invoices.map((invoice) => (
                                    <tr key={invoice.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 text-sm font-medium text-gray-900">{invoice.invoice_number}</td>
                                        <td className="px-4 py-3 text-sm text-gray-900">{invoice.customer_name}</td>
                                        <td className="px-4 py-3 text-sm text-gray-900">
                                            {invoice.due_date_formatted || '-'}
                                            {invoice.is_overdue && (
                                                <span className="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded">Overdue</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-sm">
                                            <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                                invoice.status === 'overdue' ? 'bg-red-100 text-red-700' :
                                                invoice.status === 'sent' ? 'bg-yellow-100 text-yellow-700' :
                                                'bg-gray-100 text-gray-700'
                                            }`}>
                                                {invoice.status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-sm font-semibold text-gray-900 text-right">
                                            {symbol} {formatNumber(invoice.amount_due)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Quotations Table */}
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                    <h3 className="text-lg font-bold text-gray-900 mb-4">Pending Quotations</h3>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Quotation Number</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Title</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Customer/Prospect</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Valid Until</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {quotations.map((quotation) => (
                                    <tr key={quotation.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 text-sm font-medium text-gray-900">{quotation.quotation_number}</td>
                                        <td className="px-4 py-3 text-sm text-gray-900">{quotation.title}</td>
                                        <td className="px-4 py-3 text-sm text-gray-900">{quotation.customer_name}</td>
                                        <td className="px-4 py-3 text-sm text-gray-900">{quotation.valid_until_formatted || '-'}</td>
                                        <td className="px-4 py-3 text-sm">
                                            <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                                quotation.status === 'sent' ? 'bg-blue-100 text-blue-700' :
                                                quotation.status === 'viewed' ? 'bg-yellow-100 text-yellow-700' :
                                                'bg-gray-100 text-gray-700'
                                            }`}>
                                                {quotation.status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-sm font-semibold text-gray-900 text-right">
                                            {symbol} {formatNumber(quotation.total)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </SectionLayout>
    );
}

