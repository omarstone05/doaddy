import { Head, router, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { Calendar, Download, AlertCircle, CheckCircle, ArrowLeft } from 'lucide-react';
import { useState } from 'react';

export default function BalanceSheet({ balanceSheet, asOf }) {
    const [selectedDate, setSelectedDate] = useState(asOf || new Date().toISOString().split('T')[0]);

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount || 0);
    };

    const handleDateChange = (e) => {
        const newDate = e.target.value;
        setSelectedDate(newDate);
        router.visit(`/accounting/reports/balance-sheet?as_of=${newDate}`);
    };

    return (
        <SectionLayout sectionName="Reports">
            <Head title="Balance Sheet" />
            <div className="max-w-7xl mx-auto">
                <div className="mb-4">
                    <Link href="/reports" className="inline-flex items-center gap-2 text-sm text-teal-600 hover:text-teal-700 font-semibold">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Reports
                    </Link>
                </div>
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-black text-gray-900 tracking-tight">Balance Sheet</h1>
                        <p className="text-gray-500 mt-1">As of {new Date(balanceSheet.as_of).toLocaleDateString()}</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2">
                            <Calendar className="h-4 w-4 text-gray-400" />
                            <input
                                type="date"
                                value={selectedDate}
                                onChange={handleDateChange}
                                className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>
                        <Button variant="outline" className="gap-2">
                            <Download className="h-4 w-4" />
                            Export
                        </Button>
                    </div>
                </div>

                {balanceSheet.is_balanced ? (
                    <div className="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center gap-2">
                        <CheckCircle className="h-5 w-5 text-green-600" />
                        <span className="text-sm text-green-800">Balance sheet is balanced</span>
                    </div>
                ) : (
                    <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2">
                        <AlertCircle className="h-5 w-5 text-red-600" />
                        <span className="text-sm text-red-800">
                            Balance sheet is not balanced. Difference: {formatCurrency(Math.abs(balanceSheet.total_assets - (balanceSheet.total_liabilities + balanceSheet.total_equity)))}
                        </span>
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Assets */}
                    <Card className="p-6">
                        <h2 className="text-xl font-bold text-gray-900 mb-4">Assets</h2>
                        <div className="space-y-2">
                            {balanceSheet.assets.map((asset, index) => (
                                <div key={index} className="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span className="text-sm text-gray-900">{asset.name}</span>
                                    <span className="text-sm font-bold text-gray-900">{formatCurrency(asset.balance)}</span>
                                </div>
                            ))}
                            <div className="flex justify-between items-center py-3 mt-4 border-t-2 border-gray-300 font-bold text-lg">
                                <span className="text-gray-900">Total Assets</span>
                                <span className="text-gray-900">{formatCurrency(balanceSheet.total_assets)}</span>
                            </div>
                        </div>
                    </Card>

                    {/* Liabilities & Equity */}
                    <Card className="p-6">
                        <h2 className="text-xl font-bold text-gray-900 mb-4">Liabilities & Equity</h2>
                        <div className="space-y-4">
                            <div>
                                <h3 className="text-sm font-semibold text-gray-700 mb-2">Liabilities</h3>
                                <div className="space-y-2">
                                    {balanceSheet.liabilities.map((liability, index) => (
                                        <div key={index} className="flex justify-between items-center py-2 border-b border-gray-100">
                                            <span className="text-sm text-gray-900">{liability.name}</span>
                                            <span className="text-sm font-bold text-gray-900">{formatCurrency(liability.balance)}</span>
                                        </div>
                                    ))}
                                    <div className="flex justify-between items-center py-2 mt-2 font-semibold">
                                        <span className="text-sm text-gray-700">Total Liabilities</span>
                                        <span className="text-sm text-gray-900">{formatCurrency(balanceSheet.total_liabilities)}</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-gray-700 mb-2">Equity</h3>
                                <div className="space-y-2">
                                    {balanceSheet.equity.map((equity, index) => (
                                        <div key={index} className="flex justify-between items-center py-2 border-b border-gray-100">
                                            <span className="text-sm text-gray-900">{equity.name}</span>
                                            <span className="text-sm font-bold text-gray-900">{formatCurrency(equity.balance)}</span>
                                        </div>
                                    ))}
                                    <div className="flex justify-between items-center py-2 mt-2 font-semibold">
                                        <span className="text-sm text-gray-700">Total Equity</span>
                                        <span className="text-sm text-gray-900">{formatCurrency(balanceSheet.total_equity)}</span>
                                    </div>
                                </div>
                            </div>
                            <div className="flex justify-between items-center py-3 mt-4 border-t-2 border-gray-300 font-bold text-lg">
                                <span className="text-gray-900">Total Liabilities & Equity</span>
                                <span className="text-gray-900">{formatCurrency(balanceSheet.total_liabilities_and_equity)}</span>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </SectionLayout>
    );
}

