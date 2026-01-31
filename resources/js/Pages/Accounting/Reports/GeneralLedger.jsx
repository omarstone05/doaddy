import { Head, router, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { Calendar, Download, ChevronDown, ChevronRight, ArrowLeft } from 'lucide-react';
import { useState } from 'react';

export default function GeneralLedger({ accounts, fromDate, toDate }) {
    const [expandedAccounts, setExpandedAccounts] = useState(new Set());
    const [startDate, setStartDate] = useState(fromDate || new Date().toISOString().split('T')[0]);
    const [endDate, setEndDate] = useState(toDate || new Date().toISOString().split('T')[0]);

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount || 0);
    };

    const toggleAccount = (accountId) => {
        const newExpanded = new Set(expandedAccounts);
        if (newExpanded.has(accountId)) {
            newExpanded.delete(accountId);
        } else {
            newExpanded.add(accountId);
        }
        setExpandedAccounts(newExpanded);
    };

    const handleDateChange = () => {
        router.visit(`/accounting/reports/general-ledger?from_date=${startDate}&to_date=${endDate}`);
    };

    return (
        <SectionLayout sectionName="Reports">
            <Head title="General Ledger" />
            <div className="max-w-7xl mx-auto">
                <div className="mb-4">
                    <Link href="/reports" className="inline-flex items-center gap-2 text-sm text-teal-600 hover:text-teal-700 font-semibold">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Reports
                    </Link>
                </div>
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-black text-gray-900 tracking-tight">General Ledger</h1>
                        <p className="text-gray-500 mt-1">
                            {new Date(fromDate).toLocaleDateString()} - {new Date(toDate).toLocaleDateString()}
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

                <div className="space-y-4">
                    {accounts && accounts.length > 0 ? (
                        accounts.map((accountData) => (
                            <Card key={accountData.account.id} className="p-6">
                                <div
                                    className="flex items-center justify-between cursor-pointer"
                                    onClick={() => toggleAccount(accountData.account.id)}
                                >
                                    <div className="flex items-center gap-3">
                                        {expandedAccounts.has(accountData.account.id) ? (
                                            <ChevronDown className="h-5 w-5 text-gray-400" />
                                        ) : (
                                            <ChevronRight className="h-5 w-5 text-gray-400" />
                                        )}
                                        <div>
                                            <p className="font-mono text-sm text-gray-600">{accountData.account.code}</p>
                                            <p className="font-semibold text-gray-900">{accountData.account.name}</p>
                                            <p className="text-xs text-gray-500">{accountData.account.account_type}</p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-xs text-gray-500">Opening: {formatCurrency(accountData.opening_balance)}</p>
                                        <p className="text-xs text-gray-500">Closing: {formatCurrency(accountData.closing_balance)}</p>
                                    </div>
                                </div>

                                {expandedAccounts.has(accountData.account.id) && (
                                    <div className="mt-4 pt-4 border-t border-gray-200">
                                        {accountData.entries && accountData.entries.length > 0 ? (
                                            <div className="overflow-x-auto">
                                                <table className="w-full">
                                                    <thead className="bg-gray-50">
                                                        <tr>
                                                            <th className="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase">Date</th>
                                                            <th className="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase">Entry #</th>
                                                            <th className="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase">Description</th>
                                                            <th className="px-3 py-2 text-center text-xs font-bold text-gray-600 uppercase">Type</th>
                                                            <th className="px-3 py-2 text-right text-xs font-bold text-gray-600 uppercase">Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-gray-100">
                                                        {accountData.entries.map((entry, index) => (
                                                            <tr key={index} className="hover:bg-teal-50/30">
                                                                <td className="px-3 py-2 text-xs text-gray-900">{entry.date}</td>
                                                                <td className="px-3 py-2 text-xs font-mono text-gray-600">{entry.entry_number}</td>
                                                                <td className="px-3 py-2 text-xs text-gray-900">{entry.description}</td>
                                                                <td className="px-3 py-2 text-center">
                                                                    <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold ${
                                                                        entry.type === 'debit' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'
                                                                    }`}>
                                                                        {entry.type}
                                                                    </span>
                                                                </td>
                                                                <td className="px-3 py-2 text-right text-xs font-bold text-gray-900">
                                                                    {formatCurrency(entry.amount)}
                                                                </td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        ) : (
                                            <p className="text-sm text-gray-500 text-center py-4">No entries for this period</p>
                                        )}
                                    </div>
                                )}
                            </Card>
                        ))
                    ) : (
                        <Card className="p-12 text-center">
                            <p className="text-gray-500">No accounts with activity found for this period</p>
                        </Card>
                    )}
                </div>
            </div>
        </SectionLayout>
    );
}

