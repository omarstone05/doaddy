import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PrintShopLayout from '../PrintShopLayout';
import { Button } from '@/Components/ui';
import { 
    Plus, 
    Edit,
    Trash2,
    Tag,
    Star,
    ToggleLeft,
    ToggleRight
} from 'lucide-react';

export default function PricingRulesIndex({ pricingRules, markupTypes }) {
    const formatCurrency = (amount) => {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(num);
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this pricing rule?')) {
            router.delete(`/print-shop/pricing-rules/${id}`);
        }
    };

    const getMarkupDisplay = (rule) => {
        switch (rule.markup_type) {
            case 'percentage':
                return `${rule.markup_value}% markup`;
            case 'fixed_amount':
                return `+${formatCurrency(rule.markup_value)} per sqm`;
            case 'fixed_price':
                return `${formatCurrency(rule.markup_value)} per sqm`;
            default:
                return rule.markup_value;
        }
    };

    return (
        <PrintShopLayout>
            <Head title="Pricing Rules" />

            {/* Header */}
            <div className="flex items-center justify-between mb-8">
                <div>
                    <h2 className="text-2xl font-black text-gray-900 tracking-tight">Pricing Rules</h2>
                    <p className="text-gray-500 mt-1">Define markup rules for your print jobs</p>
                </div>
                <Button onClick={() => router.visit('/print-shop/pricing-rules/create')} className="gap-2 bg-violet-500 hover:bg-violet-600">
                    <Plus className="h-4 w-4" />
                    Add Pricing Rule
                </Button>
            </div>

            {/* Pricing Rules Table */}
            {pricingRules.data.length === 0 ? (
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-12 border border-gray-200/50 text-center">
                    <div className="w-16 h-16 rounded-2xl bg-amber-100 flex items-center justify-center mx-auto mb-4">
                        <Tag className="h-8 w-8 text-amber-500" />
                    </div>
                    <h3 className="text-lg font-bold text-gray-900 mb-2">No pricing rules yet</h3>
                    <p className="text-gray-500 mb-6">Add pricing rules to automatically calculate prices</p>
                    <Button onClick={() => router.visit('/print-shop/pricing-rules/create')} className="gap-2 bg-violet-500 hover:bg-violet-600">
                        <Plus className="h-4 w-4" />
                        Add Pricing Rule
                    </Button>
                </div>
            ) : (
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-gray-50/80 border-b border-gray-200/50">
                            <tr>
                                <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Rule</th>
                                <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Material</th>
                                <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Markup Type</th>
                                <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Value</th>
                                <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Area Range</th>
                                <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Priority</th>
                                <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                                <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {pricingRules.data.map((rule) => (
                                <tr key={rule.id} className="hover:bg-violet-50/30 transition-colors">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${
                                                rule.is_default ? 'bg-violet-100' : 'bg-amber-100'
                                            }`}>
                                                <Tag className={`h-5 w-5 ${
                                                    rule.is_default ? 'text-violet-600' : 'text-amber-600'
                                                }`} />
                                            </div>
                                            <div>
                                                <p className="font-bold text-gray-900">
                                                    {rule.rule_name}
                                                    {rule.is_default && (
                                                        <span className="ml-2 inline-flex items-center gap-1 text-xs bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full">
                                                            <Star className="h-3 w-3 fill-current" />
                                                            Default
                                                        </span>
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 text-sm text-gray-600">
                                        {rule.print_material?.name || (
                                            <span className="text-gray-400 italic">All materials</span>
                                        )}
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className={`inline-flex px-2.5 py-1 rounded-full text-xs font-semibold ${
                                            rule.markup_type === 'fixed_price' ? 'bg-green-100 text-green-700' :
                                            rule.markup_type === 'percentage' ? 'bg-blue-100 text-blue-700' :
                                            'bg-purple-100 text-purple-700'
                                        }`}>
                                            {markupTypes[rule.markup_type]}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-sm font-bold text-gray-900">
                                        {getMarkupDisplay(rule)}
                                    </td>
                                    <td className="px-6 py-4 text-center text-sm text-gray-600">
                                        {rule.min_area || rule.max_area ? (
                                            <span>
                                                {rule.min_area || '0'} - {rule.max_area || '∞'} sqm
                                            </span>
                                        ) : (
                                            <span className="text-gray-400">Any</span>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 text-center">
                                        <span className="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 text-sm font-bold text-gray-700">
                                            {rule.priority}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-center">
                                        {rule.is_active ? (
                                            <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                                <ToggleRight className="h-3 w-3" />
                                                Active
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                                <ToggleLeft className="h-3 w-3" />
                                                Inactive
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="flex items-center justify-center gap-1">
                                            <Link
                                                href={`/print-shop/pricing-rules/${rule.id}/edit`}
                                                className="p-2 rounded-lg text-blue-600 hover:bg-blue-100 transition-colors"
                                            >
                                                <Edit className="h-4 w-4" />
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(rule.id)}
                                                className="p-2 rounded-lg text-red-500 hover:bg-red-100 transition-colors"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    {/* Pagination */}
                    {pricingRules.links && pricingRules.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200/50 flex items-center justify-between bg-gray-50/50">
                            <p className="text-sm text-gray-600">
                                Showing <span className="font-semibold">{pricingRules.from}</span> to <span className="font-semibold">{pricingRules.to}</span> of <span className="font-semibold">{pricingRules.total}</span>
                            </p>
                            <div className="flex gap-1">
                                {pricingRules.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all ${
                                            link.active
                                                ? 'bg-violet-500 text-white shadow-sm'
                                                : 'bg-white border border-gray-200 text-gray-700 hover:bg-violet-50 hover:border-violet-200'
                                        } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            )}
        </PrintShopLayout>
    );
}

