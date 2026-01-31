import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { ArrowLeft, Download, AlertTriangle, Calendar, TrendingUp } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, Legend } from 'recharts';
import { TimePeriodSelector } from '@/Components/ui/TimePeriodSelector';
import { StatCard } from '@/Components/ui';
import { exportToPdf } from '@/utils/exportPdf';
import { useCurrency } from '@/hooks/useCurrency';

export default function ReportsLiabilities({ 
    totalLiabilities, 
    overdueAmount, 
    upcomingAmount,
    bills,
    billsByVendor,
    billsByCategory,
    timelineData,
    liabilities30Days,
    liabilities60Days,
    liabilities90Days,
    period = 'month', 
    filters 
}) {
    const [currentPeriod, setCurrentPeriod] = useState(period);
    const [isExporting, setIsExporting] = useState(false);
    const { formatCurrency, formatNumber, symbol } = useCurrency();

    // Better color palette for pie chart
    const COLORS = [
        '#ef4444', // Red
        '#f97316', // Orange
        '#f59e0b', // Amber
        '#eab308', // Yellow
        '#84cc16', // Lime
        '#10b981', // Green
        '#14b8a6', // Teal
        '#06b6d4', // Cyan
        '#3b82f6', // Blue
        '#8b5cf6', // Purple
    ];

    const handlePeriodChange = (newPeriod) => {
        setCurrentPeriod(newPeriod);
        if (newPeriod !== 'custom') {
            router.get('/reports/liabilities', { period: newPeriod }, { preserveState: true, preserveScroll: true });
        }
    };

    const handleCustomDateChange = (field, value) => {
        const params = {
            period: 'custom',
            date_from: field === 'date_from' ? value : filters.date_from,
            date_to: field === 'date_to' ? value : filters.date_to,
        };
        router.get('/reports/liabilities', params, { preserveState: true, preserveScroll: true });
    };

    const handleExport = () => {
        setIsExporting(true);
        exportToPdf('report-content', `liabilities-report-${currentPeriod}-${new Date().toISOString().split('T')[0]}`);
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
            <Head title="Liabilities Report" />
            <div className="max-w-7xl mx-auto" id="report-content">
                {/* Header */}
                <div className="mb-8">
                    <Link href="/reports" className="inline-flex items-center gap-2 text-gray-600 hover:text-teal-600 mb-4 font-medium transition-colors">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Reports
                    </Link>
                    <div className="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h1 className="text-3xl font-black text-gray-900 tracking-tight">Liabilities Report</h1>
                            <p className="text-gray-500 mt-1">Outstanding bills and liabilities - {getPeriodLabel()}</p>
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
                        title="Total Liabilities"
                        value={formatCurrency(totalLiabilities)}
                        subtitle={`${bills.length} bills`}
                        icon={AlertTriangle}
                        className="border-red-200"
                    />
                    <StatCard
                        title="Overdue"
                        value={formatCurrency(overdueAmount)}
                        subtitle={`${bills.filter(b => b.is_overdue).length} bills`}
                        icon={AlertTriangle}
                        className="border-red-300 bg-red-50"
                    />
                    <StatCard
                        title="Upcoming"
                        value={formatCurrency(upcomingAmount)}
                        subtitle={`${bills.filter(b => !b.is_overdue).length} bills`}
                        icon={Calendar}
                        className="border-orange-200"
                    />
                </div>

                {/* 30/60/90 Day Timeline */}
                <div className="bg-gradient-to-br from-red-50 to-orange-50 rounded-2xl border border-red-200/50 p-6 mb-8">
                    <h3 className="text-lg font-bold text-gray-900 mb-6">Liabilities Timeline</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="bg-white rounded-xl p-6 shadow-sm border border-red-100">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-sm font-medium text-gray-600">30 Days</span>
                                <div className="w-3 h-3 rounded-full bg-red-500"></div>
                            </div>
                            <p className="text-3xl font-black text-gray-900 mb-1">
                                {formatCurrency(liabilities30Days || 0)}
                            </p>
                            <p className="text-xs text-gray-500">
                                {totalLiabilities > 0 ? ((liabilities30Days / totalLiabilities) * 100).toFixed(1) : 0}% of total
                            </p>
                        </div>
                        <div className="bg-white rounded-xl p-6 shadow-sm border border-red-100">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-sm font-medium text-gray-600">60 Days</span>
                                <div className="w-3 h-3 rounded-full bg-orange-500"></div>
                            </div>
                            <p className="text-3xl font-black text-gray-900 mb-1">
                                {formatCurrency(liabilities60Days || 0)}
                            </p>
                            <p className="text-xs text-gray-500">
                                {totalLiabilities > 0 ? ((liabilities60Days / totalLiabilities) * 100).toFixed(1) : 0}% of total
                            </p>
                        </div>
                        <div className="bg-white rounded-xl p-6 shadow-sm border border-red-100">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-sm font-medium text-gray-600">90 Days</span>
                                <div className="w-3 h-3 rounded-full bg-amber-500"></div>
                            </div>
                            <p className="text-3xl font-black text-gray-900 mb-1">
                                {formatCurrency(liabilities90Days || 0)}
                            </p>
                            <p className="text-xs text-gray-500">
                                {totalLiabilities > 0 ? ((liabilities90Days / totalLiabilities) * 100).toFixed(1) : 0}% of total
                            </p>
                        </div>
                    </div>
                </div>

                {/* Charts */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    {/* Timeline Chart */}
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">12-Week Liability Timeline</h3>
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
                                <Bar dataKey="amount" fill="#ef4444" radius={[8, 8, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Bills by Vendor - Improved Pie Chart */}
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Liabilities by Vendor</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <PieChart>
                                <Pie
                                    data={billsByVendor}
                                    dataKey="total_due"
                                    nameKey="vendor_name"
                                    cx="50%"
                                    cy="50%"
                                    outerRadius={90}
                                    innerRadius={30}
                                    paddingAngle={2}
                                    label={({ percent }) => `${(percent * 100).toFixed(0)}%`}
                                    labelLine={false}
                                >
                                    {billsByVendor.map((entry, index) => (
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
                                        const entry = billsByVendor.find(e => e.vendor_name === value);
                                        return entry ? `${value}: ${formatCurrency(entry.total_due)}` : value;
                                    }}
                                    wrapperStyle={{ fontSize: '12px', paddingTop: '20px' }}
                                />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Bills by Category */}
                {billsByCategory && billsByCategory.length > 0 && (
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6 mb-8">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Liabilities by Category</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {billsByCategory.map((category, index) => (
                                <div key={index} className="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="text-sm font-medium text-gray-600">
                                            {category.category || 'Uncategorized'}
                                        </span>
                                        <div 
                                            className="w-3 h-3 rounded-full" 
                                            style={{ backgroundColor: COLORS[index % COLORS.length] }}
                                        ></div>
                                    </div>
                                    <p className="text-2xl font-black text-gray-900 mb-1">
                                        {formatCurrency(category.total_due || 0)}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {category.bill_count || 0} bills
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Bills Table */}
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6 mb-8">
                    <h3 className="text-lg font-bold text-gray-900 mb-4">Outstanding Bills</h3>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Bill Number</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Vendor</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Category</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Due Date</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Amount Due</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {bills.map((bill) => (
                                    <tr key={bill.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 text-sm font-medium text-gray-900">{bill.bill_number}</td>
                                        <td className="px-4 py-3 text-sm text-gray-900">{bill.vendor_name}</td>
                                        <td className="px-4 py-3 text-sm text-gray-500">{bill.category || '-'}</td>
                                        <td className="px-4 py-3 text-sm text-gray-900">
                                            {bill.due_date_formatted || '-'}
                                            {bill.is_overdue && (
                                                <span className="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded">Overdue</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-sm">
                                            <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                                bill.payment_status === 'unpaid' ? 'bg-red-100 text-red-700' :
                                                bill.payment_status === 'partially_paid' ? 'bg-yellow-100 text-yellow-700' :
                                                'bg-green-100 text-green-700'
                                            }`}>
                                                {bill.payment_status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-sm font-semibold text-gray-900 text-right">
                                            {symbol} {formatNumber(bill.amount_due)}
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

