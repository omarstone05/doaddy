import React, { useState, useEffect } from 'react';
import { Head, useForm, Link, usePage } from '@inertiajs/react';
import PrintShopLayout from '../PrintShopLayout';
import { Button } from '@/Components/ui';
import { ArrowLeft, Save, Calculator, DollarSign, TrendingUp } from 'lucide-react';

export default function JobsCreate({ materials, customers }) {
    const { url } = usePage();
    const params = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');

    const { data, setData, post, processing, errors } = useForm({
        customer_id: '',
        print_material_id: params.get('material') || '',
        ink_configuration_id: params.get('ink') || '',
        width: params.get('width') || '',
        height: params.get('height') || '',
        quantity: params.get('qty') || 1,
        price_per_sqm: '',
        setup_cost: '',
        finishing_cost: '',
        delivery_cost: '',
        other_costs: '',
        notes: '',
    });

    const [calcResult, setCalcResult] = useState(null);
    const [calculating, setCalculating] = useState(false);

    const selectedMaterial = materials.find(m => m.id === parseInt(data.print_material_id));
    const availableInkConfigs = selectedMaterial?.ink_configurations || [];

    const formatCurrency = (amount) => {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(num);
    };

    const handleCalculate = async () => {
        if (!data.print_material_id || !data.ink_configuration_id || !data.width || !data.height) {
            return;
        }

        setCalculating(true);
        try {
            const response = await fetch('/print-shop/jobs/calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
                body: JSON.stringify({
                    print_material_id: data.print_material_id,
                    ink_configuration_id: data.ink_configuration_id,
                    width: parseFloat(data.width),
                    height: parseFloat(data.height),
                    quantity: parseInt(data.quantity),
                    price_per_sqm: data.price_per_sqm ? parseFloat(data.price_per_sqm) : null,
                }),
            });
            const result = await response.json();
            setCalcResult(result);

            // Set suggested price if not already set
            if (!data.price_per_sqm && result.pricing?.suggested_price_per_sqm) {
                setData('price_per_sqm', result.pricing.suggested_price_per_sqm);
            }
        } catch (error) {
            console.error('Calculation error:', error);
        } finally {
            setCalculating(false);
        }
    };

    useEffect(() => {
        if (data.print_material_id && data.ink_configuration_id && data.width && data.height) {
            handleCalculate();
        }
    }, [data.print_material_id, data.ink_configuration_id, data.width, data.height, data.quantity, data.price_per_sqm]);

    const handleMaterialChange = (e) => {
        const materialId = e.target.value;
        setData(prev => ({
            ...prev,
            print_material_id: materialId,
            ink_configuration_id: '',
        }));
        setCalcResult(null);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/print-shop/jobs');
    };

    const totalAdditionalCosts = (parseFloat(data.setup_cost) || 0) + 
        (parseFloat(data.finishing_cost) || 0) + 
        (parseFloat(data.delivery_cost) || 0) + 
        (parseFloat(data.other_costs) || 0);

    return (
        <PrintShopLayout>
            <Head title="New Print Job" />

            {/* Header */}
            <div className="flex items-center gap-4 mb-8">
                <Link
                    href="/print-shop/jobs"
                    className="p-2 rounded-xl hover:bg-gray-100 transition-colors"
                >
                    <ArrowLeft className="h-5 w-5 text-gray-600" />
                </Link>
                <div>
                    <h2 className="text-2xl font-black text-gray-900 tracking-tight">New Print Job</h2>
                    <p className="text-gray-500 mt-1">Create a quote or print job</p>
                </div>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="grid lg:grid-cols-3 gap-8">
                    {/* Main Form */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Customer Selection */}
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <h3 className="text-lg font-bold text-gray-900 mb-6">Customer (Optional)</h3>
                            <select
                                value={data.customer_id}
                                onChange={(e) => setData('customer_id', e.target.value)}
                                className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                            >
                                <option value="">Walk-in Customer</option>
                                {customers.map(c => (
                                    <option key={c.id} value={c.id}>{c.name}</option>
                                ))}
                            </select>
                        </div>

                        {/* Material & Ink Selection */}
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center">
                                    <Calculator className="w-5 h-5 text-violet-600" />
                                </div>
                                <h3 className="text-lg font-bold text-gray-900">Job Details</h3>
                            </div>

                            <div className="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Material *</label>
                                    <select
                                        value={data.print_material_id}
                                        onChange={handleMaterialChange}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    >
                                        <option value="">Select material...</option>
                                        {materials.map(m => (
                                            <option key={m.id} value={m.id}>{m.name}</option>
                                        ))}
                                    </select>
                                    {errors.print_material_id && <p className="mt-1 text-sm text-red-500">{errors.print_material_id}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Ink Configuration *</label>
                                    <select
                                        value={data.ink_configuration_id}
                                        onChange={(e) => setData('ink_configuration_id', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                        disabled={!data.print_material_id}
                                    >
                                        <option value="">Select ink config...</option>
                                        {availableInkConfigs.map(ic => (
                                            <option key={ic.id} value={ic.id}>
                                                {ic.name} {ic.is_default && '(Default)'}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.ink_configuration_id && <p className="mt-1 text-sm text-red-500">{errors.ink_configuration_id}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Width (m) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.width}
                                        onChange={(e) => setData('width', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                        placeholder="2.0"
                                    />
                                    {errors.width && <p className="mt-1 text-sm text-red-500">{errors.width}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Height (m) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.height}
                                        onChange={(e) => setData('height', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                        placeholder="3.0"
                                    />
                                    {errors.height && <p className="mt-1 text-sm text-red-500">{errors.height}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Quantity *</label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={data.quantity}
                                        onChange={(e) => setData('quantity', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    />
                                    {errors.quantity && <p className="mt-1 text-sm text-red-500">{errors.quantity}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Price per Sqm (K) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.price_per_sqm}
                                        onChange={(e) => setData('price_per_sqm', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                        placeholder="150.00"
                                    />
                                    {calcResult?.pricing?.suggested_price_per_sqm && (
                                        <p className="mt-1 text-xs text-violet-600">
                                            Suggested: K{calcResult.pricing.suggested_price_per_sqm}
                                        </p>
                                    )}
                                    {errors.price_per_sqm && <p className="mt-1 text-sm text-red-500">{errors.price_per_sqm}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Additional Costs */}
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                                    <DollarSign className="w-5 h-5 text-green-600" />
                                </div>
                                <h3 className="text-lg font-bold text-gray-900">Additional Costs (Optional)</h3>
                            </div>

                            <div className="grid md:grid-cols-4 gap-4">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Setup Cost (K)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.setup_cost}
                                        onChange={(e) => setData('setup_cost', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                        placeholder="0.00"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Finishing (K)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.finishing_cost}
                                        onChange={(e) => setData('finishing_cost', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                        placeholder="0.00"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Delivery (K)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.delivery_cost}
                                        onChange={(e) => setData('delivery_cost', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                        placeholder="0.00"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Other (K)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.other_costs}
                                        onChange={(e) => setData('other_costs', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                        placeholder="0.00"
                                    />
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
                                placeholder="Job notes, special instructions..."
                            />
                        </div>
                    </div>

                    {/* Sidebar - Cost Summary */}
                    <div className="space-y-6">
                        {/* Cost Breakdown */}
                        <div className="bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl p-6 text-white">
                            <h3 className="text-lg font-bold mb-4">Cost Summary</h3>
                            {calcResult ? (
                                <div className="space-y-4">
                                    <div className="bg-white/10 rounded-xl p-4">
                                        <p className="text-sm text-white/70">Total Area</p>
                                        <p className="text-2xl font-black">{calcResult.dimensions.total_area} sqm</p>
                                    </div>
                                    
                                    <div className="space-y-2 text-sm">
                                        <div className="flex justify-between">
                                            <span className="text-white/70">Material Cost/sqm</span>
                                            <span>{formatCurrency(calcResult.costs.material_unit_cost)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-white/70">Ink Cost/sqm</span>
                                            <span>{formatCurrency(calcResult.costs.ink_unit_cost)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-white/70">Off-Cut Cost</span>
                                            <span>{formatCurrency(calcResult.costs.off_cut_cost)}</span>
                                        </div>
                                        <div className="flex justify-between pt-2 border-t border-white/20">
                                            <span className="font-semibold">Total Cost</span>
                                            <span className="font-bold">{formatCurrency(calcResult.costs.total_cost)}</span>
                                        </div>
                                    </div>

                                    <div className="bg-white/10 rounded-xl p-4 mt-4">
                                        <div className="flex justify-between items-center mb-2">
                                            <span className="text-sm text-white/70">Print Price</span>
                                            <span className="text-lg font-bold">{formatCurrency(calcResult.pricing.total_price)}</span>
                                        </div>
                                        {totalAdditionalCosts > 0 && (
                                            <div className="flex justify-between items-center mb-2">
                                                <span className="text-sm text-white/70">Additional Costs</span>
                                                <span className="font-semibold">{formatCurrency(totalAdditionalCosts)}</span>
                                            </div>
                                        )}
                                        <div className="flex justify-between items-center pt-2 border-t border-white/20">
                                            <span className="font-semibold">Grand Total</span>
                                            <span className="text-xl font-black">
                                                {formatCurrency(calcResult.pricing.total_price + totalAdditionalCosts)}
                                            </span>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2 bg-green-500/20 rounded-xl p-4">
                                        <TrendingUp className="w-5 h-5" />
                                        <div>
                                            <p className="text-sm text-white/70">Margin</p>
                                            <p className="font-bold">
                                                {formatCurrency(calcResult.pricing.total_margin)} ({calcResult.pricing.margin_percentage}%)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="text-center py-8">
                                    <Calculator className="w-12 h-12 mx-auto mb-4 text-white/50" />
                                    <p className="text-white/70">Enter dimensions to calculate costs</p>
                                </div>
                            )}
                        </div>

                        {/* Actions */}
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <Button
                                type="submit"
                                disabled={processing || !calcResult}
                                className="w-full gap-2 bg-violet-500 hover:bg-violet-600 mb-3"
                            >
                                <Save className="h-4 w-4" />
                                {processing ? 'Creating...' : 'Create Job'}
                            </Button>
                            <Link
                                href="/print-shop/jobs"
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

