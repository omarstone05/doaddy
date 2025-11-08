import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { ArrowLeft, Download, TrendingUp, TrendingDown } from 'lucide-react';

export default function ReportsProfitLoss({ revenue, expenses, profit, profitMargin, filters }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const handleDateChange = (field, value) => {
        router.visit(`/reports/profit-loss?${field}=${value}&${field === 'date_from' ? 'date_to' : 'date_from'}=${filters[field === 'date_from' ? 'date_to' : 'date_from']}`);
    };

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Profit & Loss Report" />
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
                            <h1 className="text-3xl font-bold text-gray-900">Profit & Loss Report</h1>
                            <p className="text-gray-500 mt-1">Financial performance overview</p>
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
                        <div className="flex items-center gap-3 mb-2">
                            <TrendingUp className="h-5 w-5 text-green-500" />
                            <div className="text-sm text-gray-500">Total Revenue</div>
                        </div>
                        <div className="text-3xl font-bold text-gray-900">{formatCurrency(revenue)}</div>
                    </div>
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="flex items-center gap-3 mb-2">
                            <TrendingDown className="h-5 w-5 text-red-500" />
                            <div className="text-sm text-gray-500">Total Expenses</div>
                        </div>
                        <div className="text-3xl font-bold text-gray-900">{formatCurrency(expenses)}</div>
                    </div>
                    <div className={`bg-white border rounded-lg p-6 ${profit >= 0 ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'}`}>
                        <div className="text-sm text-gray-500 mb-2">
                            {profit >= 0 ? 'Net Profit' : 'Net Loss'}
                        </div>
                        <div className={`text-3xl font-bold ${profit >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                            {formatCurrency(profit)}
                        </div>
                        <div className="text-sm text-gray-500 mt-2">
                            Margin: {profitMargin.toFixed(1)}%
                        </div>
                    </div>
                </div>

                {/* P&L Statement */}
                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <h2 className="text-xl font-semibold text-gray-900 mb-6">Profit & Loss Statement</h2>
                    <div className="space-y-4">
                        <div className="flex justify-between items-center py-3 border-b border-gray-200">
                            <span className="text-gray-700">Revenue</span>
                            <span className="font-medium text-gray-900">{formatCurrency(revenue)}</span>
                        </div>
                        <div className="flex justify-between items-center py-3 border-b border-gray-200">
                            <span className="text-gray-700">Cost of Goods Sold</span>
                            <span className="font-medium text-gray-900">{formatCurrency(0)}</span>
                        </div>
                        <div className="flex justify-between items-center py-3 border-b-2 border-gray-300">
                            <span className="font-semibold text-gray-900">Gross Profit</span>
                            <span className="font-bold text-gray-900">{formatCurrency(revenue)}</span>
                        </div>
                        <div className="flex justify-between items-center py-3 border-b border-gray-200">
                            <span className="text-gray-700">Operating Expenses</span>
                            <span className="font-medium text-gray-900">{formatCurrency(expenses)}</span>
                        </div>
                        <div className={`flex justify-between items-center py-4 border-t-2 border-gray-300 ${profit >= 0 ? 'bg-green-50' : 'bg-red-50'}`}>
                            <span className={`font-bold text-lg ${profit >= 0 ? 'text-green-700' : 'text-red-700'}`}>
                                {profit >= 0 ? 'Net Profit' : 'Net Loss'}
                            </span>
                            <span className={`font-bold text-lg ${profit >= 0 ? 'text-green-700' : 'text-red-700'}`}>
                                {formatCurrency(profit)}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Profitability Metrics */}
                <div className="grid grid-cols-3 gap-6">
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="text-sm text-gray-500 mb-2">Profit Margin</div>
                        <div className="text-2xl font-bold text-gray-900">{profitMargin.toFixed(1)}%</div>
                        <div className="text-xs text-gray-500 mt-2">
                            {(profit / revenue * 100).toFixed(1)}% of revenue
                        </div>
                    </div>
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="text-sm text-gray-500 mb-2">Expense Ratio</div>
                        <div className="text-2xl font-bold text-gray-900">
                            {revenue > 0 ? ((expenses / revenue) * 100).toFixed(1) : 0}%
                        </div>
                        <div className="text-xs text-gray-500 mt-2">
                            Expenses as % of revenue
                        </div>
                    </div>
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="text-sm text-gray-500 mb-2">Break-Even Point</div>
                        <div className="text-2xl font-bold text-gray-900">
                            {formatCurrency(expenses)}
                        </div>
                        <div className="text-xs text-gray-500 mt-2">
                            Revenue needed to break even
                        </div>
                    </div>
                </div>
            </div>
        </SectionLayout>
    );
}

