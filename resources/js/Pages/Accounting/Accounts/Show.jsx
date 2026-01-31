import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { ArrowLeft, Edit, TrendingUp } from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

export default function AccountsShow({ account, recentEntries, balanceHistory }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount || 0);
    };

    return (
        <SectionLayout sectionName="Accounting">
            <Head title={`Account - ${account.name}`} />
            <div className="max-w-7xl mx-auto">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => router.visit('/accounting/accounts')}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">{account.name}</h1>
                            <p className="text-gray-500 mt-1">
                                {account.code} • {account.accountType?.name}
                            </p>
                        </div>
                        <Link href={`/accounting/accounts/${account.id}/edit`}>
                            <Button>
                                <Edit className="h-4 w-4 mr-2" />
                                Edit
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <Card className="p-6">
                        <p className="text-sm font-semibold text-gray-600 mb-2">Current Balance</p>
                        <p className={`text-3xl font-black ${
                            account.current_balance >= 0 ? 'text-gray-900' : 'text-red-600'
                        }`}>
                            {formatCurrency(account.current_balance)}
                        </p>
                    </Card>
                    <Card className="p-6">
                        <p className="text-sm font-semibold text-gray-600 mb-2">Opening Balance</p>
                        <p className="text-3xl font-black text-gray-900">
                            {formatCurrency(account.opening_balance)}
                        </p>
                    </Card>
                    <Card className="p-6">
                        <p className="text-sm font-semibold text-gray-600 mb-2">Normal Balance</p>
                        <p className="text-3xl font-black text-gray-900 capitalize">
                            {account.normal_balance}
                        </p>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    {/* Balance History Chart */}
                    <Card className="p-6">
                        <div className="flex items-center gap-2 mb-4">
                            <TrendingUp className="h-5 w-5 text-teal-600" />
                            <h2 className="text-lg font-semibold text-gray-900">Balance History</h2>
                        </div>
                        {balanceHistory && balanceHistory.length > 0 ? (
                            <ResponsiveContainer width="100%" height={300}>
                                <LineChart data={balanceHistory}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="month" />
                                    <YAxis />
                                    <Tooltip formatter={(value) => formatCurrency(value)} />
                                    <Line type="monotone" dataKey="balance" stroke="#14b8a6" strokeWidth={2} />
                                </LineChart>
                            </ResponsiveContainer>
                        ) : (
                            <p className="text-gray-500 text-center py-12">No balance history available</p>
                        )}
                    </Card>

                    {/* Account Details */}
                    <Card className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Account Details</h2>
                        <dl className="space-y-4">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Account Code</dt>
                                <dd className="mt-1 text-sm text-gray-900 font-mono">{account.code}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Account Type</dt>
                                <dd className="mt-1 text-sm text-gray-900">{account.accountType?.name}</dd>
                            </div>
                            {account.parentAccount && (
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Parent Account</dt>
                                    <dd className="mt-1 text-sm text-gray-900">
                                        {account.parentAccount.code} - {account.parentAccount.name}
                                    </dd>
                                </div>
                            )}
                            {account.description && (
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Description</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{account.description}</dd>
                                </div>
                            )}
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Status</dt>
                                <dd className="mt-1">
                                    <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                                        account.is_active 
                                            ? 'bg-green-100 text-green-700' 
                                            : 'bg-gray-100 text-gray-700'
                                    }`}>
                                        {account.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </Card>
                </div>

                {/* Recent Journal Entries */}
                <Card className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4">Recent Journal Entries</h2>
                    {recentEntries && recentEntries.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Date</th>
                                        <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Entry Number</th>
                                        <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Description</th>
                                        <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Type</th>
                                        <th className="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Amount</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {recentEntries.map((entry) => (
                                        <tr key={entry.id} className="hover:bg-teal-50/30 transition-colors">
                                            <td className="px-4 py-3 text-sm text-gray-900">
                                                {new Date(entry.journalEntry?.entry_date).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 py-3 text-sm font-mono text-gray-600">
                                                {entry.journalEntry?.entry_number}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-900">
                                                {entry.description || entry.journalEntry?.description}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                                                    entry.type === 'debit' 
                                                        ? 'bg-blue-100 text-blue-700' 
                                                        : 'bg-green-100 text-green-700'
                                                }`}>
                                                    {entry.type}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm font-bold text-gray-900">
                                                {formatCurrency(entry.amount)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-gray-500 text-center py-8">No journal entries found for this account</p>
                    )}
                </Card>
            </div>
        </SectionLayout>
    );
}

