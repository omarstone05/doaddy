import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Edit, Trash2, DollarSign, TrendingUp } from 'lucide-react';

export default function BusinessValuationsIndex({ valuations }) {
    const handleDelete = (valuationId) => {
        if (confirm('Are you sure you want to delete this valuation?')) {
            router.delete(`/decisions/valuation/${valuationId}`);
        }
    };

    const formatCurrency = (amount, currency = 'ZMW') => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Business Valuations" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Business Valuations</h1>
                        <p className="text-gray-500 mt-1">Track your business valuation over time</p>
                    </div>
                    <Button onClick={() => router.visit('/decisions/valuation/create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        New Valuation
                    </Button>
                </div>

                {/* Valuations Table */}
                <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Valuation Date</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Valuation Amount</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Method</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Valued By</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {valuations.data.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="px-6 py-12 text-center text-gray-500">
                                        No valuations found. Create your first valuation to get started.
                                    </td>
                                </tr>
                            ) : (
                                valuations.data.map((valuation) => (
                                    <tr key={valuation.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-gray-900">
                                                {new Date(valuation.valuation_date).toLocaleDateString()}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="font-semibold text-gray-900 text-lg">
                                                {formatCurrency(valuation.valuation_amount, valuation.currency)}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {valuation.valuation_method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {valuation.valued_by?.name || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link href={`/decisions/valuation/${valuation.id}`}>
                                                    <button className="text-gray-400 hover:text-teal-600">
                                                        <Edit className="h-4 w-4" />
                                                    </button>
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(valuation.id)}
                                                    className="text-gray-400 hover:text-red-600"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {valuations.links && valuations.links.length > 3 && (
                    <div className="mt-4 flex items-center justify-between">
                        <div className="text-sm text-gray-500">
                            Showing {valuations.from} to {valuations.to} of {valuations.total} results
                        </div>
                        <div className="flex gap-2">
                            {valuations.links.map((link, index) => (
                                <button
                                    key={index}
                                    onClick={() => link.url && router.visit(link.url)}
                                    disabled={!link.url}
                                    className={`px-3 py-2 text-sm border rounded-lg ${
                                        link.active
                                            ? 'bg-teal-500 text-white border-teal-500'
                                            : link.url
                                            ? 'border-gray-300 hover:bg-gray-50'
                                            : 'border-gray-200 text-gray-400 cursor-not-allowed'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}

