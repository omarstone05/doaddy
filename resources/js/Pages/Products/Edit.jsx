import { Head, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function ProductsEdit({ product }) {
    const { data, setData, put, processing, errors } = useForm({
        name: product.name,
        type: product.type,
        description: product.description || '',
        sku: product.sku || '',
        barcode: product.barcode || '',
        cost_price: product.cost_price || '',
        selling_price: product.selling_price || '',
        current_stock: product.current_stock || '',
        minimum_stock: product.minimum_stock || '',
        unit: product.unit || '',
        category: product.category || '',
        is_active: product.is_active,
        track_stock: product.track_stock,
    });

    const submit = (e) => {
        e.preventDefault();
        put(`/products/${product.id}`);
    };

    return (
        <SectionLayout sectionName="Inventory">
            <Head title={`Edit ${product.name}`} />
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
                    <h1 className="text-3xl font-bold text-gray-900">Edit Product</h1>
                    <p className="text-gray-500 mt-1">{product.name}</p>
                </div>

                <form onSubmit={submit} className="bg-white border border-gray-200 rounded-lg p-6">
                    <div className="space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
                                    Name *
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                                {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                            </div>

                            <div>
                                <label htmlFor="type" className="block text-sm font-medium text-gray-700 mb-2">
                                    Type *
                                </label>
                                <select
                                    id="type"
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                >
                                    <option value="product">Product</option>
                                    <option value="service">Service</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
                                Description
                            </label>
                            <textarea
                                id="description"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="sku" className="block text-sm font-medium text-gray-700 mb-2">
                                    SKU
                                </label>
                                <input
                                    id="sku"
                                    type="text"
                                    value={data.sku}
                                    onChange={(e) => setData('sku', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>

                            <div>
                                <label htmlFor="barcode" className="block text-sm font-medium text-gray-700 mb-2">
                                    Barcode
                                </label>
                                <input
                                    id="barcode"
                                    type="text"
                                    value={data.barcode}
                                    onChange={(e) => setData('barcode', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="cost_price" className="block text-sm font-medium text-gray-700 mb-2">
                                    Cost Price
                                </label>
                                <input
                                    id="cost_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.cost_price}
                                    onChange={(e) => setData('cost_price', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="0.00"
                                />
                            </div>

                            <div>
                                <label htmlFor="selling_price" className="block text-sm font-medium text-gray-700 mb-2">
                                    Selling Price
                                </label>
                                <input
                                    id="selling_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.selling_price}
                                    onChange={(e) => setData('selling_price', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="0.00"
                                />
                            </div>
                        </div>

                        {data.type === 'product' && (
                            <>
                                <div className="border-t border-gray-200 pt-4">
                                    <div className="flex items-center gap-2 mb-4">
                                        <input
                                            type="checkbox"
                                            id="track_stock"
                                            checked={data.track_stock}
                                            onChange={(e) => setData('track_stock', e.target.checked)}
                                            className="rounded border-gray-300 text-teal-500 focus:ring-teal-500"
                                        />
                                        <label htmlFor="track_stock" className="text-sm font-medium text-gray-700">
                                            Track Stock
                                        </label>
                                    </div>

                                    {data.track_stock && (
                                        <div className="grid grid-cols-3 gap-4 ml-6">
                                            <div>
                                                <label htmlFor="current_stock" className="block text-sm font-medium text-gray-700 mb-2">
                                                    Current Stock
                                                </label>
                                                <input
                                                    id="current_stock"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={data.current_stock}
                                                    onChange={(e) => setData('current_stock', e.target.value)}
                                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                                    placeholder="0.00"
                                                />
                                            </div>
                                            <div>
                                                <label htmlFor="minimum_stock" className="block text-sm font-medium text-gray-700 mb-2">
                                                    Minimum Stock
                                                </label>
                                                <input
                                                    id="minimum_stock"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={data.minimum_stock}
                                                    onChange={(e) => setData('minimum_stock', e.target.value)}
                                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                                    placeholder="0.00"
                                                />
                                            </div>
                                            <div>
                                                <label htmlFor="unit" className="block text-sm font-medium text-gray-700 mb-2">
                                                    Unit
                                                </label>
                                                <input
                                                    id="unit"
                                                    type="text"
                                                    value={data.unit}
                                                    onChange={(e) => setData('unit', e.target.value)}
                                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                                    placeholder="e.g., pcs, kg, L"
                                                />
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </>
                        )}

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="category" className="block text-sm font-medium text-gray-700 mb-2">
                                    Category
                                </label>
                                <input
                                    id="category"
                                    type="text"
                                    value={data.category}
                                    onChange={(e) => setData('category', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="e.g., Electronics, Food"
                                />
                            </div>

                            <div className="flex items-center pt-8">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="rounded border-gray-300 text-teal-500 focus:ring-teal-500"
                                />
                                <label htmlFor="is_active" className="ml-2 text-sm font-medium text-gray-700">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div className="flex gap-4 pt-4 border-t border-gray-200">
                            <Button type="submit" disabled={processing} className="flex-1">
                                Update Product
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => window.history.back()}
                            >
                                Cancel
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </SectionLayout>
    );
}

