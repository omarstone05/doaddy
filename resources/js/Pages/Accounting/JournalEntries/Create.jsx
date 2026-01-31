import { Head, useForm, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { ArrowLeft, Plus, X, AlertCircle } from 'lucide-react';
import { useState } from 'react';

export default function JournalEntriesCreate({ accounts }) {
    const [lines, setLines] = useState([
        { account_id: '', type: 'debit', amount: '', description: '', reference: '' },
        { account_id: '', type: 'credit', amount: '', description: '', reference: '' },
    ]);

    const { data, setData, post, processing, errors } = useForm({
        entry_date: new Date().toISOString().split('T')[0],
        description: '',
        reference: '',
        type: 'manual',
        lines: [],
    });

    const addLine = () => {
        setLines([...lines, { account_id: '', type: 'debit', amount: '', description: '', reference: '' }]);
    };

    const removeLine = (index) => {
        if (lines.length > 2) {
            setLines(lines.filter((_, i) => i !== index));
        }
    };

    const updateLine = (index, field, value) => {
        const updatedLines = [...lines];
        updatedLines[index] = { ...updatedLines[index], [field]: value };
        setLines(updatedLines);
    };

    const calculateTotals = () => {
        const debits = lines
            .filter(line => line.type === 'debit' && line.account_id && line.amount)
            .reduce((sum, line) => sum + parseFloat(line.amount || 0), 0);
        const credits = lines
            .filter(line => line.type === 'credit' && line.account_id && line.amount)
            .reduce((sum, line) => sum + parseFloat(line.amount || 0), 0);
        return { debits, credits, difference: Math.abs(debits - credits) };
    };

    const totals = calculateTotals();
    const isBalanced = totals.difference < 0.01 && totals.debits > 0;

    const submit = (e) => {
        e.preventDefault();

        // Validate lines
        const validLines = lines.filter(line => line.account_id && line.amount && parseFloat(line.amount) > 0);
        if (validLines.length < 2) {
            alert('Please add at least two journal entry lines');
            return;
        }

        if (!isBalanced) {
            alert('Journal entry must be balanced. Total debits must equal total credits.');
            return;
        }

        const formattedLines = validLines.map((line, index) => ({
            account_id: line.account_id,
            type: line.type,
            amount: parseFloat(line.amount),
            description: line.description || '',
            reference: line.reference || '',
        }));

        setData('lines', formattedLines);

        post('/accounting/journal-entries', {
            preserveScroll: true,
            onSuccess: () => {
                router.visit('/accounting/journal-entries');
            },
        });
    };

    return (
        <SectionLayout sectionName="Accounting">
            <Head title="Create Journal Entry" />
            <div className="max-w-6xl mx-auto">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => router.visit('/accounting/journal-entries')}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <h1 className="text-3xl font-bold text-gray-900">Create Journal Entry</h1>
                    <p className="text-gray-500 mt-1">Record a new accounting transaction</p>
                </div>

                <form onSubmit={submit}>
                    <Card className="p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Entry Details</h2>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label htmlFor="entry_date" className="block text-sm font-medium text-gray-700 mb-2">
                                    Entry Date *
                                </label>
                                <input
                                    id="entry_date"
                                    type="date"
                                    value={data.entry_date}
                                    onChange={(e) => setData('entry_date', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.entry_date && <p className="mt-1 text-sm text-red-600">{errors.entry_date}</p>}
                            </div>
                            <div className="md:col-span-2">
                                <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
                                    Description *
                                </label>
                                <input
                                    id="description"
                                    type="text"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Brief description of the transaction"
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                            </div>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label htmlFor="reference" className="block text-sm font-medium text-gray-700 mb-2">
                                    Reference
                                </label>
                                <input
                                    id="reference"
                                    type="text"
                                    value={data.reference}
                                    onChange={(e) => setData('reference', e.target.value)}
                                    placeholder="Invoice number, check number, etc."
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label htmlFor="type" className="block text-sm font-medium text-gray-700 mb-2">
                                    Entry Type
                                </label>
                                <select
                                    id="type"
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="manual">Manual</option>
                                    <option value="adjusting">Adjusting</option>
                                    <option value="closing">Closing</option>
                                    <option value="recurring">Recurring</option>
                                </select>
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6 mb-6">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-lg font-semibold text-gray-900">Journal Entry Lines</h2>
                            <Button type="button" variant="outline" onClick={addLine} className="gap-2">
                                <Plus className="h-4 w-4" />
                                Add Line
                            </Button>
                        </div>

                        {!isBalanced && totals.debits > 0 && (
                            <div className="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg flex items-center gap-2">
                                <AlertCircle className="h-5 w-5 text-yellow-600" />
                                <span className="text-sm text-yellow-800">
                                    Entry is not balanced. Difference: {totals.difference.toFixed(2)}
                                </span>
                            </div>
                        )}

                        <div className="space-y-3">
                            {lines.map((line, index) => (
                                <div key={index} className="grid grid-cols-12 gap-3 items-end p-3 bg-gray-50 rounded-lg">
                                    <div className="col-span-4">
                                        <label className="block text-xs font-medium text-gray-700 mb-1">Account *</label>
                                        <select
                                            value={line.account_id}
                                            onChange={(e) => updateLine(index, 'account_id', e.target.value)}
                                            className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                            required
                                        >
                                            <option value="">Select Account</option>
                                            {accounts.map((account) => (
                                                <option key={account.id} value={account.id}>
                                                    {account.code} - {account.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="col-span-2">
                                        <label className="block text-xs font-medium text-gray-700 mb-1">Type *</label>
                                        <select
                                            value={line.type}
                                            onChange={(e) => updateLine(index, 'type', e.target.value)}
                                            className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        >
                                            <option value="debit">Debit</option>
                                            <option value="credit">Credit</option>
                                        </select>
                                    </div>
                                    <div className="col-span-2">
                                        <label className="block text-xs font-medium text-gray-700 mb-1">Amount *</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            value={line.amount}
                                            onChange={(e) => updateLine(index, 'amount', e.target.value)}
                                            className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                            required
                                        />
                                    </div>
                                    <div className="col-span-3">
                                        <label className="block text-xs font-medium text-gray-700 mb-1">Description</label>
                                        <input
                                            type="text"
                                            value={line.description}
                                            onChange={(e) => updateLine(index, 'description', e.target.value)}
                                            className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        />
                                    </div>
                                    <div className="col-span-1">
                                        {lines.length > 2 && (
                                            <button
                                                type="button"
                                                onClick={() => removeLine(index)}
                                                className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            >
                                                <X className="h-4 w-4" />
                                            </button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                            <div className="grid grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span className="text-gray-600">Total Debits:</span>
                                    <span className="ml-2 font-bold text-gray-900">
                                        {totals.debits.toFixed(2)}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-gray-600">Total Credits:</span>
                                    <span className="ml-2 font-bold text-gray-900">
                                        {totals.credits.toFixed(2)}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-gray-600">Difference:</span>
                                    <span className={`ml-2 font-bold ${
                                        totals.difference < 0.01 ? 'text-green-600' : 'text-red-600'
                                    }`}>
                                        {totals.difference.toFixed(2)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <div className="flex justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit('/accounting/journal-entries')}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing || !isBalanced}>
                            {processing ? 'Creating...' : 'Create Journal Entry'}
                        </Button>
                    </div>
                </form>
            </div>
        </SectionLayout>
    );
}

