import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PrintShopLayout from '../PrintShopLayout';
import { Button } from '@/Components/ui';
import { 
    Plus, 
    Search, 
    Filter,
    Edit,
    Trash2,
    Package,
    Droplets,
    ToggleLeft,
    ToggleRight
} from 'lucide-react';

export default function MaterialsIndex({ materials, inkConfigurations, materialTypes, filters }) {
    const formatCurrency = (amount) => {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(num);
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this material?')) {
            router.delete(`/print-shop/materials/${id}`);
        }
    };

    const getTypeLabel = (type) => {
        return materialTypes[type] || type;
    };

    const getTypeColor = (type) => {
        const colors = {
            vinyl: 'bg-blue-100 text-blue-700',
            banner: 'bg-green-100 text-green-700',
            banner_flex: 'bg-teal-100 text-teal-700',
            contra_vision: 'bg-purple-100 text-purple-700',
            clear_vinyl: 'bg-pink-100 text-pink-700',
            custom: 'bg-gray-100 text-gray-700',
        };
        return colors[type] || colors.custom;
    };

    return (
        <PrintShopLayout>
            <Head title="Print Materials" />

            {/* Header */}
            <div className="flex items-center justify-between mb-8">
                <div>
                    <h2 className="text-2xl font-black text-gray-900 tracking-tight">Materials</h2>
                    <p className="text-gray-500 mt-1">Configure your print materials and their costs</p>
                </div>
                <Button onClick={() => router.visit('/print-shop/materials/create')} className="gap-2 bg-violet-500 hover:bg-violet-600">
                    <Plus className="h-4 w-4" />
                    Add Material
                </Button>
            </div>

            {/* Filters */}
            <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 mb-6">
                <div className="flex items-center gap-3 mb-4">
                    <div className="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center">
                        <Filter className="w-5 h-5 text-violet-600" />
                    </div>
                    <h3 className="text-sm font-bold text-gray-900">Filters</h3>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label className="block text-xs font-semibold text-gray-600 mb-2">Search</label>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                                type="text"
                                defaultValue={filters?.search || ''}
                                onChange={(e) => router.visit(`/print-shop/materials?search=${e.target.value}`, { preserveState: true })}
                                placeholder="Search materials..."
                                className="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                            />
                        </div>
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-gray-600 mb-2">Type</label>
                        <select
                            defaultValue={filters?.type || ''}
                            onChange={(e) => router.visit(`/print-shop/materials?type=${e.target.value}`, { preserveState: true })}
                            className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-500"
                        >
                            <option value="">All Types</option>
                            {Object.entries(materialTypes).map(([key, label]) => (
                                <option key={key} value={key}>{label}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-gray-600 mb-2">Status</label>
                        <select
                            defaultValue={filters?.active || ''}
                            onChange={(e) => router.visit(`/print-shop/materials?active=${e.target.value}`, { preserveState: true })}
                            className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-500"
                        >
                            <option value="">All Status</option>
                            <option value="true">Active</option>
                            <option value="false">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            {/* Materials Table */}
            {materials.data.length === 0 ? (
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-12 border border-gray-200/50 text-center">
                    <div className="w-16 h-16 rounded-2xl bg-violet-100 flex items-center justify-center mx-auto mb-4">
                        <Package className="h-8 w-8 text-violet-500" />
                    </div>
                    <h3 className="text-lg font-bold text-gray-900 mb-2">No materials yet</h3>
                    <p className="text-gray-500 mb-6">Add your first print material to get started</p>
                    <Button onClick={() => router.visit('/print-shop/materials/create')} className="gap-2 bg-violet-500 hover:bg-violet-600">
                        <Plus className="h-4 w-4" />
                        Add Material
                    </Button>
                </div>
            ) : (
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-gray-50/80 border-b border-gray-200/50">
                            <tr>
                                <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Material</th>
                                <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Type</th>
                                <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Roll Size</th>
                                <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Total Area</th>
                                <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Material Cost</th>
                                <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Unit Cost/sqm</th>
                                <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Ink Configs</th>
                                <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                                <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {materials.data.map((material) => (
                                <tr key={material.id} className="hover:bg-violet-50/30 transition-colors">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center">
                                                <Package className="h-5 w-5 text-violet-600" />
                                            </div>
                                            <div>
                                                <p className="font-bold text-gray-900">{material.name}</p>
                                                {material.notes && (
                                                    <p className="text-xs text-gray-500 truncate max-w-[200px]">{material.notes}</p>
                                                )}
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className={`inline-flex px-2.5 py-1 rounded-full text-xs font-semibold ${getTypeColor(material.material_type)}`}>
                                            {getTypeLabel(material.material_type)}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-right text-sm text-gray-600">
                                        {material.roll_width}m × {material.roll_length}m
                                    </td>
                                    <td className="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                        {material.total_area} sqm
                                    </td>
                                    <td className="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                        {formatCurrency(material.material_cost)}
                                    </td>
                                    <td className="px-6 py-4 text-right text-sm font-bold text-violet-600">
                                        {formatCurrency(material.material_unit_cost)}
                                    </td>
                                    <td className="px-6 py-4 text-center">
                                        <div className="flex items-center justify-center gap-1">
                                            <Droplets className="h-4 w-4 text-blue-500" />
                                            <span className="text-sm font-medium text-gray-700">
                                                {material.ink_configurations?.length || 0}
                                            </span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 text-center">
                                        {material.is_active ? (
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
                                                href={`/print-shop/materials/${material.id}/edit`}
                                                className="p-2 rounded-lg text-blue-600 hover:bg-blue-100 transition-colors"
                                                title="Edit"
                                            >
                                                <Edit className="h-4 w-4" />
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(material.id)}
                                                className="p-2 rounded-lg text-red-500 hover:bg-red-100 transition-colors"
                                                title="Delete"
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
                    {materials.links && materials.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200/50 flex items-center justify-between bg-gray-50/50">
                            <p className="text-sm text-gray-600">
                                Showing <span className="font-semibold">{materials.from}</span> to <span className="font-semibold">{materials.to}</span> of <span className="font-semibold">{materials.total}</span>
                            </p>
                            <div className="flex gap-1">
                                {materials.links.map((link, index) => (
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

