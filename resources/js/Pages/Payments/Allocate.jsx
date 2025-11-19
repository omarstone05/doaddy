import { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';

export default function PaymentsAllocate({ payment, invoices, unallocatedAmount }) {
    const [allocations, setAllocations] = useState([]);

    const { post, processing, errors } = useForm({
        allocations: [],
    });

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const addAllocation = () => {
        setAllocations([
            ...allocations,
            {
                invoice_id: '',
                amount: '',
            },
        ]);
    };

    const removeAllocation = (index) => {
        setAllocations(allocations.filter((_, i) => i !== index));
    };

    const updateAllocation = (index, field, value) => {
        const updated = [...allocations];
        updated[index] = { ...updated[index], [field]: value };
        
        // Auto-fill amount with outstanding amount when invoice is selected
        if (field === 'invoice_id' && value) {
            const invoice = invoices.find(inv => inv.id === value);
            if (invoice) {
                updated[index].amount = invoice.outstanding_amount.toFixed(2);
            }
        }
        
        setAllocations(updated);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Filter out empty allocations
        const validAllocations = allocations.filter(
            alloc => alloc.invoice_id && alloc.amount && parseFloat(alloc.amount) > 0
        );

        if (validAllocations.length === 0) {
            alert('Please add at least one allocation');
            return;
        }

        // Calculate total allocated
        const totalAllocated = validAllocations.reduce(
            (sum, alloc) => sum + parseFloat(alloc.amount || 0),
            0
        );

        if (totalAllocated > unallocatedAmount) {
            alert(`Total allocation (${formatCurrency(totalAllocated)}) exceeds unallocated amount (${formatCurrency(unallocatedAmount)})`);
            return;
        }

        post(`/payments/${payment.id}/allocate`, {
            data: {
                allocations: validAllocations.map(alloc => ({
                    invoice_id: alloc.invoice_id,
                    amount: parseFloat(alloc.amount),
                })),
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Allocate Payment - ${payment.payment_number}`} />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href={`/payments/${payment.id}`}>
                        <Button variant="ghost" className="mb-4">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Back to Payment
                        </Button>
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Allocate Payment</h1>
                    <p className="text-gray-500 mt-1">
                        Payment {payment.payment_number} - {payment.customer?.name}
                    </p>
                </div>

                <Card className="p-6 mb-6">
                    <div className="mb-4">
                        <div className="flex justify-between items-center">
                            <span className="text-sm font-medium text-gray-500">Payment Amount</span>
                            <span className="text-lg font-bold text-gray-900">
                                {formatCurrency(payment.amount)}
                            </span>
                        </div>
                        <div className="flex justify-between items-center mt-2">
                            <span className="text-sm font-medium text-gray-500">Unallocated Amount</span>
                            <span className="text-lg font-bold text-yellow-600">
                                {formatCurrency(unallocatedAmount)}
                            </span>
                        </div>
                    </div>
                </Card>

                <Card className="p-6">
                    <form onSubmit={handleSubmit}>
                        <div className="mb-4">
                            <div className="flex items-center justify-between mb-4">
                                <h2 className="text-lg font-semibold text-gray-900">Allocate to Invoices</h2>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={addAllocation}
                                    disabled={allocations.length >= invoices.length}
                                >
                                    <Plus className="h-4 w-4 mr-2" />
                                    Add Invoice
                                </Button>
                            </div>

                            {allocations.length === 0 ? (
                                <div className="text-center py-8 text-gray-500">
                                    <p>No allocations added yet. Click "Add Invoice" to start.</p>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {allocations.map((allocation, index) => {
                                        const selectedInvoice = invoices.find(
                                            inv => inv.id === allocation.invoice_id
                                        );
                                        const availableInvoices = invoices.filter(
                                            inv => !allocations.some(
                                                (a, i) => i !== index && a.invoice_id === inv.id
                                            )
                                        );

                                        return (
                                            <div
                                                key={index}
                                                className="flex gap-4 items-start p-4 border border-gray-200 rounded-lg"
                                            >
                                                <div className="flex-1">
                                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                                        Invoice *
                                                    </label>
                                                    <select
                                                        value={allocation.invoice_id}
                                                        onChange={(e) =>
                                                            updateAllocation(index, 'invoice_id', e.target.value)
                                                        }
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                                        required
                                                    >
                                                        <option value="">Select Invoice</option>
                                                        {availableInvoices.map((invoice) => (
                                                            <option key={invoice.id} value={invoice.id}>
                                                                {invoice.invoice_number} - Outstanding: {formatCurrency(invoice.outstanding_amount)}
                                                            </option>
                                                        ))}
                                                        {selectedInvoice && !availableInvoices.find(inv => inv.id === selectedInvoice.id) && (
                                                            <option value={selectedInvoice.id}>
                                                                {selectedInvoice.invoice_number} - Outstanding: {formatCurrency(selectedInvoice.outstanding_amount)}
                                                            </option>
                                                        )}
                                                    </select>
                                                    {selectedInvoice && (
                                                        <p className="text-xs text-gray-500 mt-1">
                                                            Invoice Date: {new Date(selectedInvoice.invoice_date).toLocaleDateString()} | 
                                                            Total: {formatCurrency(selectedInvoice.total_amount)} | 
                                                            Paid: {formatCurrency(selectedInvoice.paid_amount)} | 
                                                            Outstanding: {formatCurrency(selectedInvoice.outstanding_amount)}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="w-48">
                                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                                        Amount *
                                                    </label>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0.01"
                                                        max={selectedInvoice ? selectedInvoice.outstanding_amount : unallocatedAmount}
                                                        value={allocation.amount}
                                                        onChange={(e) =>
                                                            updateAllocation(index, 'amount', e.target.value)
                                                        }
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                                        required
                                                    />
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    onClick={() => removeAllocation(index)}
                                                    className="mt-7"
                                                >
                                                    <Trash2 className="h-4 w-4 text-red-500" />
                                                </Button>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>

                        {errors.allocations && (
                            <div className="mb-4 text-red-600 text-sm">{errors.allocations}</div>
                        )}

                        {allocations.length > 0 && (
                            <div className="mt-6 pt-6 border-t border-gray-200">
                                <div className="flex justify-between items-center mb-4">
                                    <span className="text-sm font-medium text-gray-700">Total Allocated</span>
                                    <span className="text-lg font-bold text-gray-900">
                                        {formatCurrency(
                                            allocations.reduce(
                                                (sum, alloc) => sum + parseFloat(alloc.amount || 0),
                                                0
                                            )
                                        )}
                                    </span>
                                </div>
                                <div className="flex justify-end gap-4">
                                    <Link href={`/payments/${payment.id}`}>
                                        <Button type="button" variant="outline">
                                            Cancel
                                        </Button>
                                    </Link>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Allocating...' : 'Allocate Payment'}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </form>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

