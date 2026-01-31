import { Head, router, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { Calendar, Download, AlertCircle, CheckCircle, ArrowLeft } from 'lucide-react';
import { useState } from 'react';

export default function TrialBalance({ trialBalance, asOf }) {
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
        router.visit(`/accounting/reports/trial-balance?as_of=${newDate}`);
    };

    return (
        <SectionLayout sectionName="Reports">
            <Head title="Trial Balance" />
            <div className="max-w-7xl mx-auto">
                <div className="mb-4">
                    <Link href="/reports" className="inline-flex items-center gap-2 text-sm text-teal-600 hover:text-teal-700 font-semibold">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Reports
                    </Link>
                </div>
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-black text-gray-900 tracking-tight">Trial Balance</h1>
                        <p className="text-gray-500 mt-1">As of {new Date(trialBalance.as_of).toLocaleDateString()}</p>
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

                {trialBalance.is_balanced ? (
                    <div className="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center gap-2">
                        <CheckCircle className="h-5 w-5 text-green-600" />
                        <span className="text-sm text-green-800">Trial balance is balanced</span>
                    </div>
                ) : (
                    <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2">
                        <AlertCircle className="h-5 w-5 text-red-600" />
                        <span className="text-sm text-red-800">
                            Trial balance is not balanced. Difference: {formatCurrency(Math.abs(trialBalance.total_debits - trialBalance.total_credits))}
                        </span>
                    </div>
                )}

                <Card className="p-6">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Account Code</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Account Name</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Account Type</th>
                                    <th className="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Debit</th>
                                    <th className="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Credit</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {trialBalance.accounts.map((account, index) => (
                                    <tr key={index} className="hover:bg-teal-50/30 transition-colors">
                                        <td className="px-4 py-3 text-sm font-mono text-gray-900">
                                            {account.account_code}
                                        </td>
                                        <td className="px-4 py-3 text-sm font-semibold text-gray-900">
                                            {account.account_name}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-600">
                                            {account.account_type}
                                        </td>
                                        <td className="px-4 py-3 text-right text-sm font-bold text-gray-900">
                                            {account.debit > 0 ? formatCurrency(account.debit) : '-'}
                                        </td>
                                        <td className="px-4 py-3 text-right text-sm font-bold text-gray-900">
                                            {account.credit > 0 ? formatCurrency(account.credit) : '-'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot className="bg-gray-50 border-t-2 border-gray-200">
                                <tr>
                                    <td colSpan="3" className="px-4 py-3 text-right font-bold text-gray-900">
                                        Totals:
                                    </td>
                                    <td className="px-4 py-3 text-right text-lg font-black text-gray-900">
                                        {formatCurrency(trialBalance.total_debits)}
                                    </td>
                                    <td className="px-4 py-3 text-right text-lg font-black text-gray-900">
                                        {formatCurrency(trialBalance.total_credits)}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </Card>
            </div>
        </SectionLayout>
    );
}

