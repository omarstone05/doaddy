import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Edit, Eye, CreditCard, Wallet, Building2, Smartphone } from 'lucide-react';

const accountTypeIcons = {
    bank: Building2,
    cash: Wallet,
    mobile_money: Smartphone,
    card: CreditCard,
    other: Wallet,
};

export default function MoneyAccountsIndex({ accounts }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const getTypeIcon = (type) => {
        const Icon = accountTypeIcons[type] || Wallet;
        return <Icon className="h-5 w-5" />;
    };

    return (
        <SectionLayout sectionName="Money">
            <Head title="Money Accounts" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Money Accounts</h1>
                        <p className="text-gray-500 mt-1">Manage your bank accounts, cash, and payment methods</p>
                    </div>
                    <Button onClick={() => router.visit('/money/accounts/create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        Add Account
                    </Button>
                </div>

                {accounts.length === 0 ? (
                    <div className="bg-white border border-gray-200 rounded-lg p-12 text-center">
                        <Wallet className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No accounts yet</h3>
                        <p className="text-gray-500 mb-4">Create your first money account to start tracking transactions</p>
                        <Button onClick={() => router.visit('/money/accounts/create')}>
                            <Plus className="h-4 w-4 mr-2" />
                            Create Account
                        </Button>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {accounts.map((account) => (
                            <div key={account.id} className="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                                <div className="flex items-start justify-between mb-4">
                                    <div className="flex items-center gap-3">
                                        <div className="p-2 bg-teal-50 rounded-lg">
                                            {getTypeIcon(account.type)}
                                        </div>
                                        <div>
                                            <h3 className="font-semibold text-gray-900">{account.name}</h3>
                                            <p className="text-sm text-gray-500 capitalize">{account.type.replace('_', ' ')}</p>
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <Link
                                            href={`/money/accounts/${account.id}`}
                                            className="p-2 text-gray-400 hover:text-gray-600"
                                        >
                                            <Eye className="h-4 w-4" />
                                        </Link>
                                        <Link
                                            href={`/money/accounts/${account.id}/edit`}
                                            className="p-2 text-gray-400 hover:text-gray-600"
                                        >
                                            <Edit className="h-4 w-4" />
                                        </Link>
                                    </div>
                                </div>
                                <div className="border-t border-gray-100 pt-4">
                                    <div className="flex justify-between items-center">
                                        <span className="text-sm text-gray-500">Current Balance</span>
                                        <span className="text-2xl font-bold text-gray-900">
                                            {formatCurrency(account.current_balance)}
                                        </span>
                                    </div>
                                    {account.account_number && (
                                        <p className="text-xs text-gray-400 mt-2">Account: {account.account_number}</p>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}

