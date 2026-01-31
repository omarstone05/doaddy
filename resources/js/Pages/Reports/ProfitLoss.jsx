import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { ArrowLeft, Download, TrendingUp, TrendingDown, DollarSign, Percent, Target } from 'lucide-react';
import { TimePeriodSelector } from '@/Components/ui/TimePeriodSelector';
import { StatCard } from '@/Components/ui';
import { exportToPdf } from '@/utils/exportPdf';

export default function ReportsProfitLoss({ revenue, expenses, profit, profitMargin, period = 'month', filters }) {
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
            router.get('/reports/profit-loss', { period: newPeriod }, { preserveState: true, preserveScroll: true });
        }
    };

    const handleCustomDateChange = (field, value) => {
        const params = {
            period: 'custom',
            date_from: field === 'date_from' ? value : filters.date_from,
            date_to: field === 'date_to' ? value : filters.date_to,
        };
        router.get('/reports/profit-loss', params, { preserveState: true, preserveScroll: true });
    };

    const handleExport = () => {
        setIsExporting(true);
        exportToPdf('report-content', `profit-loss-report-${currentPeriod}-${new Date().toISOString().split('T')[0]}`);
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

    const isProfit = profit >= 0;

    return (
        <SectionLayout sectionName="Reports">
            <Head title="Profit & Loss Report" />
            <div className="max-w-7xl mx-auto">
                {/* Header */}
                <div className="mb-8">
                    <Link href="/reports" className="inline-flex items-center gap-2 text-gray-600 hover:text-teal-600 mb-4 font-medium transition-colors">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Reports
                    </Link>
                    <div className="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h1 className="text-3xl font-black text-gray-900 tracking-tight">Profit & Loss Report</h1>
                            <p className="text-gray-500 mt-1">Financial performance overview</p>
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
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <StatCard
                            title="Total Revenue"
                            value={formatCurrency(revenue)}
                            subtitle={getPeriodLabel()}
                            icon={TrendingUp}
                            variant="gradient-positive"
                        />
                        <StatCard
                            title="Total Expenses"
                            value={formatCurrency(expenses)}
                            subtitle={getPeriodLabel()}
                            icon={TrendingDown}
                            variant="gradient-negative"
                        />
                        <StatCard
                            title={isProfit ? 'Net Profit' : 'Net Loss'}
                            value={formatCurrency(Math.abs(profit))}
                            subtitle={`${profitMargin.toFixed(1)}% margin`}
                            icon={DollarSign}
                            variant={isProfit ? 'gradient-positive' : 'gradient-negative'}
                        />
                    </div>

                    {/* P&L Statement */}
                    <div className="bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl p-6 mb-6">
                        <h2 className="text-xl font-bold text-gray-900 mb-6">Profit & Loss Statement</h2>
                        <div className="space-y-1">
                            <div className="flex justify-between items-center py-4 px-4 rounded-xl bg-teal-50/50">
                                <span className="text-gray-700 font-medium">Revenue</span>
                                <span className="font-bold text-teal-600">K {formatFullAmount(revenue)}</span>
                            </div>
                            <div className="flex justify-between items-center py-4 px-4 rounded-xl hover:bg-gray-50 transition-colors">
                                <span className="text-gray-600">Cost of Goods Sold</span>
                                <span className="font-medium text-gray-900">K 0.00</span>
                            </div>
                            <div className="flex justify-between items-center py-4 px-4 rounded-xl bg-gray-100/50 border-y-2 border-gray-200">
                                <span className="font-bold text-gray-900">Gross Profit</span>
                                <span className="font-black text-gray-900">K {formatFullAmount(revenue)}</span>
                            </div>
                            <div className="flex justify-between items-center py-4 px-4 rounded-xl bg-red-50/50">
                                <span className="text-gray-700 font-medium">Operating Expenses</span>
                                <span className="font-bold text-red-600">K {formatFullAmount(expenses)}</span>
                            </div>
                            <div className={`flex justify-between items-center py-5 px-4 rounded-xl ${isProfit ? 'bg-gradient-to-r from-emerald-100 to-teal-100' : 'bg-gradient-to-r from-red-100 to-rose-100'}`}>
                                <span className={`font-black text-lg ${isProfit ? 'text-emerald-700' : 'text-red-700'}`}>
                                    {isProfit ? 'Net Profit' : 'Net Loss'}
                                </span>
                                <span className={`font-black text-2xl ${isProfit ? 'text-emerald-700' : 'text-red-700'}`}>
                                    K {formatFullAmount(Math.abs(profit))}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Profitability Metrics */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <StatCard
                            title="Profit Margin"
                            value={profitMargin.toFixed(1)}
                            suffix="%"
                            subtitle={revenue > 0 ? `${(profit / revenue * 100).toFixed(1)}% of revenue` : 'N/A'}
                            icon={Percent}
                        />
                        <StatCard
                            title="Expense Ratio"
                            value={revenue > 0 ? ((expenses / revenue) * 100).toFixed(1) : '0'}
                            suffix="%"
                            subtitle="Expenses as % of revenue"
                            icon={TrendingDown}
                        />
                        <StatCard
                            title="Break-Even Point"
                            value={formatCurrency(expenses)}
                            subtitle="Revenue needed to break even"
                            icon={Target}
                        />
                    </div>
                </div>
            </div>
        </SectionLayout>
    );
}
