import { Head, router, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { Calendar, Download, DollarSign, ArrowLeft } from 'lucide-react';
import { useState } from 'react';

export default function CashFlow({ cashFlow, periodStart, periodEnd }) {
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
        router.visit(`/accounting/reports/cash-flow?period_start=${startDate}&period_end=${endDate}`);
    };

    return (
        <SectionLayout sectionName="Reports">
            <Head title="Cash Flow Statement" />
            <div className="max-w-7xl mx-auto">
                <div className="mb-4">
                    <Link href="/reports" className="inline-flex items-center gap-2 text-sm text-teal-600 hover:text-teal-700 font-semibold">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Reports
                    </Link>
                </div>
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-black text-gray-900 tracking-tight">Cash Flow Statement</h1>
                        <p className="text-gray-500 mt-1">
                            {new Date(cashFlow.period_start).toLocaleDateString()} - {new Date(cashFlow.period_end).toLocaleDateString()}
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
                    <h2 className="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <DollarSign className="h-5 w-5 text-teal-600" />
                        Cash Accounts
                    </h2>
                    <div className="space-y-4">
                        {cashFlow.cash_accounts && cashFlow.cash_accounts.length > 0 ? (
                            <>
                                {cashFlow.cash_accounts.map((account, index) => (
                                    <div key={index} className="p-4 bg-gray-50 rounded-lg">
                                        <div className="flex justify-between items-center mb-2">
                                            <div>
                                                <p className="font-mono text-sm text-gray-600">{account.code}</p>
                                                <p className="font-semibold text-gray-900">{account.name}</p>
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-3 gap-4 mt-3 pt-3 border-t border-gray-200">
                                            <div>
                                                <p className="text-xs text-gray-500">Opening Balance</p>
                                                <p className="text-sm font-bold text-gray-900">{formatCurrency(account.opening_balance)}</p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-gray-500">Closing Balance</p>
                                                <p className="text-sm font-bold text-gray-900">{formatCurrency(account.closing_balance)}</p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-gray-500">Net Change</p>
                                                <p className={`text-sm font-bold ${
                                                    account.net_change >= 0 ? 'text-green-600' : 'text-red-600'
                                                }`}>
                                                    {formatCurrency(account.net_change)}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                <div className="pt-4 mt-4 border-t-4 border-gray-300">
                                    <div className="flex justify-between items-center">
                                        <span className="text-xl font-black text-gray-900">Net Cash Flow</span>
                                        <span className={`text-xl font-black ${
                                            cashFlow.net_cash_flow >= 0 ? 'text-green-600' : 'text-red-600'
                                        }`}>
                                            {formatCurrency(cashFlow.net_cash_flow)}
                                        </span>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <p className="text-gray-500 text-center py-8">No cash accounts found</p>
                        )}
                    </div>
                </Card>
            </div>
        </SectionLayout>
    );
}

