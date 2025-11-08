import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { ArrowLeft, Download } from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, BarChart, Bar } from 'recharts';

export default function ReportsSales({ totalSales, totalRevenue, averageSale, salesByProduct, salesByCustomer, dailySales, filters }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const handleDateChange = (field, value) => {
        router.visit(`/reports/sales?${field}=${value}&${field === 'date_from' ? 'date_to' : 'date_from'}=${filters[field === 'date_from' ? 'date_to' : 'date_from']}`);
    };

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Sales Report" />
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
                            <h1 className="text-3xl font-bold text-gray-900">Sales Report</h1>
                            <p className="text-gray-500 mt-1">Sales performance and trends</p>
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
                <div className="grid grid-cols-3 gap-6 mb-6">
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="text-sm text-gray-500 mb-1">Total Sales</div>
                        <div className="text-3xl font-bold text-gray-900">{totalSales}</div>
                    </div>
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="text-sm text-gray-500 mb-1">Total Revenue</div>
                        <div className="text-3xl font-bold text-gray-900">{formatCurrency(totalRevenue)}</div>
                    </div>
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="text-sm text-gray-500 mb-1">Average Sale</div>
                        <div className="text-3xl font-bold text-gray-900">{formatCurrency(averageSale)}</div>
                    </div>
                </div>

                {/* Charts */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    {/* Daily Sales Chart */}
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Daily Sales Trend</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={dailySales}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="date" />
                                <YAxis />
                                <Tooltip />
                                <Legend />
                                <Line type="monotone" dataKey="revenue" stroke="#14b8a6" name="Revenue" />
                                <Line type="monotone" dataKey="count" stroke="#3b82f6" name="Sales Count" />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Top Products Chart */}
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Top Products by Revenue</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <BarChart data={salesByProduct}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="name" angle={-45} textAnchor="end" height={100} />
                                <YAxis />
                                <Tooltip />
                                <Bar dataKey="total_revenue" fill="#14b8a6" name="Revenue" />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Tables */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Top Products */}
                    <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h3 className="text-lg font-semibold text-gray-900">Top Products</h3>
                        </div>
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Product</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Quantity</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Revenue</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {salesByProduct.map((item, index) => (
                                    <tr key={index}>
                                        <td className="px-6 py-4 text-sm text-gray-900">{item.name}</td>
                                        <td className="px-6 py-4 text-sm text-right text-gray-600">{item.total_quantity}</td>
                                        <td className="px-6 py-4 text-sm text-right font-medium text-gray-900">
                                            {formatCurrency(item.total_revenue)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Top Customers */}
                    <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h3 className="text-lg font-semibold text-gray-900">Top Customers</h3>
                        </div>
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Customer</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Sales</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Revenue</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {salesByCustomer.map((item, index) => (
                                    <tr key={index}>
                                        <td className="px-6 py-4 text-sm text-gray-900">{item.name}</td>
                                        <td className="px-6 py-4 text-sm text-right text-gray-600">{item.total_sales}</td>
                                        <td className="px-6 py-4 text-sm text-right font-medium text-gray-900">
                                            {formatCurrency(item.total_revenue)}
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

