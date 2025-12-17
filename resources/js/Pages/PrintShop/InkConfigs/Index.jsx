import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PrintShopLayout from '../PrintShopLayout';
import { Button } from '@/Components/ui';
import { 
    Plus, 
    Edit,
    Trash2,
    Droplets,
    Star,
    Package
} from 'lucide-react';

export default function InkConfigsIndex({ inkConfigurations }) {
    const formatCurrency = (amount) => {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(num);
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this ink configuration?')) {
            router.delete(`/print-shop/ink-configs/${id}`);
        }
    };

    const handleSetDefault = (id) => {
        router.post(`/print-shop/ink-configs/${id}/set-default`);
    };

    return (
        <PrintShopLayout>
            <Head title="Ink Configurations" />

            {/* Header */}
            <div className="flex items-center justify-between mb-8">
                <div>
                    <h2 className="text-2xl font-black text-gray-900 tracking-tight">Ink Configurations</h2>
                    <p className="text-gray-500 mt-1">Manage ink sets and their coverage costs</p>
                </div>
                <Button onClick={() => router.visit('/print-shop/ink-configs/create')} className="gap-2 bg-violet-500 hover:bg-violet-600">
                    <Plus className="h-4 w-4" />
                    Add Ink Config
                </Button>
            </div>

            {/* Ink Configs Grid */}
            {inkConfigurations.data.length === 0 ? (
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-12 border border-gray-200/50 text-center">
                    <div className="w-16 h-16 rounded-2xl bg-blue-100 flex items-center justify-center mx-auto mb-4">
                        <Droplets className="h-8 w-8 text-blue-500" />
                    </div>
                    <h3 className="text-lg font-bold text-gray-900 mb-2">No ink configurations yet</h3>
                    <p className="text-gray-500 mb-6">Add your first ink configuration to get started</p>
                    <Button onClick={() => router.visit('/print-shop/ink-configs/create')} className="gap-2 bg-violet-500 hover:bg-violet-600">
                        <Plus className="h-4 w-4" />
                        Add Ink Config
                    </Button>
                </div>
            ) : (
                <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {inkConfigurations.data.map((config) => (
                        <div 
                            key={config.id} 
                            className={`bg-white/90 backdrop-blur-sm rounded-2xl border-2 p-6 transition-all hover:shadow-lg ${
                                config.is_default ? 'border-violet-500' : 'border-gray-200/50'
                            }`}
                        >
                            <div className="flex items-start justify-between mb-4">
                                <div className="flex items-center gap-3">
                                    <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${
                                        config.is_default ? 'bg-violet-100' : 'bg-blue-100'
                                    }`}>
                                        <Droplets className={`h-6 w-6 ${
                                            config.is_default ? 'text-violet-600' : 'text-blue-600'
                                        }`} />
                                    </div>
                                    <div>
                                        <h3 className="font-bold text-gray-900">{config.name}</h3>
                                        {config.is_default && (
                                            <span className="inline-flex items-center gap-1 text-xs font-semibold text-violet-600">
                                                <Star className="h-3 w-3 fill-current" />
                                                Default
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div className="flex items-center gap-1">
                                    <Link
                                        href={`/print-shop/ink-configs/${config.id}/edit`}
                                        className="p-2 rounded-lg text-blue-600 hover:bg-blue-100 transition-colors"
                                    >
                                        <Edit className="h-4 w-4" />
                                    </Link>
                                    <button
                                        onClick={() => handleDelete(config.id)}
                                        className="p-2 rounded-lg text-red-500 hover:bg-red-100 transition-colors"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div className="space-y-3 mb-4">
                                <div className="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span className="text-sm text-gray-500">Bottles per Set</span>
                                    <span className="text-sm font-semibold text-gray-900">{config.bottles_per_set}</span>
                                </div>
                                <div className="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span className="text-sm text-gray-500">Cost per Set</span>
                                    <span className="text-sm font-semibold text-gray-900">{formatCurrency(config.cost_per_set)}</span>
                                </div>
                                <div className="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span className="text-sm text-gray-500">Coverage Area</span>
                                    <span className="text-sm font-semibold text-gray-900">{config.coverage_area} sqm</span>
                                </div>
                                <div className="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span className="text-sm text-gray-500">Coverage Multiplier</span>
                                    <span className="text-sm font-semibold text-gray-900">{config.coverage_multiplier}x</span>
                                </div>
                                <div className="flex justify-between items-center py-2">
                                    <span className="text-sm text-gray-500">Ink Cost/sqm</span>
                                    <span className="text-lg font-bold text-violet-600">{formatCurrency(config.ink_unit_cost)}</span>
                                </div>
                            </div>

                            <div className="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div className="flex items-center gap-1 text-sm text-gray-500">
                                    <Package className="h-4 w-4" />
                                    <span>{config.materials_count || 0} materials</span>
                                </div>
                                {!config.is_default && (
                                    <button
                                        onClick={() => handleSetDefault(config.id)}
                                        className="text-sm font-semibold text-violet-600 hover:text-violet-700"
                                    >
                                        Set as Default
                                    </button>
                                )}
                            </div>

                            {config.notes && (
                                <p className="mt-4 text-xs text-gray-500 bg-gray-50 p-3 rounded-lg">
                                    {config.notes}
                                </p>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {/* Pagination */}
            {inkConfigurations.links && inkConfigurations.links.length > 3 && (
                <div className="mt-8 flex items-center justify-center">
                    <div className="flex gap-1">
                        {inkConfigurations.links.map((link, index) => (
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
        </PrintShopLayout>
    );
}

