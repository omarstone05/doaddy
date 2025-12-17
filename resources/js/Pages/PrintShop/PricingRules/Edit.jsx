import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import PrintShopLayout from '../PrintShopLayout';
import { Button } from '@/Components/ui';
import { ArrowLeft, Save, Tag } from 'lucide-react';

export default function PricingRulesEdit({ pricingRule, materials, markupTypes }) {
    const { data, setData, put, processing, errors } = useForm({
        rule_name: pricingRule.rule_name || '',
        print_material_id: pricingRule.print_material_id || '',
        markup_type: pricingRule.markup_type || 'fixed_price',
        markup_value: pricingRule.markup_value || '',
        min_area: pricingRule.min_area || '',
        max_area: pricingRule.max_area || '',
        is_default: pricingRule.is_default || false,
        priority: pricingRule.priority || 0,
        is_active: pricingRule.is_active ?? true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/print-shop/pricing-rules/${pricingRule.id}`);
    };

    return (
        <PrintShopLayout>
            <Head title={`Edit ${pricingRule.rule_name}`} />

            {/* Header */}
            <div className="flex items-center gap-4 mb-8">
                <Link
                    href="/print-shop/pricing-rules"
                    className="p-2 rounded-xl hover:bg-gray-100 transition-colors"
                >
                    <ArrowLeft className="h-5 w-5 text-gray-600" />
                </Link>
                <div>
                    <h2 className="text-2xl font-black text-gray-900 tracking-tight">Edit Pricing Rule</h2>
                    <p className="text-gray-500 mt-1">{pricingRule.rule_name}</p>
                </div>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="grid lg:grid-cols-3 gap-8">
                    {/* Main Form */}
                    <div className="lg:col-span-2 space-y-6">
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                                    <Tag className="w-5 h-5 text-amber-600" />
                                </div>
                                <h3 className="text-lg font-bold text-gray-900">Pricing Rule Details</h3>
                            </div>

                            <div className="grid md:grid-cols-2 gap-6">
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Rule Name *</label>
                                    <input
                                        type="text"
                                        value={data.rule_name}
                                        onChange={(e) => setData('rule_name', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    />
                                    {errors.rule_name && <p className="mt-1 text-sm text-red-500">{errors.rule_name}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Apply to Material</label>
                                    <select
                                        value={data.print_material_id}
                                        onChange={(e) => setData('print_material_id', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    >
                                        <option value="">All Materials</option>
                                        {materials.map(m => (
                                            <option key={m.id} value={m.id}>{m.name}</option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Priority</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.priority}
                                        onChange={(e) => setData('priority', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Markup Type *</label>
                                    <select
                                        value={data.markup_type}
                                        onChange={(e) => setData('markup_type', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    >
                                        {Object.entries(markupTypes).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">
                                        {data.markup_type === 'percentage' ? 'Markup Percentage (%)' : 'Markup Value (K)'} *
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.markup_value}
                                        onChange={(e) => setData('markup_value', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    />
                                    {errors.markup_value && <p className="mt-1 text-sm text-red-500">{errors.markup_value}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Area Range */}
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <h3 className="text-lg font-bold text-gray-900 mb-6">Area Range (Optional)</h3>
                            <div className="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Minimum Area (sqm)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.min_area}
                                        onChange={(e) => setData('min_area', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Maximum Area (sqm)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.max_area}
                                        onChange={(e) => setData('max_area', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Options */}
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <h3 className="text-lg font-bold text-gray-900 mb-6">Options</h3>
                            <div className="space-y-4">
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.is_default}
                                        onChange={(e) => setData('is_default', e.target.checked)}
                                        className="w-5 h-5 text-violet-600 rounded focus:ring-violet-500"
                                    />
                                    <span className="text-sm font-semibold text-gray-700">Set as default pricing rule</span>
                                </label>
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                        className="w-5 h-5 text-violet-600 rounded focus:ring-violet-500"
                                    />
                                    <span className="text-sm font-semibold text-gray-700">Rule is active</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Actions */}
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-full gap-2 bg-violet-500 hover:bg-violet-600 mb-3"
                            >
                                <Save className="h-4 w-4" />
                                {processing ? 'Saving...' : 'Update Pricing Rule'}
                            </Button>
                            <Link
                                href="/print-shop/pricing-rules"
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

