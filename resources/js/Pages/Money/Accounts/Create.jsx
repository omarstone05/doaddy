import { Head, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function MoneyAccountsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        type: 'cash',
        account_number: '',
        bank_name: '',
        currency: 'ZMW',
        opening_balance: '0.00',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/money/accounts');
    };

    return (
        <SectionLayout sectionName="Money">
            <Head title="Create Money Account" />
            <div className="max-w-2xl mx-auto">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => window.history.back()}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <h1 className="text-3xl font-bold text-gray-900">Create Money Account</h1>
                    <p className="text-gray-500 mt-1">Add a new account to track your money</p>
                </div>

                <form onSubmit={submit} className="bg-white border border-gray-200 rounded-lg p-6">
                    <div className="space-y-6">
                        <div>
                            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
                                Account Name *
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            />
                            {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                        </div>

                        <div>
                            <label htmlFor="type" className="block text-sm font-medium text-gray-700 mb-2">
                                Account Type *
                            </label>
                            <select
                                id="type"
                                value={data.type}
                                onChange={(e) => setData('type', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                                <option value="cash">Cash</option>
                                <option value="bank">Bank Account</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="card">Card</option>
                                <option value="other">Other</option>
                            </select>
                            {errors.type && <p className="mt-1 text-sm text-red-600">{errors.type}</p>}
                        </div>

                        {data.type === 'bank' && (
                            <>
                                <div>
                                    <label htmlFor="bank_name" className="block text-sm font-medium text-gray-700 mb-2">
                                        Bank Name
                                    </label>
                                    <input
                                        id="bank_name"
                                        type="text"
                                        value={data.bank_name}
                                        onChange={(e) => setData('bank_name', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="account_number" className="block text-sm font-medium text-gray-700 mb-2">
                                        Account Number
                                    </label>
                                    <input
                                        id="account_number"
                                        type="text"
                                        value={data.account_number}
                                        onChange={(e) => setData('account_number', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                            </>
                        )}

                        {data.type === 'mobile_money' && (
                            <div>
                                <label htmlFor="account_number" className="block text-sm font-medium text-gray-700 mb-2">
                                    Mobile Number
                                </label>
                                <input
                                    id="account_number"
                                    type="text"
                                    value={data.account_number}
                                    onChange={(e) => setData('account_number', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                        )}

                        <div>
                            <label htmlFor="currency" className="block text-sm font-medium text-gray-700 mb-2">
                                Currency *
                            </label>
                            <select
                                id="currency"
                                value={data.currency}
                                onChange={(e) => setData('currency', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                                <option value="ZMW">ZMW - Zambian Kwacha</option>
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - British Pound</option>
                            </select>
                        </div>

                        <div>
                            <label htmlFor="opening_balance" className="block text-sm font-medium text-gray-700 mb-2">
                                Opening Balance *
                            </label>
                            <input
                                id="opening_balance"
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.opening_balance}
                                onChange={(e) => setData('opening_balance', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            />
                            {errors.opening_balance && <p className="mt-1 text-sm text-red-600">{errors.opening_balance}</p>}
                        </div>

                        <div className="flex gap-4 pt-4">
                            <Button type="submit" disabled={processing} className="flex-1">
                                Create Account
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => window.history.back()}
                            >
                                Cancel
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </SectionLayout>
    );
}

