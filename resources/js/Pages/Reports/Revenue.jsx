import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { ArrowLeft, Download } from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';

export default function ReportsRevenue({ totalRevenue, salesRevenue, paymentsRevenue, otherIncome, revenueBySource, dailyRevenue, filters }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const COLORS = ['#14b8a6', '#3b82f6', '#f59e0b'];

    const handleDateChange = (field, value) => {
        router.visit(`/reports/revenue?${field}=${value}&${field === 'date_from' ? 'date_to' : 'date_from'}=${filters[field === 'date_from' ? 'date_to' : 'date_from']}`);
    };

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Revenue Report" />
            <div className="max-w-7xl mx-auto">
                <div className="mb-6">
                    <Link href="/reports">
                        <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4">
                            <ArrowLeft className="h-4 w-4" />
                            Back to Reports
                        </button>
                    </Link>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Revenue Report</h1>
                            <p className="text-gray-500 mt-1">Revenue from all sources</p>
                        </div>
                        <button className="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            <Download className="h-4 w-4" />
                            Export
                        </button>
                    </div>
                </div>

                {/* Date Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                            <input
                                type="date"
                                value={filters.date_from}
                                onChange={(e) => handleDateChange('date_from', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                            <input
                                type="date"
                                value={filters.date_to}
                                onChange={(e) => handleDateChange('date_to', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-4 gap-6 mb-6">
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="text-sm text-gray-500 mb-1">Total Revenue</div>
                        <div className="text-2xl font-bold text-gray-900">{formatCurrency(totalRevenue)}</div>
                    </div>
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="text-sm text-gray-500 mb-1">Sales Revenue</div>
                        <div className="text-2xl font-bold text-blue-600">{formatCurrency(salesRevenue)}</div>
                    </div>
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="text-sm text-gray-500 mb-1">Payments</div>
                        <div className="text-2xl font-bold text-green-600">{formatCurrency(paymentsRevenue)}</div>
                    </div>
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="text-sm text-gray-500 mb-1">Other Income</div>
                        <div className="text-2xl font-bold text-purple-600">{formatCurrency(otherIncome)}</div>
                    </div>
                </div>

                {/* Charts */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    {/* Daily Revenue Chart */}
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Daily Revenue Trend</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={dailyRevenue}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="date" />
                                <YAxis />
                                <Tooltip />
                                <Legend />
                                <Line type="monotone" dataKey="revenue" stroke="#14b8a6" name="Revenue" />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Revenue by Source */}
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Revenue by Source</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <PieChart>
                                <Pie
                                    data={revenueBySource}
                                    dataKey="amount"
                                    nameKey="source"
                                    cx="50%"
                                    cy="50%"
                                    outerRadius={100}
                                    label
                                >
                                    {revenueBySource.map((entry, index) => (
                                        <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                    ))}
                                </Pie>
                                <Tooltip />
                                <Legend />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Revenue Breakdown Table */}
                <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-200">
                        <h3 className="text-lg font-semibold text-gray-900">Revenue Breakdown</h3>
                    </div>
                    <table className="w-full">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Source</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Amount</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Percentage</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {revenueBySource.map((item, index) => {
                                const percentage = totalRevenue > 0 ? (item.amount / totalRevenue * 100).toFixed(1) : 0;
                                return (
                                    <tr key={index}>
                                        <td className="px-6 py-4 text-sm text-gray-900">{item.source}</td>
                                        <td className="px-6 py-4 text-sm text-right font-medium text-gray-900">
                                            {formatCurrency(item.amount)}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-right text-gray-600">{percentage}%</td>
                                    </tr>
                                );
                            })}
                        </tbody>
                        <tfoot className="bg-gray-50 border-t border-gray-200">
                            <tr>
                                <td className="px-6 py-4 text-sm font-semibold text-gray-900">Total</td>
                                <td className="px-6 py-4 text-sm text-right font-bold text-gray-900">
                                    {formatCurrency(totalRevenue)}
                                </td>
                                <td className="px-6 py-4 text-sm text-right font-semibold text-gray-900">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </SectionLayout>
    );
}

