import React, { useState, useEffect } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import PrintShopLayout from '../PrintShopLayout';
import { Button } from '@/Components/ui';
import { ArrowLeft, Save, Calculator, DollarSign, TrendingUp } from 'lucide-react';

export default function JobsEdit({ job, materials, customers }) {
    const { data, setData, put, processing, errors } = useForm({
        customer_id: job.customer_id || '',
        print_material_id: job.print_material_id || '',
        ink_configuration_id: job.ink_configuration_id || '',
        width: job.width || '',
        height: job.height || '',
        quantity: job.quantity || 1,
        price_per_sqm: job.price_per_sqm || '',
        setup_cost: job.setup_cost || '',
        finishing_cost: job.finishing_cost || '',
        delivery_cost: job.delivery_cost || '',
        other_costs: job.other_costs || '',
        notes: job.notes || '',
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
        put(`/print-shop/jobs/${job.id}`);
    };

    const totalAdditionalCosts = (parseFloat(data.setup_cost) || 0) + 
        (parseFloat(data.finishing_cost) || 0) + 
        (parseFloat(data.delivery_cost) || 0) + 
        (parseFloat(data.other_costs) || 0);

    return (
        <PrintShopLayout>
            <Head title={`Edit ${job.job_number}`} />

            {/* Header */}
            <div className="flex items-center gap-4 mb-8">
                <Link
                    href={`/print-shop/jobs/${job.id}`}
                    className="p-2 rounded-xl hover:bg-gray-100 transition-colors"
                >
                    <ArrowLeft className="h-5 w-5 text-gray-600" />
                </Link>
                <div>
                    <h2 className="text-2xl font-black text-gray-900 tracking-tight">Edit Job</h2>
                    <p className="text-gray-500 mt-1">{job.job_number}</p>
                </div>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="grid lg:grid-cols-3 gap-8">
                    {/* Main Form */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Customer Selection */}
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <h3 className="text-lg font-bold text-gray-900 mb-6">Customer</h3>
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
                                    />
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
                                <h3 className="text-lg font-bold text-gray-900">Additional Costs</h3>
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
                            />
                        </div>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Cost Summary */}
                        <div className="bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl p-6 text-white">
                            <h3 className="text-lg font-bold mb-4">Cost Summary</h3>
                            {calcResult ? (
                                <div className="space-y-4">
                                    <div className="bg-white/10 rounded-xl p-4">
                                        <p className="text-sm text-white/70">Total Area</p>
                                        <p className="text-2xl font-black">{calcResult.dimensions.total_area} sqm</p>
                                    </div>
                                    
                                    <div className="space-y-2 text-sm">
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
                                                <span className="text-sm text-white/70">Additional</span>
                                                <span>{formatCurrency(totalAdditionalCosts)}</span>
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
                                disabled={processing}
                                className="w-full gap-2 bg-violet-500 hover:bg-violet-600 mb-3"
                            >
                                <Save className="h-4 w-4" />
                                {processing ? 'Saving...' : 'Update Job'}
                            </Button>
                            <Link
                                href={`/print-shop/jobs/${job.id}`}
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

