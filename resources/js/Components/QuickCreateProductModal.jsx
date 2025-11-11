import { useState } from 'react';
import { X } from 'lucide-react';
import axios from 'axios';
import { Button } from '@/Components/ui/Button';

export default function QuickCreateProductModal({ isOpen, onClose, onSuccess }) {
    const [formData, setFormData] = useState({
        name: '',
        type: 'product',
        description: '',
        sku: '',
        barcode: '',
        cost_price: '',
        selling_price: '',
        current_stock: '',
        minimum_stock: '',
        unit: '',
        category: '',
        is_active: true,
        track_stock: false,
    });
    const [errors, setErrors] = useState({});
    const [saving, setSaving] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setErrors({});
        setSaving(true);

        try {
            const payload = {
                ...formData,
                cost_price: formData.cost_price ? parseFloat(formData.cost_price) : null,
                selling_price: formData.selling_price ? parseFloat(formData.selling_price) : null,
                current_stock: formData.current_stock ? parseFloat(formData.current_stock) : null,
                minimum_stock: formData.minimum_stock ? parseFloat(formData.minimum_stock) : null,
            };
            const response = await axios.post('/api/products/quick-create', payload);
            if (response.data.success) {
                onSuccess(response.data.product);
                handleClose();
            }
        } catch (error) {
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            } else {
                setErrors({ general: error.response?.data?.message || 'Failed to create product' });
            }
        } finally {
            setSaving(false);
        }
    };

    const handleClose = () => {
        setFormData({
            name: '',
            type: 'product',
            description: '',
            sku: '',
            barcode: '',
            cost_price: '',
            selling_price: '',
            current_stock: '',
            minimum_stock: '',
            unit: '',
            category: '',
            is_active: true,
            track_stock: false,
        });
        setErrors({});
        onClose();
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div className="flex items-center justify-between p-4 border-b border-gray-200 sticky top-0 bg-white">
                    <h2 className="text-lg font-semibold text-gray-900">Add New Product</h2>
                    <button
                        onClick={handleClose}
                        className="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-4 space-y-4">
                    {errors.general && (
                        <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                            {errors.general}
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
                                Product Name *
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            />
                            {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name[0]}</p>}
                        </div>

                        <div>
                            <label htmlFor="type" className="block text-sm font-medium text-gray-700 mb-1">
                                Type *
                            </label>
                            <select
                                id="type"
                                value={formData.type}
                                onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            >
                                <option value="product">Product</option>
                                <option value="service">Service</option>
                            </select>
                            {errors.type && <p className="mt-1 text-xs text-red-600">{errors.type[0]}</p>}
                        </div>
                    </div>

                    <div>
                        <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea
                            id="description"
                            value={formData.description}
                            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                            rows={2}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        />
                    </div>

                    <div className="grid grid-cols-3 gap-4">
                        <div>
                            <label htmlFor="sku" className="block text-sm font-medium text-gray-700 mb-1">
                                SKU
                            </label>
                            <input
                                id="sku"
                                type="text"
                                value={formData.sku}
                                onChange={(e) => setFormData({ ...formData, sku: e.target.value })}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>

                        <div>
                            <label htmlFor="barcode" className="block text-sm font-medium text-gray-700 mb-1">
                                Barcode
                            </label>
                            <input
                                id="barcode"
                                type="text"
                                value={formData.barcode}
                                onChange={(e) => setFormData({ ...formData, barcode: e.target.value })}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>

                        <div>
                            <label htmlFor="category" className="block text-sm font-medium text-gray-700 mb-1">
                                Category
                            </label>
                            <input
                                id="category"
                                type="text"
                                value={formData.category}
                                onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label htmlFor="cost_price" className="block text-sm font-medium text-gray-700 mb-1">
                                Cost Price
                            </label>
                            <input
                                id="cost_price"
                                type="number"
                                step="0.01"
                                min="0"
                                value={formData.cost_price}
                                onChange={(e) => setFormData({ ...formData, cost_price: e.target.value })}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>

                        <div>
                            <label htmlFor="selling_price" className="block text-sm font-medium text-gray-700 mb-1">
                                Selling Price *
                            </label>
                            <input
                                id="selling_price"
                                type="number"
                                step="0.01"
                                min="0"
                                value={formData.selling_price}
                                onChange={(e) => setFormData({ ...formData, selling_price: e.target.value })}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            />
                            {errors.selling_price && <p className="mt-1 text-xs text-red-600">{errors.selling_price[0]}</p>}
                        </div>
                    </div>

                    {formData.type === 'product' && (
                        <>
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="track_stock"
                                    checked={formData.track_stock}
                                    onChange={(e) => setFormData({ ...formData, track_stock: e.target.checked })}
                                    className="rounded border-gray-300 text-teal-500 focus:ring-teal-500"
                                />
                                <label htmlFor="track_stock" className="text-sm font-medium text-gray-700">
                                    Track Stock
                                </label>
                            </div>

                            {formData.track_stock && (
                                <div className="grid grid-cols-3 gap-4">
                                    <div>
                                        <label htmlFor="current_stock" className="block text-sm font-medium text-gray-700 mb-1">
                                            Current Stock
                                        </label>
                                        <input
                                            id="current_stock"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={formData.current_stock}
                                            onChange={(e) => setFormData({ ...formData, current_stock: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        />
                                    </div>

                                    <div>
                                        <label htmlFor="minimum_stock" className="block text-sm font-medium text-gray-700 mb-1">
                                            Minimum Stock
                                        </label>
                                        <input
                                            id="minimum_stock"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={formData.minimum_stock}
                                            onChange={(e) => setFormData({ ...formData, minimum_stock: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        />
                                    </div>

                                    <div>
                                        <label htmlFor="unit" className="block text-sm font-medium text-gray-700 mb-1">
                                            Unit
                                        </label>
                                        <input
                                            id="unit"
                                            type="text"
                                            value={formData.unit}
                                            onChange={(e) => setFormData({ ...formData, unit: e.target.value })}
                                            placeholder="e.g., pcs, kg"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        />
                                    </div>
                                </div>
                            )}
                        </>
                    )}

                    <div className="flex gap-3 pt-2">
                        <Button type="submit" disabled={saving} className="flex-1">
                            {saving ? 'Creating...' : 'Create Product'}
                        </Button>
                        <Button type="button" variant="secondary" onClick={handleClose} disabled={saving}>
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}

