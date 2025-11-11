import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import FileUpload from '@/Components/FileUpload';
import { ArrowLeft, FileText, CheckCircle, XCircle, Clock } from 'lucide-react';

export default function QuotesShow({ quote }) {
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
            sent: { color: 'bg-blue-100 text-blue-700', icon: FileText },
            accepted: { color: 'bg-green-100 text-green-700', icon: CheckCircle },
            rejected: { color: 'bg-red-100 text-red-700', icon: XCircle },
            expired: { color: 'bg-orange-100 text-orange-700', icon: Clock },
        };
        const badge = badges[status] || badges.draft;
        const Icon = badge.icon;
        return (
            <span className={`inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium ${badge.color}`}>
                <Icon className="h-4 w-4" />
                {status.charAt(0).toUpperCase() + status.slice(1)}
            </span>
        );
    };

    const handleConvert = () => {
        if (confirm('Convert this quote to an invoice?')) {
            router.post(`/quotes/${quote.id}/convert`);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Quote - ${quote.quote_number}`} />
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
                            <h1 className="text-3xl font-bold text-gray-900">Quote {quote.quote_number}</h1>
                            <p className="text-gray-500 mt-1">{quote.customer?.name}</p>
                        </div>
                        <div className="flex gap-2">
                            {getStatusBadge(quote.status)}
                            {quote.status === 'accepted' && (
                                <Button onClick={handleConvert}>
                                    Convert to Invoice
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Customer</h3>
                            <p className="text-gray-900">{quote.customer?.name}</p>
                            {quote.customer?.email && (
                                <p className="text-sm text-gray-600">{quote.customer.email}</p>
                            )}
                            {quote.customer?.phone && (
                                <p className="text-sm text-gray-600">{quote.customer.phone}</p>
                            )}
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Dates</h3>
                            <p className="text-gray-900">Quote Date: {new Date(quote.quote_date).toLocaleDateString()}</p>
                            {quote.expiry_date && (
                                <p className="text-gray-900">Expiry Date: {new Date(quote.expiry_date).toLocaleDateString()}</p>
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
                                {quote.items?.map((item, index) => (
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
                            <span className="text-gray-900">{formatCurrency(quote.subtotal)}</span>
                        </div>
                        {quote.tax_amount > 0 && (
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Tax:</span>
                                <span className="text-gray-900">{formatCurrency(quote.tax_amount)}</span>
                            </div>
                        )}
                        {quote.discount_amount > 0 && (
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Discount:</span>
                                <span className="text-red-600">-{formatCurrency(quote.discount_amount)}</span>
                            </div>
                        )}
                        <div className="flex justify-between text-lg font-bold pt-2 border-t border-gray-200">
                            <span>Total:</span>
                            <span>{formatCurrency(quote.total_amount)}</span>
                        </div>
                    </div>

                    {quote.notes && (
                        <div className="mt-6 pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Notes</h3>
                            <p className="text-gray-900">{quote.notes}</p>
                        </div>
                    )}

                    {quote.terms && (
                        <div className="mt-6 pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Terms & Conditions</h3>
                            <p className="text-gray-900">{quote.terms}</p>
                        </div>
                    )}
                </div>

                {/* Attachments */}
                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    <FileUpload
                        attachableType="App\Models\Quote"
                        attachableId={quote.id}
                        category="quote"
                        existingAttachments={quote.attachments || []}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

