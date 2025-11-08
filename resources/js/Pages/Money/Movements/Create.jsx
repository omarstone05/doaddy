import { Head, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function MoneyMovementsCreate({ accounts, type }) {
    const { data, setData, post, processing, errors } = useForm({
        flow_type: type || 'income',
        amount: '',
        currency: 'ZMW',
        transaction_date: new Date().toISOString().split('T')[0],
        from_account_id: '',
        to_account_id: '',
        description: '',
        category: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/money/movements');
    };

    return (
        <SectionLayout sectionName="Money">
            <Head title={`Record ${data.flow_type === 'income' ? 'Income' : data.flow_type === 'expense' ? 'Expense' : 'Transfer'}`} />
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
                    <h1 className="text-3xl font-bold text-gray-900">
                        Record {data.flow_type === 'income' ? 'Income' : data.flow_type === 'expense' ? 'Expense' : 'Transfer'}
                    </h1>
                    <p className="text-gray-500 mt-1">Record a new money movement</p>
                </div>

                <form onSubmit={submit} className="bg-white border border-gray-200 rounded-lg p-6">
                    <div className="space-y-6">
                        <div>
                            <label htmlFor="flow_type" className="block text-sm font-medium text-gray-700 mb-2">
                                Type *
                            </label>
                            <select
                                id="flow_type"
                                value={data.flow_type}
                                onChange={(e) => setData('flow_type', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                                <option value="transfer">Transfer</option>
                            </select>
                            {errors.flow_type && <p className="mt-1 text-sm text-red-600">{errors.flow_type}</p>}
                        </div>

                        <div>
                            <label htmlFor="amount" className="block text-sm font-medium text-gray-700 mb-2">
                                Amount *
                            </label>
                            <input
                                id="amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                value={data.amount}
                                onChange={(e) => setData('amount', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            />
                            {errors.amount && <p className="mt-1 text-sm text-red-600">{errors.amount}</p>}
                        </div>

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
                                <option value="ZMW">ZMW</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                        </div>

                        <div>
                            <label htmlFor="transaction_date" className="block text-sm font-medium text-gray-700 mb-2">
                                Transaction Date *
                            </label>
                            <input
                                id="transaction_date"
                                type="date"
                                value={data.transaction_date}
                                onChange={(e) => setData('transaction_date', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            />
                            {errors.transaction_date && <p className="mt-1 text-sm text-red-600">{errors.transaction_date}</p>}
                        </div>

                        {(data.flow_type === 'income' || data.flow_type === 'transfer') && (
                            <div>
                                <label htmlFor="to_account_id" className="block text-sm font-medium text-gray-700 mb-2">
                                    {data.flow_type === 'income' ? 'To Account' : 'To Account'} *
                                </label>
                                <select
                                    id="to_account_id"
                                    value={data.to_account_id}
                                    onChange={(e) => setData('to_account_id', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required={data.flow_type === 'income' || data.flow_type === 'transfer'}
                                >
                                    <option value="">Select account</option>
                                    {accounts.map((account) => (
                                        <option key={account.id} value={account.id}>
                                            {account.name} ({account.currency})
                                        </option>
                                    ))}
                                </select>
                                {errors.to_account_id && <p className="mt-1 text-sm text-red-600">{errors.to_account_id}</p>}
                            </div>
                        )}

                        {(data.flow_type === 'expense' || data.flow_type === 'transfer') && (
                            <div>
                                <label htmlFor="from_account_id" className="block text-sm font-medium text-gray-700 mb-2">
                                    {data.flow_type === 'expense' ? 'From Account' : 'From Account'} *
                                </label>
                                <select
                                    id="from_account_id"
                                    value={data.from_account_id}
                                    onChange={(e) => setData('from_account_id', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required={data.flow_type === 'expense' || data.flow_type === 'transfer'}
                                >
                                    <option value="">Select account</option>
                                    {accounts.map((account) => (
                                        <option key={account.id} value={account.id}>
                                            {account.name} ({account.currency})
                                        </option>
                                    ))}
                                </select>
                                {errors.from_account_id && <p className="mt-1 text-sm text-red-600">{errors.from_account_id}</p>}
                            </div>
                        )}

                        <div>
                            <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
                                Description *
                            </label>
                            <input
                                id="description"
                                type="text"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="e.g., Sale to customer, Office supplies"
                                required
                            />
                            {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                        </div>

                        <div>
                            <label htmlFor="category" className="block text-sm font-medium text-gray-700 mb-2">
                                Category
                            </label>
                            <input
                                id="category"
                                type="text"
                                value={data.category}
                                onChange={(e) => setData('category', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="e.g., Sales, Utilities, Rent"
                            />
                            {errors.category && <p className="mt-1 text-sm text-red-600">{errors.category}</p>}
                        </div>

                        <div className="flex gap-4 pt-4">
                            <Button type="submit" disabled={processing} className="flex-1">
                                Record Movement
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

