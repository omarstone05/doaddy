import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, RotateCcw } from 'lucide-react';

export default function SaleReturnsShow({ return: returnRecord }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Return - ${returnRecord.return_number}`} />
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
                            <h1 className="text-3xl font-bold text-gray-900">Return {returnRecord.return_number}</h1>
                            <p className="text-gray-500 mt-1">
                                Sale {returnRecord.sale?.sale_number} • {returnRecord.sale?.customer_name}
                            </p>
                        </div>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Original Sale</h3>
                            <Link
                                href={`/pos/sales/${returnRecord.sale?.id}`}
                                className="text-teal-600 hover:text-teal-700 font-medium"
                            >
                                {returnRecord.sale?.sale_number}
                            </Link>
                            <p className="text-sm text-gray-600 mt-1">
                                {new Date(returnRecord.sale?.sale_date).toLocaleDateString()}
                            </p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Return Details</h3>
                            <p className="text-gray-900">Date: {new Date(returnRecord.return_date).toLocaleDateString()}</p>
                            <p className="text-gray-900">
                                Refund Method: {returnRecord.refund_method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                            </p>
                            {returnRecord.refund_reference && (
                                <p className="text-sm text-gray-600">Reference: {returnRecord.refund_reference}</p>
                            )}
                        </div>
                    </div>

                    {/* Returned Items */}
                    <div className="mb-6">
                        <h3 className="text-sm font-medium text-gray-500 mb-3">Returned Items</h3>
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Product</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Quantity Returned</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Refund Amount</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {returnRecord.items?.map((item, index) => (
                                    <tr key={index}>
                                        <td className="px-4 py-3 text-gray-900">{item.sale_item?.product_name}</td>
                                        <td className="px-4 py-3 text-right text-gray-600">{item.quantity_returned}</td>
                                        <td className="px-4 py-3 text-right font-medium text-gray-900">
                                            {formatCurrency(item.refund_amount)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Total */}
                    <div className="border-t border-gray-200 pt-4">
                        <div className="flex justify-between items-center">
                            <span className="text-lg font-semibold text-gray-900">Total Refund</span>
                            <span className="text-2xl font-bold text-gray-900">
                                {formatCurrency(returnRecord.return_amount)}
                            </span>
                        </div>
                    </div>

                    {returnRecord.return_reason && (
                        <div className="mt-6 pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Return Reason</h3>
                            <p className="text-gray-900">{returnRecord.return_reason}</p>
                        </div>
                    )}

                    <div className="mt-6 pt-4 border-t border-gray-200">
                        <h3 className="text-sm font-medium text-gray-500 mb-2">Processed By</h3>
                        <p className="text-gray-900">
                            {returnRecord.processed_by?.first_name} {returnRecord.processed_by?.last_name}
                        </p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

