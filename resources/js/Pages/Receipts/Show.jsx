import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { Printer, ArrowLeft, Download } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function ReceiptsShow({ receipt, organization, logoUrl }) {
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
    const currentYear = new Date().getFullYear();

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
                    <div className="flex gap-2">
                        <a
                            href={`/receipts/${receipt.id}/download`}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Button variant="secondary">
                                <Download className="h-4 w-4 mr-2" />
                                Download PDF
                            </Button>
                        </a>
                        <Button onClick={() => window.print()}>
                            <Printer className="h-4 w-4 mr-2" />
                            Print Receipt
                        </Button>
                    </div>
                </div>

                {/* Receipt */}
                <div className="bg-white border border-gray-200 rounded-lg p-8 print:border-0 print:shadow-none receipt-content">
                    {/* Logo */}
                    {logoUrl && (
                        <div className="text-center mb-6 receipt-logo">
                            <img 
                                src={logoUrl} 
                                alt={organization?.name || 'Logo'} 
                                className="h-16 w-auto mx-auto object-contain"
                            />
                        </div>
                    )}

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
                    <div className="mt-8 pt-4 border-t border-gray-200 text-center text-xs text-gray-500 receipt-footer">
                        <p>Thank you for your payment!</p>
                        <p className="mt-1">This is a computer-generated receipt.</p>
                    </div>

                    {/* Penda Digital Footer - Print Only */}
                    <div className="mt-8 pt-4 border-t border-gray-200 receipt-penda-footer print-only">
                        <div className="flex flex-col items-center gap-3">
                            <img 
                                src="/assets/logos/penda.png" 
                                alt="Penda Digital" 
                                className="h-8 w-auto object-contain"
                            />
                            <div className="text-xs text-gray-500 text-center">
                                <p>© {currentYear} All rights reserved.</p>
                                <p className="mt-1">
                                    This is a product of Penda Digital, a registered company in the Republic of Zambia.
                                </p>
                                <p className="mt-1">Copyright © {currentYear} Penda Digital. All rights reserved.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Print Styles */}
                <style jsx>{`
                    @media print {
                        /* Hide web UI elements */
                        .no-print,
                        nav,
                        header,
                        footer:not(.receipt-penda-footer) {
                            display: none !important;
                        }
                        
                        /* Hide AuthenticatedLayout navigation and footer */
                        [class*="Navigation"],
                        [class*="navigation"],
                        footer.bg-white {
                            display: none !important;
                        }
                        
                        /* Show receipt content */
                        .receipt-content {
                            margin: 0;
                            padding: 20px;
                            border: none;
                            box-shadow: none;
                        }
                        
                        /* Show print-only footer */
                        .print-only {
                            display: block !important;
                        }
                        
                        /* Body styling */
                        body {
                            background: white;
                            margin: 0;
                            padding: 0;
                        }
                        
                        /* Page setup */
                        @page {
                            margin: 0.5cm;
                        }
                    }
                    
                    /* Hide print-only footer on screen */
                    .print-only {
                        display: none;
                    }
                `}</style>
            </div>
        </AuthenticatedLayout>
    );
}

