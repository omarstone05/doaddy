import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, FileText, CheckCircle, XCircle, Clock, Send, DollarSign } from 'lucide-react';

export default function InvoicesShow({ invoice }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const getStatusBadge = (status) => {
        const badges = {
            draft: { color: 'bg-gray-100 text-gray-700', icon: Clock },
            sent: { color: 'bg-blue-100 text-blue-700', icon: Send },
            paid: { color: 'bg-green-100 text-green-700', icon: CheckCircle },
            partial: { color: 'bg-yellow-100 text-yellow-700', icon: Clock },
            overdue: { color: 'bg-red-100 text-red-700', icon: XCircle },
        };
        const badge = badges[invoice.status] || badges.draft;
        const Icon = badge.icon;
        return (
            <span className={`inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium ${badge.color}`}>
                <Icon className="h-4 w-4" />
                {invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1)}
            </span>
        );
    };

    const handleSend = () => {
        router.post(`/invoices/${invoice.id}/send`);
    };

    const outstandingAmount = invoice.total_amount - (invoice.paid_amount || 0);

    return (
        <AuthenticatedLayout>
            <Head title={`Invoice - ${invoice.invoice_number}`} />
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
                            <h1 className="text-3xl font-bold text-gray-900">Invoice {invoice.invoice_number}</h1>
                            <p className="text-gray-500 mt-1">{invoice.customer?.name}</p>
                        </div>
                        <div className="flex gap-2">
                            {getStatusBadge(invoice.status)}
                            {invoice.status === 'draft' && (
                                <Button onClick={handleSend}>
                                    <Send className="h-4 w-4 mr-2" />
                                    Send Invoice
                                </Button>
                            )}
                            {outstandingAmount > 0 && (
                                <Link href={`/payments/create?customer_id=${invoice.customer_id}`}>
                                    <Button>
                                        <DollarSign className="h-4 w-4 mr-2" />
                                        Record Payment
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
                            <p className="text-gray-900">{invoice.customer?.name}</p>
                            {invoice.customer?.email && (
                                <p className="text-sm text-gray-600">{invoice.customer.email}</p>
                            )}
                            {invoice.customer?.phone && (
                                <p className="text-sm text-gray-600">{invoice.customer.phone}</p>
                            )}
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Dates</h3>
                            <p className="text-gray-900">Invoice Date: {new Date(invoice.invoice_date).toLocaleDateString()}</p>
                            {invoice.due_date && (
                                <p className="text-gray-900">Due Date: {new Date(invoice.due_date).toLocaleDateString()}</p>
                            )}
                        </div>
                    </div>

                    {/* Items */}
                    <div className="mb-6">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Description</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Quantity</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Unit Price</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {invoice.items?.map((item, index) => (
                                    <tr key={index}>
                                        <td className="px-4 py-3 text-gray-900">{item.description}</td>
                                        <td className="px-4 py-3 text-right text-gray-600">{item.quantity}</td>
                                        <td className="px-4 py-3 text-right text-gray-600">{formatCurrency(item.unit_price)}</td>
                                        <td className="px-4 py-3 text-right font-medium text-gray-900">{formatCurrency(item.total)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Totals */}
                    <div className="border-t border-gray-200 pt-4 space-y-2">
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-600">Subtotal:</span>
                            <span className="text-gray-900">{formatCurrency(invoice.subtotal)}</span>
                        </div>
                        {invoice.tax_amount > 0 && (
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Tax:</span>
                                <span className="text-gray-900">{formatCurrency(invoice.tax_amount)}</span>
                            </div>
                        )}
                        {invoice.discount_amount > 0 && (
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Discount:</span>
                                <span className="text-red-600">-{formatCurrency(invoice.discount_amount)}</span>
                            </div>
                        )}
                        <div className="flex justify-between text-lg font-bold pt-2 border-t border-gray-200">
                            <span>Total:</span>
                            <span>{formatCurrency(invoice.total_amount)}</span>
                        </div>
                        {invoice.paid_amount > 0 && (
                            <div className="flex justify-between text-sm pt-2">
                                <span className="text-gray-600">Paid:</span>
                                <span className="text-green-600">{formatCurrency(invoice.paid_amount)}</span>
                            </div>
                        )}
                        {outstandingAmount > 0 && (
                            <div className="flex justify-between text-lg font-bold pt-2 border-t border-gray-200">
                                <span>Outstanding:</span>
                                <span className="text-red-600">{formatCurrency(outstandingAmount)}</span>
                            </div>
                        )}
                    </div>

                    {/* Payments */}
                    {invoice.payments && invoice.payments.length > 0 && (
                        <div className="mt-6 pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-3">Payment History</h3>
                            <div className="space-y-2">
                                {invoice.payments.map((paymentAllocation) => (
                                    <div key={paymentAllocation.id} className="flex justify-between items-center p-2 bg-gray-50 rounded">
                                        <div>
                                            <span className="text-sm text-gray-900">
                                                Payment {paymentAllocation.payment?.payment_number}
                                            </span>
                                            <span className="text-xs text-gray-500 ml-2">
                                                {new Date(paymentAllocation.payment?.payment_date).toLocaleDateString()}
                                            </span>
                                        </div>
                                        <span className="text-sm font-medium text-gray-900">
                                            {formatCurrency(paymentAllocation.amount)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {invoice.notes && (
                        <div className="mt-6 pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Notes</h3>
                            <p className="text-gray-900">{invoice.notes}</p>
                        </div>
                    )}

                    {invoice.terms && (
                        <div className="mt-6 pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Terms & Conditions</h3>
                            <p className="text-gray-900">{invoice.terms}</p>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

