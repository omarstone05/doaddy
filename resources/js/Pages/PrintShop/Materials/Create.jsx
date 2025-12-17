import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import PrintShopLayout from '../PrintShopLayout';
import { Button } from '@/Components/ui';
import { ArrowLeft, Save, Package, Droplets } from 'lucide-react';

export default function MaterialsCreate({ inkConfigurations, materialTypes }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        material_type: 'vinyl',
        roll_width: '',
        roll_length: '',
        material_cost: '',
        off_cut_cost: '7.00',
        notes: '',
        ink_configuration_ids: [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/print-shop/materials');
    };

    const toggleInkConfig = (id) => {
        const newIds = data.ink_configuration_ids.includes(id)
            ? data.ink_configuration_ids.filter(i => i !== id)
            : [...data.ink_configuration_ids, id];
        setData('ink_configuration_ids', newIds);
    };

    const calculatedArea = (parseFloat(data.roll_width) || 0) * (parseFloat(data.roll_length) || 0);
    const calculatedUnitCost = calculatedArea > 0 ? (parseFloat(data.material_cost) || 0) / calculatedArea : 0;

    return (
        <PrintShopLayout>
            <Head title="Add Material" />

            {/* Header */}
            <div className="flex items-center gap-4 mb-8">
                <Link
                    href="/print-shop/materials"
                    className="p-2 rounded-xl hover:bg-gray-100 transition-colors"
                >
                    <ArrowLeft className="h-5 w-5 text-gray-600" />
                </Link>
                <div>
                    <h2 className="text-2xl font-black text-gray-900 tracking-tight">Add Material</h2>
                    <p className="text-gray-500 mt-1">Configure a new print material</p>
                </div>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="grid lg:grid-cols-3 gap-8">
                    {/* Main Form */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Basic Info */}
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center">
                                    <Package className="w-5 h-5 text-violet-600" />
                                </div>
                                <h3 className="text-lg font-bold text-gray-900">Material Details</h3>
                            </div>

                            <div className="grid md:grid-cols-2 gap-6">
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Name *</label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                        placeholder="e.g., Standard Vinyl"
                                    />
                                    {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Material Type *</label>
                                    <select
                                        value={data.material_type}
                                        onChange={(e) => setData('material_type', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    >
                                        {Object.entries(materialTypes).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                    {errors.material_type && <p className="mt-1 text-sm text-red-500">{errors.material_type}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Off-Cut Cost (K)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.off_cut_cost}
                                        onChange={(e) => setData('off_cut_cost', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    />
                                    {errors.off_cut_cost && <p className="mt-1 text-sm text-red-500">{errors.off_cut_cost}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Roll Dimensions & Cost */}
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <h3 className="text-lg font-bold text-gray-900 mb-6">Roll Specifications</h3>
                            
                            <div className="grid md:grid-cols-3 gap-6">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Roll Width (m) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.roll_width}
                                        onChange={(e) => setData('roll_width', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                        placeholder="1.0"
                                    />
                                    {errors.roll_width && <p className="mt-1 text-sm text-red-500">{errors.roll_width}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Roll Length (m) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.roll_length}
                                        onChange={(e) => setData('roll_length', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                        placeholder="40.0"
                                    />
                                    {errors.roll_length && <p className="mt-1 text-sm text-red-500">{errors.roll_length}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Material Cost (K) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.material_cost}
                                        onChange={(e) => setData('material_cost', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                        placeholder="1400.00"
                                    />
                                    {errors.material_cost && <p className="mt-1 text-sm text-red-500">{errors.material_cost}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Ink Configurations */}
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                                    <Droplets className="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold text-gray-900">Ink Configurations</h3>
                                    <p className="text-sm text-gray-500">Select compatible ink configurations</p>
                                </div>
                            </div>

                            {inkConfigurations.length === 0 ? (
                                <div className="text-center py-8">
                                    <p className="text-gray-500 mb-4">No ink configurations available</p>
                                    <Link
                                        href="/print-shop/ink-configs/create"
                                        className="text-violet-600 hover:text-violet-700 font-semibold"
                                    >
                                        Create one first →
                                    </Link>
                                </div>
                            ) : (
                                <div className="grid md:grid-cols-2 gap-3">
                                    {inkConfigurations.map((config) => (
                                        <label
                                            key={config.id}
                                            className={`flex items-center gap-3 p-4 rounded-xl cursor-pointer transition-all ${
                                                data.ink_configuration_ids.includes(config.id)
                                                    ? 'bg-violet-50 border-2 border-violet-500'
                                                    : 'bg-gray-50 border-2 border-transparent hover:border-gray-200'
                                            }`}
                                        >
                                            <input
                                                type="checkbox"
                                                checked={data.ink_configuration_ids.includes(config.id)}
                                                onChange={() => toggleInkConfig(config.id)}
                                                className="w-4 h-4 text-violet-600 rounded focus:ring-violet-500"
                                            />
                                            <div>
                                                <p className="font-semibold text-gray-900">
                                                    {config.name}
                                                    {config.is_default && (
                                                        <span className="ml-2 text-xs bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full">
                                                            Default
                                                        </span>
                                                    )}
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    K{config.cost_per_set} / {config.coverage_area}sqm coverage
                                                </p>
                                            </div>
                                        </label>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Notes */}
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <label className="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                            <textarea
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                rows={4}
                                className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                placeholder="Additional notes about this material..."
                            />
                        </div>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Calculated Preview */}
                        <div className="bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl p-6 text-white">
                            <h3 className="text-lg font-bold mb-4">Calculated Values</h3>
                            <div className="space-y-4">
                                <div className="bg-white/10 rounded-xl p-4">
                                    <p className="text-sm text-white/70">Total Roll Area</p>
                                    <p className="text-2xl font-black">{calculatedArea.toFixed(2)} sqm</p>
                                </div>
                                <div className="bg-white/10 rounded-xl p-4">
                                    <p className="text-sm text-white/70">Material Cost per sqm</p>
                                    <p className="text-2xl font-black">K{calculatedUnitCost.toFixed(2)}</p>
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-full gap-2 bg-violet-500 hover:bg-violet-600 mb-3"
                            >
                                <Save className="h-4 w-4" />
                                {processing ? 'Saving...' : 'Save Material'}
                            </Button>
                            <Link
                                href="/print-shop/materials"
                                className="flex items-center justify-center w-full px-4 py-3 text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors"
                            >
                                Cancel
                            </Link>
                        </div>
                    </div>
                </div>
            </form>
        </PrintShopLayout>
    );
}

