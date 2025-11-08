import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Eye, Edit, Trash2, Package, AlertTriangle } from 'lucide-react';

export default function ProductsIndex({ products, filters, categories }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const handleDelete = (productId) => {
        if (confirm('Are you sure you want to delete this product?')) {
            router.delete(`/products/${productId}`);
        }
    };

    return (
        <SectionLayout sectionName="Inventory">
            <Head title="Products" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Products</h1>
                        <p className="text-gray-500 mt-1">Manage your products and services</p>
                    </div>
                    <Button onClick={() => router.visit('/products/create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        New Product
                    </Button>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input
                                type="text"
                                value={filters?.search || ''}
                                onChange={(e) => router.visit(`/products?search=${e.target.value}`)}
                                placeholder="Name, SKU, or barcode..."
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Type</label>
                            <select
                                value={filters?.type || ''}
                                onChange={(e) => router.visit(`/products?type=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Types</option>
                                <option value="product">Product</option>
                                <option value="service">Service</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select
                                value={filters?.category || ''}
                                onChange={(e) => router.visit(`/products?category=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Categories</option>
                                {categories.map((cat) => (
                                    <option key={cat} value={cat}>{cat}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                value={filters?.is_active || ''}
                                onChange={(e) => router.visit(`/products?is_active=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Status</option>
                                <option value="true">Active</option>
                                <option value="false">Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Stock</label>
                            <button
                                onClick={() => router.visit(`/products?low_stock=${filters?.low_stock === 'true' ? '' : 'true'}`)}
                                className={`w-full px-4 py-2 border rounded-lg ${
                                    filters?.low_stock === 'true'
                                        ? 'bg-yellow-50 border-yellow-300 text-yellow-700'
                                        : 'bg-white border-gray-300 text-gray-700'
                                }`}
                            >
                                Low Stock
                            </button>
                        </div>
                    </div>
                </div>

                {products.data.length === 0 ? (
                    <div className="bg-white border border-gray-200 rounded-lg p-12 text-center">
                        <Package className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No products yet</h3>
                        <p className="text-gray-500 mb-4">Create your first product to start managing inventory</p>
                        <Button onClick={() => router.visit('/products/create')}>
                            <Plus className="h-4 w-4 mr-2" />
                            Create Product
                        </Button>
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
                                        SKU/Barcode
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Category
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Cost Price
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Selling Price
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Stock
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
                                            <div className="text-sm text-gray-500 capitalize">{product.type}</div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-sm text-gray-900">{product.sku || '-'}</div>
                                            {product.barcode && (
                                                <div className="text-xs text-gray-500">{product.barcode}</div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {product.category || '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600">
                                            {product.cost_price ? formatCurrency(product.cost_price) : '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                            {product.selling_price ? formatCurrency(product.selling_price) : '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right">
                                            {product.track_stock ? (
                                                <div>
                                                    <span className={`font-medium ${
                                                        product.is_low_stock ? 'text-red-600' : 'text-gray-900'
                                                    }`}>
                                                        {product.current_stock} {product.unit || ''}
                                                    </span>
                                                    {product.minimum_stock && (
                                                        <div className="text-xs text-gray-500">
                                                            Min: {product.minimum_stock}
                                                        </div>
                                                    )}
                                                </div>
                                            ) : (
                                                <span className="text-gray-400">-</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <Link
                                                    href={`/products/${product.id}`}
                                                    className="text-teal-500 hover:text-teal-600"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                                <Link
                                                    href={`/products/${product.id}/edit`}
                                                    className="text-blue-500 hover:text-blue-600"
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(product.id)}
                                                    className="text-red-500 hover:text-red-600"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
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

