import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Eye, Edit, Trash2, Package, AlertTriangle, TrendingUp, Search, Filter } from 'lucide-react';

export default function ProductsIndex({ products, filters, categories }) {
    const formatCurrency = (amount) => {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num);
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
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-black text-gray-900 tracking-tight">Products</h1>
                        <p className="text-gray-500 mt-1">Manage your products and services</p>
                    </div>
                    <div className="flex gap-3">
                        <Button 
                            variant="secondary" 
                            onClick={() => router.visit('/products/stock-adjustment')}
                            className="gap-2"
                        >
                            <TrendingUp className="h-4 w-4" />
                            Stock Adjustment
                        </Button>
                        <Button onClick={() => router.visit('/products/create')} className="gap-2">
                            <Plus className="h-4 w-4" />
                            New Product
                        </Button>
                    </div>
                </div>

                {/* Filters Card */}
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 mb-6">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
                            <Filter className="w-5 h-5 text-teal-600" />
                        </div>
                        <h3 className="text-sm font-bold text-gray-900">Filters</h3>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Search</label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <input
                                    type="text"
                                    value={filters?.search || ''}
                                    onChange={(e) => router.visit(`/products?search=${e.target.value}`)}
                                    placeholder="Name, SKU, barcode..."
                                    className="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Type</label>
                            <select
                                value={filters?.type || ''}
                                onChange={(e) => router.visit(`/products?type=${e.target.value}`)}
                                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            >
                                <option value="">All Types</option>
                                <option value="product">Product</option>
                                <option value="service">Service</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Category</label>
                            <select
                                value={filters?.category || ''}
                                onChange={(e) => router.visit(`/products?category=${e.target.value}`)}
                                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            >
                                <option value="">All Categories</option>
                                {categories.map((cat) => (
                                    <option key={cat} value={cat}>{cat}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Status</label>
                            <select
                                value={filters?.is_active || ''}
                                onChange={(e) => router.visit(`/products?is_active=${e.target.value}`)}
                                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            >
                                <option value="">All Status</option>
                                <option value="true">Active</option>
                                <option value="false">Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Stock Alert</label>
                            <button
                                onClick={() => router.visit(`/products?low_stock=${filters?.low_stock === 'true' ? '' : 'true'}`)}
                                className={`w-full px-4 py-2.5 border rounded-xl text-sm font-semibold transition-all ${
                                    filters?.low_stock === 'true'
                                        ? 'bg-amber-50 border-amber-300 text-amber-700'
                                        : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-teal-50 hover:border-teal-200'
                                }`}
                            >
                                <span className="flex items-center justify-center gap-2">
                                    <AlertTriangle className="h-4 w-4" />
                                    Low Stock
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                {products.data.length === 0 ? (
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-12 border border-gray-200/50 text-center">
                        <div className="w-16 h-16 rounded-2xl bg-teal-100 flex items-center justify-center mx-auto mb-4">
                            <Package className="h-8 w-8 text-teal-500" />
                        </div>
                        <h3 className="text-lg font-bold text-gray-900 mb-2">No products yet</h3>
                        <p className="text-gray-500 mb-6">Create your first product to start managing inventory</p>
                        <Button onClick={() => router.visit('/products/create')} className="gap-2">
                            <Plus className="h-4 w-4" />
                            Create Product
                        </Button>
                    </div>
                ) : (
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 overflow-hidden">
                        <table className="w-full">
                            <thead className="bg-gray-50/80 border-b border-gray-200/50">
                                <tr>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Product
                                    </th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        SKU/Barcode
                                    </th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Category
                                    </th>
                                    <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Cost
                                    </th>
                                    <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Price
                                    </th>
                                    <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Stock
                                    </th>
                                    <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {products.data.map((product) => (
                                    <tr key={product.id} className="hover:bg-teal-50/30 transition-colors">
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
                                                    <Package className="h-5 w-5 text-teal-600" />
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <p className="font-bold text-gray-900">{product.name}</p>
                                                        {product.is_low_stock && (
                                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                                                <AlertTriangle className="h-3 w-3 mr-1" />
                                                                Low
                                                            </span>
                                                        )}
                                                    </div>
                                                    <p className="text-xs text-gray-500 capitalize">{product.type}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <p className="text-sm font-medium text-gray-900">{product.sku || '-'}</p>
                                            {product.barcode && (
                                                <p className="text-xs text-gray-500">{product.barcode}</p>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            {product.category ? (
                                                <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                                    {product.category}
                                                </span>
                                            ) : (
                                                <span className="text-gray-400">-</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600">
                                            {product.cost_price ? formatCurrency(product.cost_price) : '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                                            {product.selling_price ? formatCurrency(product.selling_price) : '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right">
                                            {product.type === 'product' ? (
                                                <div>
                                                    <span className={`font-bold ${
                                                        product.is_low_stock ? 'text-red-500' : 'text-gray-900'
                                                    }`}>
                                                        {product.current_stock ?? 0}
                                                    </span>
                                                    <span className="text-gray-500 ml-1">{product.unit || ''}</span>
                                                    {product.minimum_stock && (
                                                        <p className="text-xs text-gray-400">
                                                            Min: {product.minimum_stock}
                                                        </p>
                                                    )}
                                                </div>
                                            ) : (
                                                <span className="text-gray-400">N/A</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                            <div className="flex items-center justify-center gap-1">
                                                <Link
                                                    href={`/products/${product.id}`}
                                                    className="p-2 rounded-lg text-teal-600 hover:bg-teal-100 transition-colors"
                                                    title="View"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                                <Link
                                                    href={`/products/${product.id}/edit`}
                                                    className="p-2 rounded-lg text-blue-600 hover:bg-blue-100 transition-colors"
                                                    title="Edit"
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Link>
                                                {product.type === 'product' && product.track_stock && (
                                                    <button
                                                        onClick={() => router.visit(`/products/stock-adjustment?product_id=${product.id}`)}
                                                        className="p-2 rounded-lg text-green-600 hover:bg-green-100 transition-colors"
                                                        title="Adjust Stock"
                                                    >
                                                        <TrendingUp className="h-4 w-4" />
                                                    </button>
                                                )}
                                                <button
                                                    onClick={() => handleDelete(product.id)}
                                                    className="p-2 rounded-lg text-red-500 hover:bg-red-100 transition-colors"
                                                    title="Delete"
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
                            <div className="px-6 py-4 border-t border-gray-200/50 flex items-center justify-between bg-gray-50/50">
                                <p className="text-sm text-gray-600">
                                    Showing <span className="font-semibold">{products.from}</span> to <span className="font-semibold">{products.to}</span> of <span className="font-semibold">{products.total}</span>
                                </p>
                                <div className="flex gap-1">
                                    {products.links.map((link, index) => (
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
