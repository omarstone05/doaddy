import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { ArrowLeft, Download, ShoppingCart, TrendingUp, Calculator } from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar } from 'recharts';
import { TimePeriodSelector } from '@/Components/ui/TimePeriodSelector';
import { StatCard } from '@/Components/ui';
import { exportToPdf } from '@/utils/exportPdf';

export default function ReportsSales({ totalSales, totalRevenue, averageSale, salesByProduct, salesByCustomer, dailySales, period = 'month', filters }) {
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

    const handlePeriodChange = (newPeriod) => {
        setCurrentPeriod(newPeriod);
        if (newPeriod !== 'custom') {
            router.get('/reports/sales', { period: newPeriod }, { preserveState: true, preserveScroll: true });
        }
    };

    const handleCustomDateChange = (field, value) => {
        const params = {
            period: 'custom',
            date_from: field === 'date_from' ? value : filters.date_from,
            date_to: field === 'date_to' ? value : filters.date_to,
        };
        router.get('/reports/sales', params, { preserveState: true, preserveScroll: true });
    };

    const handleExport = () => {
        setIsExporting(true);
        exportToPdf('report-content', `sales-report-${currentPeriod}-${new Date().toISOString().split('T')[0]}`);
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
            <Head title="Sales Report" />
            <div className="max-w-7xl mx-auto">
                {/* Header - Not included in PDF */}
                <div className="mb-8">
                    <Link href="/reports" className="inline-flex items-center gap-2 text-gray-600 hover:text-teal-600 mb-4 font-medium transition-colors">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Reports
                    </Link>
                    <div className="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h1 className="text-3xl font-black text-gray-900 tracking-tight">Sales Report</h1>
                            <p className="text-gray-500 mt-1">Sales performance and trends</p>
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

                {/* Report Content - This gets exported to PDF */}
                <div id="report-content" className="bg-white rounded-2xl p-6">
                    {/* PDF Header */}
                    <div className="mb-6 pb-4 border-b border-gray-200 print:block hidden" style={{ display: 'none' }}>
                        <h1 className="text-2xl font-bold text-gray-900">Sales Report</h1>
                        <p className="text-gray-500">{getPeriodLabel()} • Generated {new Date().toLocaleDateString()}</p>
                    </div>

                    {/* Summary Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <StatCard
                            title="Total Sales"
                            value={totalSales}
                            subtitle={getPeriodLabel()}
                            icon={ShoppingCart}
                            variant="gradient-positive"
                        />
                        <StatCard
                            title="Total Revenue"
                            value={formatCurrency(totalRevenue)}
                            prefix="K "
                            subtitle={getPeriodLabel()}
                            icon={TrendingUp}
                        />
                        <StatCard
                            title="Average Sale"
                            value={formatCurrency(averageSale)}
                            prefix="K "
                            subtitle={getPeriodLabel()}
                            icon={Calculator}
                        />
                    </div>

                    {/* Charts */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div className="bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl p-6">
                            <h3 className="text-lg font-bold text-gray-900 mb-6">Sales Trend</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <LineChart data={dailySales}>
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
                                    />
                                    <Line type="monotone" dataKey="revenue" stroke="#14b8a6" strokeWidth={2} dot={false} name="Revenue" />
                                    <Line type="monotone" dataKey="count" stroke="#3b82f6" strokeWidth={2} dot={false} name="Count" />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>

                        <div className="bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl p-6">
                            <h3 className="text-lg font-bold text-gray-900 mb-6">Top Products by Revenue</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={salesByProduct} layout="vertical" barGap={4}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" horizontal={false} />
                                    <XAxis type="number" axisLine={false} tickLine={false} tick={{ fill: '#78716c', fontSize: 11 }} />
                                    <YAxis dataKey="name" type="category" width={100} axisLine={false} tickLine={false} tick={{ fill: '#78716c', fontSize: 11 }} />
                                    <Tooltip
                                        contentStyle={{
                                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                            border: '1px solid #e7e5e4',
                                            borderRadius: '12px',
                                        }}
                                        formatter={(value) => [`K ${value.toLocaleString()}`, 'Revenue']}
                                    />
                                    <Bar dataKey="total_revenue" fill="#14b8a6" radius={[0, 6, 6, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    {/* Tables */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div className="bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl overflow-hidden">
                            <div className="px-6 py-4 border-b border-gray-200/50">
                                <h3 className="text-lg font-bold text-gray-900">Top Products</h3>
                            </div>
                            <table className="w-full">
                                <thead className="bg-gray-50/80">
                                    <tr>
                                        <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase">Product</th>
                                        <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase">Qty</th>
                                        <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {salesByProduct.map((item, index) => (
                                        <tr key={index}>
                                            <td className="px-6 py-4 text-sm font-semibold text-gray-900">{item.name}</td>
                                            <td className="px-6 py-4 text-sm text-right text-gray-600">{item.total_quantity}</td>
                                            <td className="px-6 py-4 text-sm text-right font-bold text-teal-600">K {formatFullAmount(item.total_revenue)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl overflow-hidden">
                            <div className="px-6 py-4 border-b border-gray-200/50">
                                <h3 className="text-lg font-bold text-gray-900">Top Customers</h3>
                            </div>
                            <table className="w-full">
                                <thead className="bg-gray-50/80">
                                    <tr>
                                        <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase">Customer</th>
                                        <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase">Sales</th>
                                        <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {salesByCustomer.map((item, index) => (
                                        <tr key={index}>
                                            <td className="px-6 py-4 text-sm font-semibold text-gray-900">{item.name}</td>
                                            <td className="px-6 py-4 text-sm text-right text-gray-600">{item.total_sales}</td>
                                            <td className="px-6 py-4 text-sm text-right font-bold text-teal-600">K {formatFullAmount(item.total_revenue)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </SectionLayout>
    );
}
