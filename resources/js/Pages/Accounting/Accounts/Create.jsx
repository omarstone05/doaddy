import { Head, useForm, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { ArrowLeft } from 'lucide-react';

export default function AccountsCreate({ accountTypes, parentAccounts }) {
    const { data, setData, post, processing, errors } = useForm({
        account_type_id: '',
        parent_account_id: '',
        code: '',
        name: '',
        description: '',
        normal_balance: 'debit',
        opening_balance: 0,
        is_sub_account: false,
        allows_postings: true,
        sort_order: 0,
        is_active: true,
    });

    const selectedAccountType = accountTypes.find(t => t.id === data.account_type_id);

    // Auto-set normal balance when account type changes
    const handleAccountTypeChange = (e) => {
        const typeId = e.target.value;
        setData('account_type_id', typeId);
        
        const type = accountTypes.find(t => t.id === typeId);
        if (type) {
            setData('normal_balance', type.normal_balance);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        post('/accounting/accounts', {
            preserveScroll: true,
            onSuccess: () => {
                router.visit('/accounting/accounts');
            },
        });
    };

    return (
        <SectionLayout sectionName="Accounting">
            <Head title="Create Account" />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => router.visit('/accounting/accounts')}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <h1 className="text-3xl font-bold text-gray-900">Create New Account</h1>
                    <p className="text-gray-500 mt-1">Add a new account to your chart of accounts</p>
                </div>

                <form onSubmit={submit}>
                    <Card className="p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Account Details</h2>
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="account_type_id" className="block text-sm font-medium text-gray-700 mb-2">
                                        Account Type *
                                    </label>
                                    <select
                                        id="account_type_id"
                                        value={data.account_type_id}
                                        onChange={handleAccountTypeChange}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="">Select Account Type</option>
                                        {accountTypes.map((type) => (
                                            <option key={type.id} value={type.id}>
                                                {type.name} ({type.code})
                                            </option>
                                        ))}
                                    </select>
                                    {errors.account_type_id && <p className="mt-1 text-sm text-red-600">{errors.account_type_id}</p>}
                                </div>
                                <div>
                                    <label htmlFor="parent_account_id" className="block text-sm font-medium text-gray-700 mb-2">
                                        Parent Account
                                    </label>
                                    <select
                                        id="parent_account_id"
                                        value={data.parent_account_id}
                                        onChange={(e) => setData('parent_account_id', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    >
                                        <option value="">None (Main Account)</option>
                                        {parentAccounts.map((account) => (
                                            <option key={account.id} value={account.id}>
                                                {account.code} - {account.name}
                                            </option>
                                        ))}
                                    </select>
                                    <p className="mt-1 text-xs text-gray-500">Select a parent account to create a sub-account</p>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="code" className="block text-sm font-medium text-gray-700 mb-2">
                                        Account Code *
                                    </label>
                                    <input
                                        id="code"
                                        type="text"
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                        placeholder="e.g., 1000, 1100"
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    />
                                    {errors.code && <p className="mt-1 text-sm text-red-600">{errors.code}</p>}
                                </div>
                                <div>
                                    <label htmlFor="normal_balance" className="block text-sm font-medium text-gray-700 mb-2">
                                        Normal Balance *
                                    </label>
                                    <select
                                        id="normal_balance"
                                        value={data.normal_balance}
                                        onChange={(e) => setData('normal_balance', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="debit">Debit</option>
                                        <option value="credit">Credit</option>
                                    </select>
                                    {selectedAccountType && (
                                        <p className="mt-1 text-xs text-gray-500">
                                            Default: {selectedAccountType.normal_balance}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div>
                                <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
                                    Account Name *
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g., Cash, Accounts Receivable, Sales Revenue"
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                            </div>

                            <div>
                                <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={3}
                                    placeholder="Optional description of the account"
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="opening_balance" className="block text-sm font-medium text-gray-700 mb-2">
                                        Opening Balance
                                    </label>
                                    <input
                                        id="opening_balance"
                                        type="number"
                                        step="0.01"
                                        value={data.opening_balance}
                                        onChange={(e) => setData('opening_balance', parseFloat(e.target.value) || 0)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="sort_order" className="block text-sm font-medium text-gray-700 mb-2">
                                        Sort Order
                                    </label>
                                    <input
                                        id="sort_order"
                                        type="number"
                                        value={data.sort_order}
                                        onChange={(e) => setData('sort_order', parseInt(e.target.value) || 0)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-6">
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={data.allows_postings}
                                        onChange={(e) => setData('allows_postings', e.target.checked)}
                                        className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                    />
                                    <span className="ml-2 text-sm text-gray-700">Allow postings to this account</span>
                                </label>
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                        className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                    />
                                    <span className="ml-2 text-sm text-gray-700">Active</span>
                                </label>
                            </div>
                        </div>
                    </Card>

                    <div className="flex justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit('/accounting/accounts')}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Creating...' : 'Create Account'}
                        </Button>
                    </div>
                </form>
            </div>
        </SectionLayout>
    );
}

