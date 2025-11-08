import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Edit, Package, AlertTriangle } from 'lucide-react';

export default function ProductsShow({ product }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    return (
        <SectionLayout sectionName="Inventory">
            <Head title={product.name} />
            <div className="max-w-4xl mx-auto">
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
                            <h1 className="text-3xl font-bold text-gray-900">{product.name}</h1>
                            <p className="text-gray-500 mt-1 capitalize">{product.type}</p>
                        </div>
                        <Link href={`/products/${product.id}/edit`}>
                            <Button>
                                <Edit className="h-4 w-4 mr-2" />
                                Edit
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Basic Information</h3>
                            <div className="space-y-2">
                                {product.sku && (
                                    <p className="text-gray-900">
                                        <span className="font-medium">SKU:</span> {product.sku}
                                    </p>
                                )}
                                {product.barcode && (
                                    <p className="text-gray-900">
                                        <span className="font-medium">Barcode:</span> {product.barcode}
                                    </p>
                                )}
                                {product.category && (
                                    <p className="text-gray-900">
                                        <span className="font-medium">Category:</span> {product.category}
                                    </p>
                                )}
                                <p className="text-gray-900">
                                    <span className="font-medium">Status:</span>{' '}
                                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                        product.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'
                                    }`}>
                                        {product.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Pricing</h3>
                            <div className="space-y-2">
                                {product.cost_price && (
                                    <p className="text-gray-900">
                                        <span className="font-medium">Cost Price:</span> {formatCurrency(product.cost_price)}
                                    </p>
                                )}
                                {product.selling_price && (
                                    <p className="text-gray-900">
                                        <span className="font-medium">Selling Price:</span> {formatCurrency(product.selling_price)}
                                    </p>
                                )}
                                {product.cost_price && product.selling_price && (
                                    <p className="text-gray-900">
                                        <span className="font-medium">Profit Margin:</span>{' '}
                                        {formatCurrency(product.selling_price - product.cost_price)} (
                                        {((product.selling_price - product.cost_price) / product.cost_price * 100).toFixed(1)}%)
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    {product.description && (
                        <div className="mb-6 pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Description</h3>
                            <p className="text-gray-900">{product.description}</p>
                        </div>
                    )}

                    {product.type === 'product' && product.track_stock && (
                        <div className="pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-4">Stock Information</h3>
                            <div className="grid grid-cols-3 gap-4">
                                <div className="p-4 bg-gray-50 rounded-lg">
                                    <div className="text-sm text-gray-500 mb-1">Current Stock</div>
                                    <div className={`text-2xl font-bold ${
                                        product.is_low_stock ? 'text-red-600' : 'text-gray-900'
                                    }`}>
                                        {product.current_stock || 0} {product.unit || ''}
                                    </div>
                                    {product.is_low_stock && (
                                        <div className="flex items-center gap-1 mt-2 text-sm text-red-600">
                                            <AlertTriangle className="h-4 w-4" />
                                            Low Stock
                                        </div>
                                    )}
                                </div>
                                <div className="p-4 bg-gray-50 rounded-lg">
                                    <div className="text-sm text-gray-500 mb-1">Minimum Stock</div>
                                    <div className="text-2xl font-bold text-gray-900">
                                        {product.minimum_stock || 0} {product.unit || ''}
                                    </div>
                                </div>
                                <div className="p-4 bg-gray-50 rounded-lg">
                                    <div className="text-sm text-gray-500 mb-1">Stock Value</div>
                                    <div className="text-2xl font-bold text-gray-900">
                                        {product.cost_price ? formatCurrency((product.current_stock || 0) * product.cost_price) : '-'}
                                    </div>
                                </div>
                            </div>
                            <div className="mt-4">
                                <Link href={`/stock/movements?product_id=${product.id}`}>
                                    <Button variant="secondary">
                                        View Stock Movements
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    )}
                </div>

                {/* Recent Stock Movements */}
                {product.stock_movements && product.stock_movements.length > 0 && (
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <h2 className="text-xl font-semibold text-gray-900 mb-4">Recent Stock Movements</h2>
                        <div className="space-y-2">
                            {product.stock_movements.map((movement) => (
                                <div key={movement.id} className="flex items-center justify-between p-3 bg-gray-50 rounded">
                                    <div>
                                        <span className={`px-2 py-1 rounded text-xs font-medium ${
                                            movement.movement_type === 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                        }`}>
                                            {movement.movement_type === 'in' ? '+' : '-'}
                                        </span>
                                        <span className="ml-2 text-sm text-gray-900">{movement.quantity} {product.unit || ''}</span>
                                        {movement.reference_number && (
                                            <span className="ml-2 text-xs text-gray-500">{movement.reference_number}</span>
                                        )}
                                    </div>
                                    <div className="text-xs text-gray-500">
                                        {new Date(movement.created_at).toLocaleDateString()}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}

