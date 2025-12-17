import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { ArrowLeft, Download, DollarSign, Wallet, CreditCard } from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';
import { TimePeriodSelector } from '@/Components/ui/TimePeriodSelector';
import { StatCard } from '@/Components/ui';
import { exportToPdf } from '@/utils/exportPdf';

export default function ReportsRevenue({ totalRevenue, salesRevenue, paymentsRevenue, otherIncome, revenueBySource, dailyRevenue, period = 'month', filters }) {
    const [currentPeriod, setCurrentPeriod] = useState(period);
    const [isExporting, setIsExporting] = useState(false);

    const formatCurrency = (amount) => {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num);
    };

    const formatFullAmount = (amount) => {
        const num = parseFloat(amount) || 0;
        return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const COLORS = ['#14b8a6', '#3b82f6', '#f59e0b', '#8b5cf6'];

    const handlePeriodChange = (newPeriod) => {
        setCurrentPeriod(newPeriod);
        if (newPeriod !== 'custom') {
            router.get('/reports/revenue', { period: newPeriod }, { preserveState: true, preserveScroll: true });
        }
    };

    const handleCustomDateChange = (field, value) => {
        const params = {
            period: 'custom',
            date_from: field === 'date_from' ? value : filters.date_from,
            date_to: field === 'date_to' ? value : filters.date_to,
        };
        router.get('/reports/revenue', params, { preserveState: true, preserveScroll: true });
    };

    const handleExport = () => {
        setIsExporting(true);
        exportToPdf('report-content', `revenue-report-${currentPeriod}-${new Date().toISOString().split('T')[0]}`);
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
            <Head title="Revenue Report" />
            <div className="max-w-7xl mx-auto">
                {/* Header */}
                <div className="mb-8">
                    <Link href="/reports" className="inline-flex items-center gap-2 text-gray-600 hover:text-teal-600 mb-4 font-medium transition-colors">
                            <ArrowLeft className="h-4 w-4" />
                            Back to Reports
                    </Link>
                    <div className="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h1 className="text-3xl font-black text-gray-900 tracking-tight">Revenue Report</h1>
                            <p className="text-gray-500 mt-1">Revenue from all sources</p>
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
                                className="flex items-center gap-2 px-4 py-2.5 bg-white/90 backdrop-blur-sm border border-gray-200 rounded-xl hover:bg-teal-50 hover:border-teal-200 font-semibold text-sm transition-all disabled:opacity-50"
                            >
                                <Download className={`h-4 w-4 ${isExporting ? 'animate-bounce' : ''}`} />
                                {isExporting ? 'Exporting...' : 'Export'}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Report Content */}
                <div id="report-content" className="bg-white rounded-2xl p-6">
                {/* Summary Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                        <StatCard
                            title="Total Revenue"
                            value={formatCurrency(totalRevenue)}
                            prefix="K "
                            subtitle={getPeriodLabel()}
                            icon={DollarSign}
                            variant="gradient-positive"
                        />
                        <StatCard
                            title="Sales Revenue"
                            value={formatCurrency(salesRevenue)}
                            prefix="K "
                            subtitle={getPeriodLabel()}
                            icon={Wallet}
                        />
                        <StatCard
                            title="Payments"
                            value={formatCurrency(paymentsRevenue)}
                            prefix="K "
                            subtitle={getPeriodLabel()}
                            icon={CreditCard}
                        />
                        <StatCard
                            title="Other Income"
                            value={formatCurrency(otherIncome)}
                            prefix="K "
                            subtitle={getPeriodLabel()}
                            icon={DollarSign}
                        />
                </div>

                {/* Charts */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div className="bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl p-6">
                            <h3 className="text-lg font-bold text-gray-900 mb-6">Revenue Trend</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={dailyRevenue}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" vertical={false} />
                                    <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fill: '#78716c', fontSize: 11 }} />
                                    <YAxis axisLine={false} tickLine={false} tick={{ fill: '#78716c', fontSize: 11 }} />
                                    <Tooltip
                                        contentStyle={{
                                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                            border: '1px solid #e7e5e4',
                                            borderRadius: '12px',
                                            boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                                        }}
                                        formatter={(value) => [`K ${value.toLocaleString()}`, 'Revenue']}
                                    />
                                    <Line type="monotone" dataKey="revenue" stroke="#14b8a6" strokeWidth={2} dot={false} name="Revenue" />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>

                        <div className="bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl p-6">
                            <h3 className="text-lg font-bold text-gray-900 mb-6">Revenue by Source</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <PieChart>
                                <Pie
                                    data={revenueBySource}
                                    dataKey="amount"
                                    nameKey="source"
                                    cx="50%"
                                    cy="50%"
                                    outerRadius={100}
                                        innerRadius={60}
                                        paddingAngle={2}
                                        label={({ source, percent }) => `${source} ${(percent * 100).toFixed(0)}%`}
                                >
                                    {revenueBySource.map((entry, index) => (
                                        <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                    ))}
                                </Pie>
                                    <Tooltip
                                        contentStyle={{
                                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                            border: '1px solid #e7e5e4',
                                            borderRadius: '12px',
                                        }}
                                        formatter={(value) => [`K ${value.toLocaleString()}`, '']}
                                    />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Revenue Breakdown Table */}
                    <div className="bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-200/50">
                            <h3 className="text-lg font-bold text-gray-900">Revenue Breakdown</h3>
                    </div>
                    <table className="w-full">
                            <thead className="bg-gray-50/80">
                                <tr>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase">Source</th>
                                    <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase">Amount</th>
                                    <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase">%</th>
                            </tr>
                        </thead>
                            <tbody className="divide-y divide-gray-100">
                            {revenueBySource.map((item, index) => {
                                    const percentage = totalRevenue > 0 ? (item.amount / totalRevenue * 100).toFixed(1) : 0;
                                    return (
                                        <tr key={index}>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-3 h-3 rounded-full" style={{ backgroundColor: COLORS[index % COLORS.length] }} />
                                                    <span className="text-sm font-semibold text-gray-900">{item.source}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-right font-bold text-teal-600">K {formatFullAmount(item.amount)}</td>
                                            <td className="px-6 py-4 text-sm text-right text-gray-600">{percentage}%</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                            <tfoot className="bg-gray-50/80 border-t border-gray-200/50">
                                <tr>
                                    <td className="px-6 py-4 text-sm font-bold text-gray-900">Total</td>
                                    <td className="px-6 py-4 text-sm text-right font-black text-teal-600">K {formatFullAmount(totalRevenue)}</td>
                                    <td className="px-6 py-4 text-sm text-right font-semibold text-gray-900">100%</td>
                                </tr>
                            </tfoot>
                    </table>
                    </div>
                </div>
            </div>
        </SectionLayout>
    );
}
