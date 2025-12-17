import { Head, useForm, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function StockAdjustmentCreate({ products, selectedProductId }) {
    const { data, setData, post, processing, errors } = useForm({
        goods_service_id: selectedProductId || '',
        adjustment_type: 'increase',
        quantity: '',
        reason: '',
        notes: '',
    });

    const selectedProduct = products.find(p => p.id === data.goods_service_id);

    const submit = (e) => {
        e.preventDefault();
        post('/stock/adjustments');
    };

    return (
        <SectionLayout sectionName="Inventory">
            <Head title="Stock Adjustment" />
            <div className="max-w-2xl mx-auto">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => window.history.back()}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <h1 className="text-3xl font-bold text-gray-900">Stock Adjustment</h1>
                    <p className="text-gray-500 mt-1">Manually adjust stock levels</p>
                </div>

                <form onSubmit={submit} className="bg-white border border-gray-200 rounded-lg p-6">
                    <div className="space-y-6">
                        <div>
                            <label htmlFor="goods_service_id" className="block text-sm font-medium text-gray-700 mb-2">
                                Product *
                            </label>
                            <select
                                id="goods_service_id"
                                value={data.goods_service_id}
                                onChange={(e) => setData('goods_service_id', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            >
                                <option value="">Select product</option>
                                {products.map((product) => (
                                    <option key={product.id} value={product.id}>
                                        {product.name} ({product.current_stock} {product.unit || ''} in stock)
                                    </option>
                                ))}
                            </select>
                            {errors.goods_service_id && <p className="mt-1 text-sm text-red-600">{errors.goods_service_id}</p>}
                        </div>

                        {selectedProduct && (
                            <div className="p-4 bg-gray-50 rounded-lg">
                                <div className="text-sm text-gray-500 mb-1">Current Stock</div>
                                <div className="text-2xl font-bold text-gray-900">
                                    {selectedProduct.current_stock} {selectedProduct.unit || ''}
                                </div>
                            </div>
                        )}

                        <div>
                            <label htmlFor="adjustment_type" className="block text-sm font-medium text-gray-700 mb-2">
                                Adjustment Type *
                            </label>
                            <select
                                id="adjustment_type"
                                value={data.adjustment_type}
                                onChange={(e) => setData('adjustment_type', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            >
                                <option value="increase">Increase Stock</option>
                                <option value="decrease">Decrease Stock</option>
                            </select>
                        </div>

                        <div>
                            <label htmlFor="quantity" className="block text-sm font-medium text-gray-700 mb-2">
                                Quantity *
                            </label>
                            <input
                                id="quantity"
                                type="number"
                                step="0.01"
                                min="0.01"
                                value={data.quantity}
                                onChange={(e) => setData('quantity', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="0.00"
                                required
                            />
                            {selectedProduct && (
                                <p className="mt-1 text-sm text-gray-500">
                                    Unit: {selectedProduct.unit || 'pieces'}
                                </p>
                            )}
                            {errors.quantity && <p className="mt-1 text-sm text-red-600">{errors.quantity}</p>}
                        </div>

                        <div>
                            <label htmlFor="reason" className="block text-sm font-medium text-gray-700 mb-2">
                                Reason *
                            </label>
                            <select
                                id="reason"
                                value={data.reason}
                                onChange={(e) => setData('reason', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            >
                                <option value="">Select reason</option>
                                <option value="Damage">Damage</option>
                                <option value="Loss">Loss</option>
                                <option value="Found">Found</option>
                                <option value="Theft">Theft</option>
                                <option value="Return">Return</option>
                                <option value="Count Error">Count Error</option>
                                <option value="Other">Other</option>
                            </select>
                            {errors.reason && <p className="mt-1 text-sm text-red-600">{errors.reason}</p>}
                        </div>

                        <div>
                            <label htmlFor="notes" className="block text-sm font-medium text-gray-700 mb-2">
                                Additional Notes
                            </label>
                            <textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="Additional details about this adjustment..."
                            />
                        </div>

                        {selectedProduct && data.quantity && (
                            <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <div className="text-sm font-medium text-blue-900 mb-2">Adjustment Preview</div>
                                <div className="text-lg">
                                    Current: <span className="font-semibold">{selectedProduct.current_stock} {selectedProduct.unit || ''}</span>
                                    {' → '}
                                    {data.adjustment_type === 'increase' ? (
                                        <span className="text-green-600 font-semibold">
                                            {(parseFloat(selectedProduct.current_stock) + parseFloat(data.quantity)).toFixed(2)} {selectedProduct.unit || ''}
                                        </span>
                                    ) : (
                                        <span className="text-red-600 font-semibold">
                                            {(parseFloat(selectedProduct.current_stock) - parseFloat(data.quantity)).toFixed(2)} {selectedProduct.unit || ''}
                                        </span>
                                    )}
                                </div>
                            </div>
                        )}

                        <div className="flex gap-4 pt-4 border-t border-gray-200">
                            <Button type="submit" disabled={processing} className="flex-1">
                                Record Adjustment
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

