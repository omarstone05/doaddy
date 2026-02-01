import React, { useState, useEffect } from 'react';
import { X } from 'lucide-react';
import { Button } from '@/Components/ui/Button';

const TaxRateModal = ({ taxRate, onClose, onSave }) => {
    const [formData, setFormData] = useState({
        name: '',
        code: '',
        rate: '',
        description: '',
        is_default: false,
        is_active: true,
        tax_type: 'vat',
    });

    const [errors, setErrors] = useState({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (taxRate) {
            setFormData({
                name: taxRate.name || '',
                code: taxRate.code || '',
                rate: taxRate.rate || '',
                description: taxRate.description || '',
                is_default: taxRate.is_default || false,
                is_active: taxRate.is_active !== undefined ? taxRate.is_active : true,
                tax_type: taxRate.tax_type || 'vat',
            });
        }
    }, [taxRate]);

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value
        }));
        // Clear error for this field
        if (errors[name]) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[name];
                return newErrors;
            });
        }
    };

    const validate = () => {
        const newErrors = {};

        if (!formData.name.trim()) {
            newErrors.name = 'Name is required';
        }

        if (!formData.rate || isNaN(formData.rate) || parseFloat(formData.rate) < 0 || parseFloat(formData.rate) > 100) {
            newErrors.rate = 'Rate must be a number between 0 and 100';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!validate()) {
            return;
        }

        setSaving(true);
        try {
            await onSave({
                ...formData,
                rate: parseFloat(formData.rate),
            });
        } catch (error) {
            console.error('Error saving tax rate:', error);
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                {/* Background overlay */}
                <div 
                    className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                    onClick={onClose}
                    aria-hidden="true"
                />

                {/* Centering trick for sm screens */}
                <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {/* Modal panel */}
                <div className="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div className="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-medium text-gray-900">
                                {taxRate ? 'Edit Tax Rate' : 'Create Tax Rate'}
                            </h3>
                            <button
                                onClick={onClose}
                                className="text-gray-400 hover:text-gray-500"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4">
                            {/* Name */}
                            <div>
                                <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
                                    Name <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value={formData.name}
                                    onChange={handleChange}
                                    className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 ${
                                        errors.name ? 'border-red-300' : 'border-gray-300'
                                    }`}
                                    placeholder="e.g., VAT, Sales Tax, GST"
                                />
                                {errors.name && (
                                    <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                )}
                            </div>

                            {/* Code */}
                            <div>
                                <label htmlFor="code" className="block text-sm font-medium text-gray-700 mb-1">
                                    Code
                                </label>
                                <input
                                    type="text"
                                    id="code"
                                    name="code"
                                    value={formData.code}
                                    onChange={handleChange}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    placeholder="e.g., VAT, ST, GST"
                                />
                                <p className="mt-1 text-xs text-gray-500">Optional short code for this tax rate</p>
                            </div>

                            {/* Rate */}
                            <div>
                                <label htmlFor="rate" className="block text-sm font-medium text-gray-700 mb-1">
                                    Rate (%) <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="rate"
                                    name="rate"
                                    value={formData.rate}
                                    onChange={handleChange}
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 ${
                                        errors.rate ? 'border-red-300' : 'border-gray-300'
                                    }`}
                                    placeholder="16.00"
                                />
                                {errors.rate && (
                                    <p className="mt-1 text-sm text-red-600">{errors.rate}</p>
                                )}
                            </div>

                            {/* Tax Type */}
                            <div>
                                <label htmlFor="tax_type" className="block text-sm font-medium text-gray-700 mb-1">
                                    Tax Type
                                </label>
                                <select
                                    id="tax_type"
                                    name="tax_type"
                                    value={formData.tax_type}
                                    onChange={handleChange}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                >
                                    <option value="vat">VAT (Value Added Tax)</option>
                                    <option value="sales_tax">Sales Tax</option>
                                    <option value="gst">GST (Goods & Services Tax)</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>

                            {/* Description */}
                            <div>
                                <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    name="description"
                                    value={formData.description}
                                    onChange={handleChange}
                                    rows={3}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    placeholder="Optional description for this tax rate"
                                />
                            </div>

                            {/* Options */}
                            <div className="space-y-3">
                                <div className="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="is_default"
                                        name="is_default"
                                        checked={formData.is_default}
                                        onChange={handleChange}
                                        className="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                                    />
                                    <label htmlFor="is_default" className="ml-2 block text-sm text-gray-700">
                                        Set as default tax rate
                                    </label>
                                </div>
                                <div className="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="is_active"
                                        name="is_active"
                                        checked={formData.is_active}
                                        onChange={handleChange}
                                        className="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                                    />
                                    <label htmlFor="is_active" className="ml-2 block text-sm text-gray-700">
                                        Active
                                    </label>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="flex justify-end gap-3 pt-4 border-t border-gray-200">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={onClose}
                                    disabled={saving}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={saving}
                                >
                                    {saving ? 'Saving...' : taxRate ? 'Update' : 'Create'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default TaxRateModal;


