import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, DollarSign, Receipt, FileText } from 'lucide-react';

export default function PaymentsShow({ payment }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const unallocatedAmount = payment.amount - (payment.allocated_amount || 0);

    return (
        <AuthenticatedLayout>
            <Head title={`Payment - ${payment.payment_number}`} />
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
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Payment {payment.payment_number}</h1>
                            <p className="text-gray-500 mt-1">{payment.customer?.name}</p>
                        </div>
                        <div className="flex gap-2">
                            {payment.receipts && payment.receipts.length > 0 && (
                                <Link href={`/receipts/${payment.receipts[0].id}`}>
                                    <Button variant="secondary">
                                        <Receipt className="h-4 w-4 mr-2" />
                                        View Receipt
                                    </Button>
                                </Link>
                            )}
                        </div>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Customer</h3>
                            <p className="text-gray-900">{payment.customer?.name}</p>
                            {payment.customer?.email && (
                                <p className="text-sm text-gray-600">{payment.customer.email}</p>
                            )}
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Payment Details</h3>
                            <p className="text-gray-900">Date: {new Date(payment.payment_date).toLocaleDateString()}</p>
                            <p className="text-gray-900">
                                Method: {payment.payment_method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                            </p>
                            {payment.payment_reference && (
                                <p className="text-sm text-gray-600">Reference: {payment.payment_reference}</p>
                            )}
                        </div>
                    </div>

                    {/* Payment Amount */}
                    <div className="border-t border-gray-200 pt-4 mb-6">
                        <div className="flex justify-between items-center">
                            <span className="text-lg font-semibold text-gray-900">Payment Amount</span>
                            <span className="text-2xl font-bold text-gray-900">
                                {formatCurrency(payment.amount)}
                            </span>
                        </div>
                    </div>

                    {/* Allocations */}
                    {payment.allocations && payment.allocations.length > 0 && (
                        <div className="mb-6">
                            <h3 className="text-sm font-medium text-gray-500 mb-3">Allocated to Invoices</h3>
                            <div className="space-y-2">
                                {payment.allocations.map((allocation) => (
                                    <div key={allocation.id} className="flex justify-between items-center p-3 bg-gray-50 rounded">
                                        <div>
                                            <Link
                                                href={`/invoices/${allocation.invoice.id}`}
                                                className="text-teal-600 hover:text-teal-700 font-medium"
                                            >
                                                {allocation.invoice.invoice_number}
                                            </Link>
                                            <span className="text-xs text-gray-500 ml-2">
                                                {new Date(allocation.invoice.invoice_date).toLocaleDateString()}
                                            </span>
                                        </div>
                                        <span className="text-sm font-medium text-gray-900">
                                            {formatCurrency(allocation.amount)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {unallocatedAmount > 0 && (
                        <div className="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div className="flex justify-between items-center">
                                <span className="text-sm font-medium text-yellow-800">Unallocated Amount</span>
                                <span className="text-lg font-bold text-yellow-800">
                                    {formatCurrency(unallocatedAmount)}
                                </span>
                            </div>
                            <Link href={`/payments/${payment.id}/allocate`}>
                                <Button variant="secondary" className="mt-3">
                                    Allocate to Invoice
                                </Button>
                            </Link>
                        </div>
                    )}

                    {payment.notes && (
                        <div className="pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Notes</h3>
                            <p className="text-gray-900">{payment.notes}</p>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

