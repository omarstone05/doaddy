import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Eye, FileText, CheckCircle, XCircle, Clock, Send, Edit, Trash2, Filter } from 'lucide-react';

export default function InvoicesIndex({ invoices, filters }) {
    const formatCurrency = (amount) => {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num);
    };

    const formatFullAmount = (amount) => {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num);
    };

    const getStatusBadge = (status) => {
        const badges = {
            draft: { color: 'bg-gray-100 text-gray-700', icon: Clock },
            sent: { color: 'bg-blue-100 text-blue-700', icon: Send },
            paid: { color: 'bg-teal-100 text-teal-700', icon: CheckCircle },
            partial: { color: 'bg-amber-100 text-amber-700', icon: Clock },
            overdue: { color: 'bg-red-100 text-red-600', icon: XCircle },
        };
        const badge = badges[status] || badges.draft;
        const Icon = badge.icon;
        return (
            <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ${badge.color}`}>
                <Icon className="h-3 w-3" />
                {status.charAt(0).toUpperCase() + status.slice(1)}
            </span>
        );
    };

    return (
        <SectionLayout sectionName="Sales">
            <Head title="Invoices" />
            <div>
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-black text-gray-900 tracking-tight">Invoices</h1>
                        <p className="text-gray-500 mt-1">Manage customer invoices</p>
                    </div>
                    <Button onClick={() => window.location.href = '/invoices/create'} className="gap-2">
                        <Plus className="h-4 w-4" />
                        New Invoice
                    </Button>
                </div>

                {/* Filters Card */}
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-4 border border-gray-200/50 mb-6">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
                            <Filter className="w-5 h-5 text-teal-600" />
                        </div>
                        <select
                            value={filters?.status || ''}
                            onChange={(e) => window.location.href = `/invoices?status=${e.target.value}`}
                            className="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                        >
                            <option value="">All Statuses</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="paid">Paid</option>
                            <option value="partial">Partial</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                </div>

                {invoices.data.length === 0 ? (
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-12 border border-gray-200/50 text-center">
                        <div className="w-16 h-16 rounded-2xl bg-teal-100 flex items-center justify-center mx-auto mb-4">
                            <FileText className="h-8 w-8 text-teal-500" />
                        </div>
                        <h3 className="text-lg font-bold text-gray-900 mb-2">No invoices yet</h3>
                        <p className="text-gray-500 mb-6">Create your first invoice to bill customers</p>
                        <Button onClick={() => window.location.href = '/invoices/create'} className="gap-2">
                            <Plus className="h-4 w-4" />
                            Create Invoice
                        </Button>
                    </div>
                ) : (
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 overflow-hidden">
                        <table className="w-full">
                            <thead className="bg-gray-50/80 border-b border-gray-200/50">
                                <tr>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Invoice
                                    </th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Customer
                                    </th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Amount
                                    </th>
                                    <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Paid
                                    </th>
                                    <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {invoices.data.map((invoice) => (
                                    <tr key={invoice.id} className="hover:bg-teal-50/30 transition-colors">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <p className="font-bold text-gray-900">{invoice.invoice_number}</p>
                                        </td>
                                        <td className="px-6 py-4">
                                            <p className="text-sm font-medium text-gray-900">{invoice.customer?.name}</p>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {new Date(invoice.invoice_date).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            {getStatusBadge(invoice.status)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                                            {formatFullAmount(invoice.total_amount)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-teal-600">
                                            {formatFullAmount(invoice.paid_amount || 0)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center justify-center gap-1">
                                                <Link
                                                    href={`/invoices/${invoice.id}`}
                                                    className="p-2 rounded-lg text-teal-600 hover:bg-teal-100 transition-colors"
                                                    title="View"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                                {(invoice.status === 'draft' || invoice.status === 'sent' || invoice.status === 'overdue') && (!invoice.paid_amount || parseFloat(invoice.paid_amount || 0) === 0) && (
                                                    <>
                                                        <Link
                                                            href={`/invoices/${invoice.id}/edit`}
                                                            className="p-2 rounded-lg text-blue-600 hover:bg-blue-100 transition-colors"
                                                            title="Edit"
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </Link>
                                                        <button
                                                            onClick={() => {
                                                                if (confirm('Are you sure you want to delete this invoice?')) {
                                                                    router.delete(`/invoices/${invoice.id}`);
                                                                }
                                                            }}
                                                            className="p-2 rounded-lg text-red-500 hover:bg-red-100 transition-colors"
                                                            title="Delete"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        
                        {/* Pagination */}
                        {invoices.links && invoices.links.length > 3 && (
                            <div className="px-6 py-4 border-t border-gray-200/50 flex items-center justify-between bg-gray-50/50">
                                <p className="text-sm text-gray-600">
                                    Showing <span className="font-semibold">{invoices.from}</span> to <span className="font-semibold">{invoices.to}</span> of <span className="font-semibold">{invoices.total}</span>
                                </p>
                                <div className="flex gap-1">
                                    {invoices.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all ${
                                                link.active
                                                    ? 'bg-teal-500 text-white shadow-sm'
                                                    : 'bg-white border border-gray-200 text-gray-700 hover:bg-teal-50 hover:border-teal-200'
                                            } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}
