import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { ArrowLeft, Download, Receipt } from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar } from 'recharts';
import { TimePeriodSelector } from '@/Components/ui/TimePeriodSelector';
import { StatCard } from '@/Components/ui';
import { exportToPdf } from '@/utils/exportPdf';

export default function ReportsExpenses({ totalExpenses, expensesByCategory, dailyExpenses, period = 'month', filters }) {
    const [currentPeriod, setCurrentPeriod] = useState(period);
    const [isExporting, setIsExporting] = useState(false);

    const formatCurrency = (amount) => {
        const num = parseFloat(amount) || 0;
        return 'K ' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const formatFullAmount = (amount) => {
        const num = parseFloat(amount) || 0;
        return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const handlePeriodChange = (newPeriod) => {
        setCurrentPeriod(newPeriod);
        if (newPeriod !== 'custom') {
            router.get('/reports/expenses', { period: newPeriod }, { preserveState: true, preserveScroll: true });
        }
    };

    const handleCustomDateChange = (field, value) => {
        const params = {
            period: 'custom',
            date_from: field === 'date_from' ? value : filters.date_from,
            date_to: field === 'date_to' ? value : filters.date_to,
        };
        router.get('/reports/expenses', params, { preserveState: true, preserveScroll: true });
    };

    const handleExport = () => {
        setIsExporting(true);
        exportToPdf('report-content', `expenses-report-${currentPeriod}-${new Date().toISOString().split('T')[0]}`);
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
            <Head title="Expenses Report" />
            <div className="max-w-7xl mx-auto">
                {/* Header */}
                <div className="mb-8">
                    <Link href="/reports" className="inline-flex items-center gap-2 text-gray-600 hover:text-teal-600 mb-4 font-medium transition-colors">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Reports
                    </Link>
                    <div className="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h1 className="text-3xl font-black text-gray-900 tracking-tight">Expenses Report</h1>
                            <p className="text-gray-500 mt-1">Expense analysis by category</p>
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
                    {/* Summary Card */}
                    <div className="mb-6">
                        <StatCard
                            title="Total Expenses"
                            value={formatCurrency(totalExpenses)}
                            subtitle={getPeriodLabel()}
                            icon={Receipt}
                            variant="gradient-negative"
                            className="max-w-md"
                        />
                    </div>

                    {/* Charts */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div className="bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl p-6">
                            <h3 className="text-lg font-bold text-gray-900 mb-6">Expenses Trend</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <LineChart data={dailyExpenses}>
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
                                        formatter={(value) => [`K ${value.toLocaleString()}`, 'Expenses']}
                                    />
                                    <Line type="monotone" dataKey="total" stroke="#ef4444" strokeWidth={2} dot={false} name="Expenses" />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>

                        <div className="bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl p-6">
                            <h3 className="text-lg font-bold text-gray-900 mb-6">Expenses by Category</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={expensesByCategory} layout="vertical" barGap={4}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" horizontal={false} />
                                    <XAxis type="number" axisLine={false} tickLine={false} tick={{ fill: '#78716c', fontSize: 11 }} />
                                    <YAxis dataKey="category" type="category" width={100} axisLine={false} tickLine={false} tick={{ fill: '#78716c', fontSize: 11 }} />
                                    <Tooltip
                                        contentStyle={{
                                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                            border: '1px solid #e7e5e4',
                                            borderRadius: '12px',
                                        }}
                                        formatter={(value) => [`K ${value.toLocaleString()}`, 'Expenses']}
                                    />
                                    <Bar dataKey="total" fill="#ef4444" radius={[0, 6, 6, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    {/* Expenses by Category Table */}
                    <div className="bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-200/50">
                            <h3 className="text-lg font-bold text-gray-900">Expenses by Category</h3>
                        </div>
                        <table className="w-full">
                            <thead className="bg-gray-50/80">
                                <tr>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase">Category</th>
                                    <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase">Count</th>
                                    <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase">Total</th>
                                    <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase">%</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {expensesByCategory.map((item, index) => {
                                    const percentage = totalExpenses > 0 ? (item.total / totalExpenses * 100).toFixed(1) : 0;
                                    return (
                                        <tr key={index}>
                                            <td className="px-6 py-4 text-sm font-semibold text-gray-900">{item.category || 'Uncategorized'}</td>
                                            <td className="px-6 py-4 text-sm text-right text-gray-600">{item.count}</td>
                                            <td className="px-6 py-4 text-sm text-right font-bold text-red-600">K {formatFullAmount(item.total)}</td>
                                            <td className="px-6 py-4 text-sm text-right text-gray-600">{percentage}%</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                            <tfoot className="bg-gray-50/80 border-t border-gray-200/50">
                                <tr>
                                    <td className="px-6 py-4 text-sm font-bold text-gray-900">Total</td>
                                    <td className="px-6 py-4 text-sm text-right font-semibold text-gray-900">
                                        {expensesByCategory.reduce((sum, item) => sum + item.count, 0)}
                                    </td>
                                    <td className="px-6 py-4 text-sm text-right font-black text-red-600">K {formatFullAmount(totalExpenses)}</td>
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
