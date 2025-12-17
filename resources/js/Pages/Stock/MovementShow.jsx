import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { ArrowLeft, ArrowUp, ArrowDown } from 'lucide-react';

export default function StockMovementShow({ movement }) {
    return (
        <SectionLayout sectionName="Inventory">
            <Head title={`Stock Movement - ${movement.reference_number}`} />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link href="/stock/movements">
                        <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4">
                            <ArrowLeft className="h-4 w-4" />
                            Back to Movements
                        </button>
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900">Stock Movement</h1>
                    <p className="text-gray-500 mt-1">{movement.reference_number}</p>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    <div className="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Product</h3>
                            <p className="text-gray-900">{movement.goods_service?.name}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Date</h3>
                            <p className="text-gray-900">{new Date(movement.created_at).toLocaleString()}</p>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Movement Type</h3>
                            <span className={`inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium ${
                                movement.movement_type === 'in'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'
                            }`}>
                                {movement.movement_type === 'in' ? (
                                    <ArrowUp className="h-4 w-4" />
                                ) : (
                                    <ArrowDown className="h-4 w-4" />
                                )}
                                {movement.movement_type === 'in' ? 'Stock In' : 'Stock Out'}
                            </span>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Quantity</h3>
                            <p className="text-2xl font-bold text-gray-900">
                                {movement.quantity} {movement.goods_service?.unit || ''}
                            </p>
                        </div>
                    </div>

                    {movement.reference_number && (
                        <div className="mb-6 pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Reference Number</h3>
                            <p className="text-gray-900">{movement.reference_number}</p>
                        </div>
                    )}

                    {movement.notes && (
                        <div className="mb-6 pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Notes</h3>
                            <p className="text-gray-900">{movement.notes}</p>
                        </div>
                    )}

                    {movement.created_by && (
                        <div className="pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Created By</h3>
                            <p className="text-gray-900">{movement.created_by?.name}</p>
                        </div>
                    )}
                </div>
            </div>
        </SectionLayout>
    );
}

