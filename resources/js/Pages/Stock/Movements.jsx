import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Eye, ArrowUp, ArrowDown, Package } from 'lucide-react';

export default function StockMovements({ movements, products, filters }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    return (
        <SectionLayout sectionName="Inventory">
            <Head title="Stock Movements" />
            <div className="max-w-7xl mx-auto">
                <div className="mb-6">
                    <h1 className="text-3xl font-bold text-gray-900">Stock Movements</h1>
                    <p className="text-gray-500 mt-1">Track all stock movements and adjustments</p>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Product</label>
                            <select
                                value={filters?.product_id || ''}
                                onChange={(e) => router.visit(`/stock/movements?product_id=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Products</option>
                                {products.map((product) => (
                                    <option key={product.id} value={product.id}>{product.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Movement Type</label>
                            <select
                                value={filters?.movement_type || ''}
                                onChange={(e) => router.visit(`/stock/movements?movement_type=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Types</option>
                                <option value="in">In (Increase)</option>
                                <option value="out">Out (Decrease)</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                            <input
                                type="date"
                                value={filters?.date_from || ''}
                                onChange={(e) => router.visit(`/stock/movements?date_from=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                            <input
                                type="date"
                                value={filters?.date_to || ''}
                                onChange={(e) => router.visit(`/stock/movements?date_to=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                    </div>
                </div>

                {movements.data.length === 0 ? (
                    <div className="bg-white border border-gray-200 rounded-lg p-12 text-center">
                        <Package className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No stock movements yet</h3>
                        <p className="text-gray-500">Stock movements will appear here when products are sold or adjusted</p>
                    </div>
                ) : (
                    <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Product
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Reference
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Quantity
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Notes
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {movements.data.map((movement) => (
                                    <tr key={movement.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {new Date(movement.created_at).toLocaleString()}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-sm font-medium text-gray-900">
                                                {movement.goods_service?.name}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {movement.reference_number || '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                            <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                movement.movement_type === 'in'
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-red-100 text-red-700'
                                            }`}>
                                                {movement.movement_type === 'in' ? (
                                                    <ArrowUp className="h-3 w-3" />
                                                ) : (
                                                    <ArrowDown className="h-3 w-3" />
                                                )}
                                                {movement.movement_type === 'in' ? 'In' : 'Out'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                            {movement.quantity} {movement.goods_service?.unit || ''}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {movement.notes || '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                            <Link
                                                href={`/stock/movements/${movement.id}`}
                                                className="text-teal-500 hover:text-teal-600"
                                            >
                                                <Eye className="h-4 w-4" />
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        
                        {/* Pagination */}
                        {movements.links && movements.links.length > 3 && (
                            <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                                <div className="text-sm text-gray-500">
                                    Showing {movements.from} to {movements.to} of {movements.total} results
                                </div>
                                <div className="flex gap-2">
                                    {movements.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-1 rounded-lg text-sm ${
                                                link.active
                                                    ? 'bg-teal-500 text-white'
                                                    : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
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

