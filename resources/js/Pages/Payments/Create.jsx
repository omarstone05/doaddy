import { useState, useEffect } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import axios from 'axios';

export default function PaymentsCreate({ customers, accounts, invoices: initialInvoices, selectedCustomerId, prefillAllocation }) {
    const [selectedCustomer, setSelectedCustomer] = useState(selectedCustomerId || '');
    const [invoices, setInvoices] = useState([]);
    const [allocations, setAllocations] = useState([]);

    const { data, setData, post, processing, errors } = useForm({
        customer_id: selectedCustomerId || '',
        amount: '',
        currency: 'ZMW',
        payment_date: new Date().toISOString().split('T')[0],
        payment_method: 'cash',
        payment_reference: '',
        money_account_id: '',
        notes: '',
        allocations: [],
    });

    // Auto-fill allocation and amount when prefillAllocation is provided
    useEffect(() => {
        if (prefillAllocation) {
            // Set payment amount to outstanding amount
            setData('amount', prefillAllocation.amount.toFixed(2));
            
            // Add allocation automatically
            setAllocations([{
                invoice_id: prefillAllocation.invoice_id,
                amount: prefillAllocation.amount.toFixed(2),
            }]);
        }
    }, [prefillAllocation]);

    useEffect(() => {
        if (selectedCustomer) {
            setData('customer_id', selectedCustomer);
            axios.get(`/invoices?customer_id=${selectedCustomer}&status=unpaid`)
                .then(response => {
                    setInvoices(response.data);
                })
                .catch(error => console.error('Error fetching invoices:', error));
        } else {
            setInvoices([]);
        }
    }, [selectedCustomer]);

    const addAllocation = () => {
        setAllocations([...allocations, { invoice_id: '', amount: '' }]);
    };

    const removeAllocation = (index) => {
        setAllocations(allocations.filter((_, i) => i !== index));
    };

    const updateAllocation = (index, field, value) => {
        const newAllocations = [...allocations];
        newAllocations[index][field] = value;
        
        // If invoice selected, pre-fill amount with outstanding balance
        if (field === 'invoice_id' && value) {
            const invoice = invoices.find(inv => inv.id === value);
            if (invoice) {
                const outstanding = invoice.total_amount - (invoice.paid_amount || 0);
                newAllocations[index].amount = outstanding > 0 ? outstanding.toFixed(2) : '';
            }
        }
        
        setAllocations(newAllocations);
    };

    const submit = (e) => {
        e.preventDefault();
        
        // Include allocations if any
        const validAllocations = allocations.filter(a => a.invoice_id && a.amount > 0);
        if (validAllocations.length > 0) {
            setData('allocations', validAllocations.map(a => ({
                invoice_id: a.invoice_id,
                amount: parseFloat(a.amount),
            })));
        }

        // If prefillAllocation exists but no allocations were manually added, pass invoice_id
        if (prefillAllocation && validAllocations.length === 0) {
            setData('invoice_id', prefillAllocation.invoice_id);
        }

        post('/payments');
    };

    const totalAllocated = allocations.reduce((sum, a) => sum + (parseFloat(a.amount) || 0), 0);

    return (
        <AuthenticatedLayout>
            <Head title="Record Payment" />
            <div className="max-w-4xl mx-auto ">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => window.history.back()}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <h1 className="text-3xl font-bold text-gray-900">Record Payment</h1>
                    <p className="text-gray-500 mt-1">Record a payment from a customer</p>
                </div>

                <form onSubmit={submit} className="bg-white border border-gray-200 rounded-lg p-6">
                    <div className="space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="customer_id" className="block text-sm font-medium text-gray-700 mb-2">
                                    Customer *
                                </label>
                                <select
                                    id="customer_id"
                                    value={selectedCustomer}
                                    onChange={(e) => {
                                        setSelectedCustomer(e.target.value);
                                        setData('customer_id', e.target.value);
                                    }}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                >
                                    <option value="">Select customer</option>
                                    {customers.map((customer) => (
                                        <option key={customer.id} value={customer.id}>
                                            {customer.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.customer_id && <p className="mt-1 text-sm text-red-600">{errors.customer_id}</p>}
                            </div>

                            <div>
                                <label htmlFor="payment_date" className="block text-sm font-medium text-gray-700 mb-2">
                                    Payment Date *
                                </label>
                                <input
                                    id="payment_date"
                                    type="date"
                                    value={data.payment_date}
                                    onChange={(e) => setData('payment_date', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
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
                            </div>

                            <div>
                                <label htmlFor="payment_method" className="block text-sm font-medium text-gray-700 mb-2">
                                    Payment Method *
                                </label>
                                <select
                                    id="payment_method"
                                    value={data.payment_method}
                                    onChange={(e) => setData('payment_method', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                >
                                    <option value="cash">Cash</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="card">Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label htmlFor="money_account_id" className="block text-sm font-medium text-gray-700 mb-2">
                                Deposit To Account
                            </label>
                            <select
                                id="money_account_id"
                                value={data.money_account_id}
                                onChange={(e) => setData('money_account_id', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                                <option value="">Select account</option>
                                {accounts.map((account) => (
                                    <option key={account.id} value={account.id}>
                                        {account.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {data.payment_method !== 'cash' && (
                            <div>
                                <label htmlFor="payment_reference" className="block text-sm font-medium text-gray-700 mb-2">
                                    Reference Number
                                </label>
                                <input
                                    id="payment_reference"
                                    type="text"
                                    value={data.payment_reference}
                                    onChange={(e) => setData('payment_reference', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="Transaction reference"
                                />
                            </div>
                        )}

                        {/* Allocations */}
                        {invoices.length > 0 && (
                            <div>
                                <div className="flex items-center justify-between mb-4">
                                    <label className="block text-sm font-medium text-gray-700">
                                        Allocate to Invoices (Optional)
                                    </label>
                                    <Button type="button" variant="secondary" size="sm" onClick={addAllocation}>
                                        <Plus className="h-4 w-4 mr-2" />
                                        Add Allocation
                                    </Button>
                                </div>

                                {allocations.length > 0 && (
                                    <div className="space-y-3 mb-4 p-4 bg-gray-50 rounded-lg">
                                        {allocations.map((allocation, index) => (
                                            <div key={index} className="grid grid-cols-12 gap-2 items-start">
                                                <div className="col-span-7">
                                                    <select
                                                        value={allocation.invoice_id}
                                                        onChange={(e) => updateAllocation(index, 'invoice_id', e.target.value)}
                                                        className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg"
                                                    >
                                                        <option value="">Select Invoice</option>
                                                        {invoices.map((invoice) => (
                                                            <option key={invoice.id} value={invoice.id}>
                                                                {invoice.invoice_number} - K{(invoice.total_amount - (invoice.paid_amount || 0)).toFixed(2)} outstanding
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                                <div className="col-span-4">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        value={allocation.amount}
                                                        onChange={(e) => updateAllocation(index, 'amount', e.target.value)}
                                                        placeholder="Amount"
                                                        className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg"
                                                    />
                                                </div>
                                                <div className="col-span-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => removeAllocation(index)}
                                                        className="p-2 text-red-500 hover:text-red-700"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                        {totalAllocated > parseFloat(data.amount || 0) && (
                                            <p className="text-sm text-red-600 mt-2">
                                                Total allocated ({totalAllocated.toFixed(2)}) exceeds payment amount ({data.amount || '0.00'})
                                            </p>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}

                        <div>
                            <label htmlFor="notes" className="block text-sm font-medium text-gray-700 mb-2">
                                Notes
                            </label>
                            <textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>

                        <div className="flex gap-4 pt-4 border-t border-gray-200">
                            <Button type="submit" disabled={processing} className="flex-1">
                                Record Payment
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
        </AuthenticatedLayout>
    );
}

