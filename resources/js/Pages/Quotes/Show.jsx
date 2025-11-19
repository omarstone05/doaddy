import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import FileUpload from '@/Components/FileUpload';
import { ArrowLeft, FileText, CheckCircle, XCircle, Clock, Edit, Trash2, Download } from 'lucide-react';

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
            <div className="max-w-5xl mx-auto">
                {/* Header */}
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => window.history.back()}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    
                    <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                        <div className="flex items-start justify-between mb-6">
                            <div className="flex-1">
                                <div className="flex items-center gap-3 mb-2">
                                    <h1 className="text-3xl font-bold text-gray-900">Quote {quote.quote_number}</h1>
                                    {getStatusBadge(quote.status)}
                                </div>
                                <p className="text-gray-500 text-lg">{quote.customer?.name}</p>
                            </div>
                            
                            {/* Action Buttons - Grouped */}
                            <div className="flex flex-col gap-2 items-end">
                                {/* Primary Actions */}
                                <div className="flex gap-2">
                                    <a
                                        href={`/quotes/${quote.id}/download`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <Button variant="secondary">
                                            <Download className="h-4 w-4 mr-2" />
                                            Download PDF
                                        </Button>
                                    </a>
                                    {quote.status === 'accepted' && !quote.invoice_id && (
                                        <Button onClick={handleConvert}>
                                            Convert to Invoice
                                        </Button>
                                    )}
                                </div>
                                
                                {/* Secondary Actions */}
                                {!quote.invoice_id && (
                                    <div className="flex gap-2">
                                        <Link href={`/quotes/${quote.id}/edit`}>
                                            <Button variant="secondary" size="sm">
                                                <Edit className="h-4 w-4 mr-2" />
                                                Edit
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="secondary"
                                            size="sm"
                                            onClick={() => {
                                                if (confirm('Are you sure you want to delete this quote? This action cannot be undone.')) {
                                                    router.delete(`/quotes/${quote.id}`);
                                                }
                                            }}
                                        >
                                            <Trash2 className="h-4 w-4 mr-2" />
                                            Delete
                                        </Button>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Summary Cards */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-200">
                            <div>
                                <h3 className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Customer</h3>
                                <p className="text-sm font-medium text-gray-900">{quote.customer?.name}</p>
                                {quote.customer?.email && (
                                    <p className="text-sm text-gray-600 mt-1">{quote.customer.email}</p>
                                )}
                                {quote.customer?.phone && (
                                    <p className="text-sm text-gray-600">{quote.customer.phone}</p>
                                )}
                            </div>
                            <div>
                                <h3 className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Quote Date</h3>
                                <p className="text-sm font-medium text-gray-900">{new Date(quote.quote_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })}</p>
                                {quote.expiry_date && (
                                    <>
                                        <h3 className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2 mt-3">Expiry Date</h3>
                                        <p className="text-sm font-medium text-gray-900">{new Date(quote.expiry_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })}</p>
                                    </>
                                )}
                            </div>
                            <div>
                                <h3 className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Total Amount</h3>
                                <p className="text-2xl font-bold text-gray-900">{formatCurrency(quote.total_amount)}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">

                    {/* Items */}
                    <div className="mb-6">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Product / Service</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Description</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Quantity</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Unit Price</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {quote.items?.map((item, index) => (
                                    <tr key={index}>
                                        <td className="px-4 py-3">
                                            <div className="font-semibold text-gray-900">{item.name}</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            {item.description ? (
                                                <div className="text-sm text-gray-600">{item.description}</div>
                                            ) : (
                                                <div className="text-sm text-gray-400">—</div>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right text-gray-600">{item.quantity}</td>
                                        <td className="px-4 py-3 text-right text-gray-600">{formatCurrency(item.unit_price)}</td>
                                        <td className="px-4 py-3 text-right font-medium text-gray-900">{formatCurrency(item.total)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Totals - Right Aligned */}
                    <div className="border-t border-gray-200 pt-4">
                        <div className="flex justify-end">
                            <div className="w-full max-w-xs space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-600">Subtotal:</span>
                                    <span className="text-gray-900 font-medium">{formatCurrency(quote.subtotal)}</span>
                                </div>
                                {quote.tax_amount > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600">Tax:</span>
                                        <span className="text-gray-900 font-medium">{formatCurrency(quote.tax_amount)}</span>
                                    </div>
                                )}
                                {quote.discount_amount > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600">Discount:</span>
                                        <span className="text-red-600 font-medium">-{formatCurrency(quote.discount_amount)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between text-lg font-bold pt-2 border-t-2 border-gray-300">
                                    <span>Total:</span>
                                    <span>{formatCurrency(quote.total_amount)}</span>
                                </div>
                            </div>
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
                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-4">
                    <FileUpload
                        attachableType="App\Models\Quote"
                        attachableId={quote.id}
                        category="quote"
                        existingAttachments={quote.attachments || []}
                    />
                </div>

                {/* Settings Note */}
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p className="text-sm text-blue-800 text-center">
                        To change the information displayed on your quote,{' '}
                        <Link href="/settings/invoices" className="text-blue-600 hover:text-blue-800 underline font-medium">
                            go to Invoice Settings
                        </Link>
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

