import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { Printer, ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function ReceiptsShow({ receipt }) {
    const formatCurrency = (amount, currency = 'ZMW') => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const formatDate = (date) => {
        return new Date(date).toLocaleDateString('en-GB', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const payment = receipt.payment;

    return (
        <AuthenticatedLayout>
            <Head title={`Receipt - ${receipt.receipt_number}`} />
            <div className="max-w-2xl mx-auto">
                <div className="mb-6 no-print">
                    <Link href={`/payments/${payment.id}`}>
                        <Button variant="ghost" className="mb-4">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Back to Payment
                        </Button>
                    </Link>
                    <Button onClick={() => window.print()}>
                        <Printer className="h-4 w-4 mr-2" />
                        Print Receipt
                    </Button>
                </div>

                {/* Receipt */}
                <div className="bg-white border border-gray-200 rounded-lg p-8 print:border-0 print:shadow-none">
                    {/* Header */}
                    <div className="text-center mb-6">
                        <h1 className="text-2xl font-bold text-gray-900 mb-2">Payment Receipt</h1>
                        <p className="text-sm text-gray-500">Receipt Number: {receipt.receipt_number}</p>
                    </div>

                    {/* Payment Details */}
                    <div className="border-b border-gray-200 pb-4 mb-4">
                        <div className="flex justify-between mb-2">
                            <span className="text-sm text-gray-600">Receipt Date:</span>
                            <span className="text-sm font-medium text-gray-900">{formatDate(receipt.receipt_date)}</span>
                        </div>
                        <div className="flex justify-between mb-2">
                            <span className="text-sm text-gray-600">Payment Number:</span>
                            <span className="text-sm font-medium text-gray-900">{payment.payment_number}</span>
                        </div>
                        <div className="flex justify-between mb-2">
                            <span className="text-sm text-gray-600">Payment Date:</span>
                            <span className="text-sm font-medium text-gray-900">{formatDate(payment.payment_date)}</span>
                        </div>
                        {payment.customer && (
                            <div className="flex justify-between">
                                <span className="text-sm text-gray-600">Customer:</span>
                                <span className="text-sm font-medium text-gray-900">{payment.customer.name}</span>
                            </div>
                        )}
                    </div>

                    {/* Payment Amount */}
                    <div className="mb-6">
                        <div className="bg-gray-50 rounded-lg p-4">
                            <div className="flex justify-between items-center">
                                <span className="text-lg font-medium text-gray-700">Amount Received:</span>
                                <span className="text-3xl font-bold text-gray-900">
                                    {formatCurrency(payment.amount, payment.currency)}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Payment Method */}
                    <div className="mb-4 pb-4 border-b border-gray-200">
                        <div className="flex justify-between text-sm mb-2">
                            <span className="text-gray-600">Payment Method:</span>
                            <span className="font-medium text-gray-900 capitalize">
                                {payment.payment_method.replace('_', ' ')}
                            </span>
                        </div>
                        {payment.payment_reference && (
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Reference:</span>
                                <span className="text-gray-900">{payment.payment_reference}</span>
                            </div>
                        )}
                    </div>

                    {/* Allocations */}
                    {payment.allocations && payment.allocations.length > 0 && (
                        <div className="mb-4 pb-4 border-b border-gray-200">
                            <h3 className="text-sm font-medium text-gray-700 mb-3">Allocated to Invoices:</h3>
                            <div className="space-y-2">
                                {payment.allocations.map((allocation) => (
                                    <div key={allocation.id} className="flex justify-between text-sm">
                                        <span className="text-gray-600">
                                            Invoice {allocation.invoice.invoice_number}
                                        </span>
                                        <span className="font-medium text-gray-900">
                                            {formatCurrency(allocation.amount, payment.currency)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Notes */}
                    {payment.notes && (
                        <div className="mb-4 pb-4 border-b border-gray-200">
                            <h3 className="text-sm font-medium text-gray-700 mb-2">Notes:</h3>
                            <p className="text-sm text-gray-600">{payment.notes}</p>
                        </div>
                    )}

                    {receipt.notes && (
                        <div className="mb-4 pb-4 border-b border-gray-200">
                            <h3 className="text-sm font-medium text-gray-700 mb-2">Receipt Notes:</h3>
                            <p className="text-sm text-gray-600">{receipt.notes}</p>
                        </div>
                    )}

                    {/* Footer */}
                    <div className="mt-8 pt-4 border-t border-gray-200 text-center text-xs text-gray-500">
                        <p>Thank you for your payment!</p>
                        <p className="mt-1">This is a computer-generated receipt.</p>
                    </div>
                </div>

                {/* Print Styles */}
                <style jsx>{`
                    @media print {
                        .no-print {
                            display: none;
                        }
                        body {
                            background: white;
                        }
                    }
                `}</style>
            </div>
        </AuthenticatedLayout>
    );
}

