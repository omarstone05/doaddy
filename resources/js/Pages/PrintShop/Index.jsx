import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PrintShopLayout from './PrintShopLayout';
import { 
    Card, 
    CardHeader,
    StatCard,
    Button,
} from '@/Components/ui';
import { 
    Package, 
    Droplets, 
    Tag, 
    ClipboardList,
    Calculator,
    TrendingUp,
    DollarSign,
    Plus,
    ArrowRight,
    Printer,
    BarChart3
} from 'lucide-react';

export default function PrintShopIndex({ stats, recentJobs, materials }) {
    const [calcForm, setCalcForm] = useState({
        material_id: materials[0]?.id || '',
        ink_config_id: '',
        width: '',
        height: '',
        quantity: 1,
    });
    const [calcResult, setCalcResult] = useState(null);
    const [calculating, setCalculating] = useState(false);

    const selectedMaterial = materials.find(m => m.id === parseInt(calcForm.material_id));
    const availableInkConfigs = selectedMaterial?.ink_configurations || [];

    const formatCurrency = (amount) => {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(num);
    };

    const handleQuickCalculate = async () => {
        if (!calcForm.material_id || !calcForm.ink_config_id || !calcForm.width || !calcForm.height) {
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
                    print_material_id: calcForm.material_id,
                    ink_configuration_id: calcForm.ink_config_id,
                    width: parseFloat(calcForm.width),
                    height: parseFloat(calcForm.height),
                    quantity: parseInt(calcForm.quantity),
                }),
            });
            const data = await response.json();
            setCalcResult(data);
        } catch (error) {
            console.error('Calculation error:', error);
        } finally {
            setCalculating(false);
        }
    };

    const handleMaterialChange = (e) => {
        const materialId = e.target.value;
        setCalcForm(prev => ({
            ...prev,
            material_id: materialId,
            ink_config_id: '',
        }));
        setCalcResult(null);
    };

    const getStatusColor = (status) => {
        const colors = {
            draft: 'bg-gray-100 text-gray-700',
            quoted: 'bg-blue-100 text-blue-700',
            approved: 'bg-teal-100 text-teal-700',
            in_progress: 'bg-amber-100 text-amber-700',
            completed: 'bg-green-100 text-green-700',
            cancelled: 'bg-red-100 text-red-700',
        };
        return colors[status] || colors.draft;
    };

    return (
        <PrintShopLayout>
            <Head title="Print Shop" />

            {/* Quick Stats */}
            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
                <StatCard
                    title="Materials"
                    value={stats?.total_materials || 0}
                    icon={Package}
                    variant="glass"
                />
                <StatCard
                    title="Ink Configs"
                    value={stats?.total_ink_configs || 0}
                    icon={Droplets}
                    variant="glass"
                />
                <StatCard
                    title="Pricing Rules"
                    value={stats?.total_pricing_rules || 0}
                    icon={Tag}
                    variant="glass"
                />
                <StatCard
                    title="Pending Jobs"
                    value={stats?.pending_jobs || 0}
                    icon={ClipboardList}
                    variant={stats?.pending_jobs > 0 ? 'gradient-positive' : 'glass'}
                />
                <StatCard
                    title="Revenue"
                    value={formatCurrency(stats?.total_revenue || 0)}
                    icon={DollarSign}
                    variant="glass"
                />
                <StatCard
                    title="Profit"
                    value={formatCurrency(stats?.total_profit || 0)}
                    icon={TrendingUp}
                    variant="gradient-positive"
                />
            </div>

            <div className="grid lg:grid-cols-3 gap-8">
                {/* Quick Calculator */}
                <div className="lg:col-span-2">
                    <Card variant="glass" padding="none" className="overflow-hidden">
                        <div className="bg-gradient-to-r from-violet-500 to-purple-600 px-6 py-4">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                                    <Calculator className="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold text-white">Quick Calculator</h3>
                                    <p className="text-sm text-white/70">Get instant cost estimates</p>
                                </div>
                            </div>
                        </div>
                        
                        <div className="p-6">
                            <div className="grid md:grid-cols-2 gap-6">
                                {/* Input Form */}
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-xs font-semibold text-gray-600 mb-2">Material</label>
                                        <select
                                            value={calcForm.material_id}
                                            onChange={handleMaterialChange}
                                            className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                                        >
                                            <option value="">Select material...</option>
                                            {materials.map(m => (
                                                <option key={m.id} value={m.id}>{m.name}</option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-gray-600 mb-2">Ink Configuration</label>
                                        <select
                                            value={calcForm.ink_config_id}
                                            onChange={(e) => {
                                                setCalcForm(prev => ({ ...prev, ink_config_id: e.target.value }));
                                                setCalcResult(null);
                                            }}
                                            className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                                            disabled={!calcForm.material_id}
                                        >
                                            <option value="">Select ink config...</option>
                                            {availableInkConfigs.map(ic => (
                                                <option key={ic.id} value={ic.id}>
                                                    {ic.name} {ic.is_default && '(Default)'}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="grid grid-cols-3 gap-3">
                                        <div>
                                            <label className="block text-xs font-semibold text-gray-600 mb-2">Width (m)</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                value={calcForm.width}
                                                onChange={(e) => {
                                                    setCalcForm(prev => ({ ...prev, width: e.target.value }));
                                                    setCalcResult(null);
                                                }}
                                                placeholder="2.0"
                                                className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs font-semibold text-gray-600 mb-2">Height (m)</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                value={calcForm.height}
                                                onChange={(e) => {
                                                    setCalcForm(prev => ({ ...prev, height: e.target.value }));
                                                    setCalcResult(null);
                                                }}
                                                placeholder="3.0"
                                                className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs font-semibold text-gray-600 mb-2">Qty</label>
                                            <input
                                                type="number"
                                                min="1"
                                                value={calcForm.quantity}
                                                onChange={(e) => {
                                                    setCalcForm(prev => ({ ...prev, quantity: e.target.value }));
                                                    setCalcResult(null);
                                                }}
                                                className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                                            />
                                        </div>
                                    </div>

                                    <Button
                                        onClick={handleQuickCalculate}
                                        disabled={calculating || !calcForm.material_id || !calcForm.ink_config_id || !calcForm.width || !calcForm.height}
                                        className="w-full bg-gradient-to-r from-violet-500 to-purple-600 hover:from-violet-600 hover:to-purple-700"
                                    >
                                        {calculating ? 'Calculating...' : 'Calculate'}
                                    </Button>
                                </div>

                                {/* Results */}
                                <div className="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-5">
                                    {calcResult ? (
                                        <div className="space-y-4">
                                            <div className="flex items-center justify-between pb-3 border-b border-gray-200">
                                                <span className="text-sm text-gray-600">Total Area</span>
                                                <span className="text-lg font-bold text-gray-900">{calcResult.dimensions.total_area} sqm</span>
                                            </div>
                                            
                                            <div className="space-y-2">
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-500">Material Cost/sqm</span>
                                                    <span className="font-medium">{formatCurrency(calcResult.costs.material_unit_cost)}</span>
                                                </div>
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-500">Ink Cost/sqm</span>
                                                    <span className="font-medium">{formatCurrency(calcResult.costs.ink_unit_cost)}</span>
                                                </div>
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-500">Off-Cut Cost</span>
                                                    <span className="font-medium">{formatCurrency(calcResult.costs.off_cut_cost)}</span>
                                                </div>
                                                <div className="flex justify-between text-sm pt-2 border-t border-gray-200">
                                                    <span className="font-semibold text-gray-700">Total Cost</span>
                                                    <span className="font-bold text-gray-900">{formatCurrency(calcResult.costs.total_cost)}</span>
                                                </div>
                                            </div>

                                            <div className="bg-violet-50 rounded-xl p-4 mt-4">
                                                <div className="flex justify-between items-center mb-2">
                                                    <span className="text-sm font-semibold text-violet-700">Suggested Price/sqm</span>
                                                    <span className="text-xl font-black text-violet-600">{formatCurrency(calcResult.pricing.suggested_price_per_sqm)}</span>
                                                </div>
                                                <div className="flex justify-between items-center mb-2">
                                                    <span className="text-sm text-violet-600">Total Price</span>
                                                    <span className="text-lg font-bold text-violet-700">{formatCurrency(calcResult.pricing.total_price)}</span>
                                                </div>
                                                <div className="flex justify-between items-center">
                                                    <span className="text-sm text-violet-600">Margin</span>
                                                    <span className="font-bold text-green-600">
                                                        {formatCurrency(calcResult.pricing.total_margin)} ({calcResult.pricing.margin_percentage}%)
                                                    </span>
                                                </div>
                                            </div>

                                            <Link
                                                href={`/print-shop/jobs/create?material=${calcForm.material_id}&ink=${calcForm.ink_config_id}&width=${calcForm.width}&height=${calcForm.height}&qty=${calcForm.quantity}`}
                                                className="flex items-center justify-center gap-2 w-full mt-4 px-4 py-3 bg-gray-900 hover:bg-gray-800 text-white rounded-xl text-sm font-semibold transition-colors"
                                            >
                                                Create Job
                                                <ArrowRight className="w-4 h-4" />
                                            </Link>
                                        </div>
                                    ) : (
                                        <div className="h-full flex flex-col items-center justify-center text-center py-8">
                                            <div className="w-16 h-16 rounded-2xl bg-gray-200 flex items-center justify-center mb-4">
                                                <Calculator className="w-8 h-8 text-gray-400" />
                                            </div>
                                            <p className="text-sm text-gray-500">Enter dimensions to calculate costs</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Quick Actions */}
                <div>
                    <Card variant="glass" padding="md">
                        <CardHeader label="Quick Actions" />
                        <div className="space-y-3">
                            <Link
                                href="/print-shop/jobs/create"
                                className="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-violet-50 to-purple-50 hover:from-violet-100 hover:to-purple-100 transition-all group"
                            >
                                <div className="w-10 h-10 rounded-xl bg-violet-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <Plus className="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <p className="font-bold text-gray-900">New Print Job</p>
                                    <p className="text-xs text-gray-500">Create quote or job</p>
                                </div>
                            </Link>

                            <Link
                                href="/print-shop/materials/create"
                                className="flex items-center gap-3 p-4 rounded-xl hover:bg-gray-50 transition-all group"
                            >
                                <div className="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <Package className="w-5 h-5 text-teal-600" />
                                </div>
                                <div>
                                    <p className="font-semibold text-gray-900">Add Material</p>
                                    <p className="text-xs text-gray-500">Configure new material</p>
                                </div>
                            </Link>

                            <Link
                                href="/print-shop/ink-configs/create"
                                className="flex items-center gap-3 p-4 rounded-xl hover:bg-gray-50 transition-all group"
                            >
                                <div className="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <Droplets className="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <p className="font-semibold text-gray-900">Add Ink Config</p>
                                    <p className="text-xs text-gray-500">Set up ink configuration</p>
                                </div>
                            </Link>

                            <Link
                                href="/print-shop/pricing-rules/create"
                                className="flex items-center gap-3 p-4 rounded-xl hover:bg-gray-50 transition-all group"
                            >
                                <div className="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <Tag className="w-5 h-5 text-amber-600" />
                                </div>
                                <div>
                                    <p className="font-semibold text-gray-900">Add Pricing Rule</p>
                                    <p className="text-xs text-gray-500">Define markup rules</p>
                                </div>
                            </Link>
                        </div>
                    </Card>
                </div>
            </div>

            {/* Recent Jobs */}
            {recentJobs && recentJobs.length > 0 && (
                <Card variant="glass" padding="md" className="mt-8">
                    <div className="flex items-center justify-between mb-4">
                        <CardHeader label="Recent Jobs" />
                        <Link
                            href="/print-shop/jobs"
                            className="text-sm font-semibold text-violet-600 hover:text-violet-700 flex items-center gap-1"
                        >
                            View All
                            <ArrowRight className="w-4 h-4" />
                        </Link>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-gray-200">
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Job #</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Material</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Dimensions</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Customer</th>
                                    <th className="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Total</th>
                                    <th className="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {recentJobs.map((job) => (
                                    <tr key={job.id} className="hover:bg-violet-50/30 transition-colors">
                                        <td className="px-4 py-3">
                                            <Link 
                                                href={`/print-shop/jobs/${job.id}`}
                                                className="font-bold text-violet-600 hover:text-violet-700"
                                            >
                                                {job.job_number}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-900">
                                            {job.print_material?.name || '-'}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-600">
                                            {job.width}m × {job.height}m × {job.quantity}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-600">
                                            {job.customer?.name || 'Walk-in'}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-right font-bold text-gray-900">
                                            {formatCurrency(job.grand_total)}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <span className={`inline-flex px-2.5 py-1 rounded-full text-xs font-semibold ${getStatusColor(job.status)}`}>
                                                {job.status?.replace('_', ' ')}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            )}
        </PrintShopLayout>
    );
}

