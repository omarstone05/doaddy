import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import PrintShopLayout from '../PrintShopLayout';
import { Button } from '@/Components/ui';
import { ArrowLeft, Save, Droplets } from 'lucide-react';

export default function InkConfigsEdit({ inkConfiguration }) {
    const { data, setData, put, processing, errors } = useForm({
        name: inkConfiguration.name || '',
        bottles_per_set: inkConfiguration.bottles_per_set || 4,
        cost_per_set: inkConfiguration.cost_per_set || '',
        coverage_area: inkConfiguration.coverage_area || '',
        coverage_multiplier: inkConfiguration.coverage_multiplier || 1,
        is_default: inkConfiguration.is_default || false,
        notes: inkConfiguration.notes || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/print-shop/ink-configs/${inkConfiguration.id}`);
    };

    const effectiveCoverage = (parseFloat(data.coverage_area) || 0) * (parseInt(data.coverage_multiplier) || 1);
    const inkUnitCost = effectiveCoverage > 0 ? (parseFloat(data.cost_per_set) || 0) / effectiveCoverage : 0;

    return (
        <PrintShopLayout>
            <Head title={`Edit ${inkConfiguration.name}`} />

            {/* Header */}
            <div className="flex items-center gap-4 mb-8">
                <Link
                    href="/print-shop/ink-configs"
                    className="p-2 rounded-xl hover:bg-gray-100 transition-colors"
                >
                    <ArrowLeft className="h-5 w-5 text-gray-600" />
                </Link>
                <div>
                    <h2 className="text-2xl font-black text-gray-900 tracking-tight">Edit Ink Configuration</h2>
                    <p className="text-gray-500 mt-1">{inkConfiguration.name}</p>
                </div>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="grid lg:grid-cols-3 gap-8">
                    {/* Main Form */}
                    <div className="lg:col-span-2 space-y-6">
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                                    <Droplets className="w-5 h-5 text-blue-600" />
                                </div>
                                <h3 className="text-lg font-bold text-gray-900">Ink Configuration Details</h3>
                            </div>

                            <div className="grid md:grid-cols-2 gap-6">
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Name *</label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    />
                                    {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Bottles per Set *</label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={data.bottles_per_set}
                                        onChange={(e) => setData('bottles_per_set', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    />
                                    {errors.bottles_per_set && <p className="mt-1 text-sm text-red-500">{errors.bottles_per_set}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Cost per Set (K) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.cost_per_set}
                                        onChange={(e) => setData('cost_per_set', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    />
                                    {errors.cost_per_set && <p className="mt-1 text-sm text-red-500">{errors.cost_per_set}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Coverage Area (sqm) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.coverage_area}
                                        onChange={(e) => setData('coverage_area', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    />
                                    <p className="mt-1 text-xs text-gray-500">Square meters covered per ink set</p>
                                    {errors.coverage_area && <p className="mt-1 text-sm text-red-500">{errors.coverage_area}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Coverage Multiplier</label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={data.coverage_multiplier}
                                        onChange={(e) => setData('coverage_multiplier', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    />
                                    <p className="mt-1 text-xs text-gray-500">Use 3x for materials like Contra Vision</p>
                                    {errors.coverage_multiplier && <p className="mt-1 text-sm text-red-500">{errors.coverage_multiplier}</p>}
                                </div>

                                <div className="md:col-span-2">
                                    <label className="flex items-center gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.is_default}
                                            onChange={(e) => setData('is_default', e.target.checked)}
                                            className="w-5 h-5 text-violet-600 rounded focus:ring-violet-500"
                                        />
                                        <span className="text-sm font-semibold text-gray-700">Set as default ink configuration</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {/* Notes */}
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <label className="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                            <textarea
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                rows={4}
                                className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                            />
                        </div>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Calculated Preview */}
                        <div className="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl p-6 text-white">
                            <h3 className="text-lg font-bold mb-4">Calculated Values</h3>
                            <div className="space-y-4">
                                <div className="bg-white/10 rounded-xl p-4">
                                    <p className="text-sm text-white/70">Effective Coverage</p>
                                    <p className="text-2xl font-black">{effectiveCoverage.toFixed(2)} sqm</p>
                                    <p className="text-xs text-white/60 mt-1">
                                        {data.coverage_area || 0} × {data.coverage_multiplier || 1} multiplier
                                    </p>
                                </div>
                                <div className="bg-white/10 rounded-xl p-4">
                                    <p className="text-sm text-white/70">Ink Cost per sqm</p>
                                    <p className="text-2xl font-black">K{inkUnitCost.toFixed(2)}</p>
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
                                {processing ? 'Saving...' : 'Update Ink Config'}
                            </Button>
                            <Link
                                href="/print-shop/ink-configs"
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

