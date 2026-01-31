import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { Plus, Search, Eye, Edit, Trash2, Filter } from 'lucide-react';
import { useState } from 'react';

export default function AccountsIndex({ groupedAccounts, accountTypes, filters }) {
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [selectedType, setSelectedType] = useState(filters?.account_type_id || '');

    const handleSearch = (e) => {
        e.preventDefault();
        const params = new URLSearchParams();
        if (searchTerm) params.append('search', searchTerm);
        if (selectedType) params.append('account_type_id', selectedType);
        router.visit(`/accounting/accounts?${params.toString()}`);
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount || 0);
    };

    return (
        <SectionLayout sectionName="Accounting">
            <Head title="Chart of Accounts" />
            <div className="max-w-7xl mx-auto">
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-black text-gray-900 tracking-tight">Chart of Accounts</h1>
                        <p className="text-gray-500 mt-1">Manage your accounting accounts</p>
                    </div>
                    <Button onClick={() => router.visit('/accounting/accounts/create')} className="gap-2">
                        <Plus className="h-4 w-4" />
                        New Account
                    </Button>
                </div>

                {/* Filters */}
                <Card className="p-6 mb-6">
                    <div className="flex items-center gap-3 mb-4">
                        <Filter className="w-5 h-5 text-teal-600" />
                        <h3 className="text-sm font-bold text-gray-900">Filters</h3>
                    </div>
                    <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="md:col-span-2">
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Search</label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <input
                                    type="text"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder="Search by code, name, or description..."
                                    className="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Account Type</label>
                            <select
                                value={selectedType}
                                onChange={(e) => setSelectedType(e.target.value)}
                                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                                <option value="">All Types</option>
                                {accountTypes.map((type) => (
                                    <option key={type.id} value={type.id}>
                                        {type.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </form>
                </Card>

                {/* Accounts by Type */}
                {groupedAccounts && groupedAccounts.length > 0 ? (
                    <div className="space-y-6">
                        {groupedAccounts.map((group) => (
                            <Card key={group.type.id} className="p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <div>
                                        <h2 className="text-xl font-bold text-gray-900">{group.type.name}</h2>
                                        <p className="text-sm text-gray-500">{group.type.description}</p>
                                    </div>
                                    <span className="text-sm font-medium text-gray-600">
                                        {group.accounts.length} {group.accounts.length === 1 ? 'account' : 'accounts'}
                                    </span>
                                </div>

                                <div className="overflow-x-auto">
                                    <table className="w-full">
                                        <thead className="bg-gray-50 border-b border-gray-200">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Code</th>
                                                <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Account Name</th>
                                                <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Type</th>
                                                <th className="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Balance</th>
                                                <th className="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100">
                                            {group.accounts.map((account) => (
                                                <tr key={account.id} className="hover:bg-teal-50/30 transition-colors">
                                                    <td className="px-4 py-3 text-sm font-mono text-gray-900">
                                                        {account.code}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div>
                                                            <p className="text-sm font-semibold text-gray-900">{account.name}</p>
                                                            {account.description && (
                                                                <p className="text-xs text-gray-500">{account.description}</p>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {account.is_sub_account && account.parent_account ? (
                                                            <span className="text-xs text-gray-500">
                                                                Sub-account of {account.parent_account.name}
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-100 text-teal-700">
                                                                {account.is_sub_account ? 'Sub-account' : 'Main Account'}
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <span className={`text-sm font-bold ${
                                                            account.current_balance >= 0 ? 'text-gray-900' : 'text-red-600'
                                                        }`}>
                                                            {formatCurrency(account.current_balance)}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-center">
                                                        <div className="flex items-center justify-center gap-1">
                                                            <Link
                                                                href={`/accounting/accounts/${account.id}`}
                                                                className="p-2 rounded-lg text-teal-600 hover:bg-teal-100 transition-colors"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </Link>
                                                            <Link
                                                                href={`/accounting/accounts/${account.id}/edit`}
                                                                className="p-2 rounded-lg text-blue-600 hover:bg-blue-100 transition-colors"
                                                            >
                                                                <Edit className="h-4 w-4" />
                                                            </Link>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <Card className="p-12 text-center">
                        <div className="w-16 h-16 rounded-2xl bg-teal-100 flex items-center justify-center mx-auto mb-4">
                            <Plus className="h-8 w-8 text-teal-500" />
                        </div>
                        <h3 className="text-lg font-bold text-gray-900 mb-2">No accounts found</h3>
                        <p className="text-gray-500 mb-6">Get started by creating your first account</p>
                        <Button onClick={() => router.visit('/accounting/accounts/create')} className="gap-2">
                            <Plus className="h-4 w-4" />
                            Create Account
                        </Button>
                    </Card>
                )}
            </div>
        </SectionLayout>
    );
}

