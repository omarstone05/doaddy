import { Head, router, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { Calendar, Download, TrendingUp, TrendingDown, ArrowLeft } from 'lucide-react';
import { useState } from 'react';

export default function IncomeStatement({ incomeStatement, periodStart, periodEnd }) {
    const [startDate, setStartDate] = useState(periodStart || new Date().toISOString().split('T')[0]);
    const [endDate, setEndDate] = useState(periodEnd || new Date().toISOString().split('T')[0]);

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount || 0);
    };

    const handleDateChange = () => {
        router.visit(`/accounting/reports/income-statement?period_start=${startDate}&period_end=${endDate}`);
    };

    return (
        <SectionLayout sectionName="Reports">
            <Head title="Income Statement" />
            <div className="max-w-7xl mx-auto">
                <div className="mb-4">
                    <Link href="/reports" className="inline-flex items-center gap-2 text-sm text-teal-600 hover:text-teal-700 font-semibold">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Reports
                    </Link>
                </div>
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-black text-gray-900 tracking-tight">Income Statement</h1>
                        <p className="text-gray-500 mt-1">
                            {new Date(incomeStatement.period_start).toLocaleDateString()} - {new Date(incomeStatement.period_end).toLocaleDateString()}
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2">
                            <Calendar className="h-4 w-4 text-gray-400" />
                            <input
                                type="date"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                                className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                            <span className="text-gray-500">to</span>
                            <input
                                type="date"
                                value={endDate}
                                onChange={(e) => setEndDate(e.target.value)}
                                className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                            <Button onClick={handleDateChange} variant="outline" size="sm">
                                Update
                            </Button>
                        </div>
                        <Button variant="outline" className="gap-2">
                            <Download className="h-4 w-4" />
                            Export
                        </Button>
                    </div>
                </div>

                <Card className="p-6">
                    {/* Revenue Section */}
                    <div className="mb-6">
                        <h2 className="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <TrendingUp className="h-5 w-5 text-green-600" />
                            Revenue
                        </h2>
                        <div className="space-y-2">
                            {incomeStatement.revenue && incomeStatement.revenue.length > 0 ? (
                                incomeStatement.revenue.map((item, index) => (
                                    <div key={index} className="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span className="text-sm text-gray-900">{item.name}</span>
                                        <span className="text-sm font-bold text-green-600">{formatCurrency(item.balance)}</span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-gray-500 py-2">No revenue accounts with activity</p>
                            )}
                            <div className="flex justify-between items-center py-3 mt-4 border-t-2 border-gray-300 font-bold text-lg">
                                <span className="text-gray-900">Total Revenue</span>
                                <span className="text-green-600">{formatCurrency(incomeStatement.total_revenue)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Expenses Section */}
                    <div className="mb-6">
                        <h2 className="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <TrendingDown className="h-5 w-5 text-red-600" />
                            Expenses
                        </h2>
                        <div className="space-y-2">
                            {incomeStatement.expenses && incomeStatement.expenses.length > 0 ? (
                                incomeStatement.expenses.map((item, index) => (
                                    <div key={index} className="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span className="text-sm text-gray-900">{item.name}</span>
                                        <span className="text-sm font-bold text-red-600">{formatCurrency(item.balance)}</span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-gray-500 py-2">No expense accounts with activity</p>
                            )}
                            <div className="flex justify-between items-center py-3 mt-4 border-t-2 border-gray-300 font-bold text-lg">
                                <span className="text-gray-900">Total Expenses</span>
                                <span className="text-red-600">{formatCurrency(incomeStatement.total_expenses)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Net Income */}
                    <div className="pt-6 border-t-4 border-gray-300">
                        <div className="flex justify-between items-center">
                            <span className="text-2xl font-black text-gray-900">Net Income</span>
                            <span className={`text-2xl font-black ${
                                incomeStatement.net_income >= 0 ? 'text-green-600' : 'text-red-600'
                            }`}>
                                {formatCurrency(incomeStatement.net_income)}
                            </span>
                        </div>
                    </div>
                </Card>
            </div>
        </SectionLayout>
    );
}

