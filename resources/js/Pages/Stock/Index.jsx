import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Package, AlertTriangle, Plus, ArrowRight } from 'lucide-react';

export default function StockIndex({ products, filters, categories, stats }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    return (
        <SectionLayout sectionName="Inventory">
            <Head title="Stock Management" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Stock Management</h1>
                        <p className="text-gray-500 mt-1">Track and manage your inventory</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/stock/adjustments/create">
                            <Button variant="secondary">
                                <Plus className="h-4 w-4 mr-2" />
                                Stock Adjustment
                            </Button>
                        </Link>
                        <Link href="/stock/movements">
                            <Button variant="secondary">
                                View Movements
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-3 gap-6 mb-6">
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="flex items-center gap-3">
                            <div className="p-3 bg-teal-100 rounded-lg">
                                <Package className="h-6 w-6 text-teal-600" />
                            </div>
                            <div>
                                <div className="text-sm text-gray-500">Total Products</div>
                                <div className="text-2xl font-bold text-gray-900">{stats.total_products}</div>
                            </div>
                        </div>
                    </div>
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="flex items-center gap-3">
                            <div className="p-3 bg-yellow-100 rounded-lg">
                                <AlertTriangle className="h-6 w-6 text-yellow-600" />
                            </div>
                            <div>
                                <div className="text-sm text-gray-500">Low Stock Items</div>
                                <div className="text-2xl font-bold text-yellow-600">{stats.low_stock_count}</div>
                            </div>
                        </div>
                    </div>
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="flex items-center gap-3">
                            <div className="p-3 bg-green-100 rounded-lg">
                                <Package className="h-6 w-6 text-green-600" />
                            </div>
                            <div>
                                <div className="text-sm text-gray-500">Total Stock Value</div>
                                <div className="text-2xl font-bold text-gray-900">{formatCurrency(stats.total_stock_value)}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input
                                type="text"
                                value={filters?.search || ''}
                                onChange={(e) => router.visit(`/stock?search=${e.target.value}`)}
                                placeholder="Product name or SKU..."
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select
                                value={filters?.category || ''}
                                onChange={(e) => router.visit(`/stock?category=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Categories</option>
                                {categories.map((cat) => (
                                    <option key={cat} value={cat}>{cat}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Filter</label>
                            <button
                                onClick={() => router.visit(`/stock?low_stock=${filters?.low_stock === 'true' ? '' : 'true'}`)}
                                className={`w-full px-4 py-2 border rounded-lg ${
                                    filters?.low_stock === 'true'
                                        ? 'bg-yellow-50 border-yellow-300 text-yellow-700'
                                        : 'bg-white border-gray-300 text-gray-700'
                                }`}
                            >
                                Low Stock Only
                            </button>
                        </div>
                    </div>
                </div>

                {products.data.length === 0 ? (
                    <div className="bg-white border border-gray-200 rounded-lg p-12 text-center">
                        <Package className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No products with stock tracking</h3>
                        <p className="text-gray-500 mb-4">Products with stock tracking enabled will appear here</p>
                        <Link href="/products/create">
                            <Button>
                                <Plus className="h-4 w-4 mr-2" />
                                Create Product
                            </Button>
                        </Link>
                    </div>
                ) : (
                    <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Product
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        SKU
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Category
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Current Stock
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Minimum Stock
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Stock Value
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {products.data.map((product) => (
                                    <tr key={product.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-2">
                                                <div className="font-medium text-gray-900">{product.name}</div>
                                                {product.is_low_stock && (
                                                    <AlertTriangle className="h-4 w-4 text-yellow-500" />
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {product.sku || '-'}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {product.category || '-'}
                                        </td>
                                        <td className={`px-6 py-4 whitespace-nowrap text-sm text-right font-medium ${
                                            product.is_low_stock ? 'text-red-600' : 'text-gray-900'
                                        }`}>
                                            {product.current_stock} {product.unit || ''}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600">
                                            {product.minimum_stock || '-'} {product.unit || ''}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                            {product.cost_price ? formatCurrency(product.current_stock * product.cost_price) : '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                            <Link
                                                href={`/products/${product.id}`}
                                                className="text-teal-500 hover:text-teal-600 inline-flex items-center gap-1"
                                            >
                                                View
                                                <ArrowRight className="h-4 w-4" />
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        
                        {/* Pagination */}
                        {products.links && products.links.length > 3 && (
                            <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                                <div className="text-sm text-gray-500">
                                    Showing {products.from} to {products.to} of {products.total} results
                                </div>
                                <div className="flex gap-2">
                                    {products.links.map((link, index) => (
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

