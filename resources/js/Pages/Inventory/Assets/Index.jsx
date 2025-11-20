import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { Plus, Eye, Edit, Trash2, Package, AlertTriangle, Search, Filter } from 'lucide-react';
import { useState } from 'react';

export default function AssetsIndex({ assets, stats, filters }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount || 0);
    };

    const getStatusColor = (status) => {
        const colors = {
            active: 'bg-green-100 text-green-800',
            inactive: 'bg-gray-100 text-gray-800',
            maintenance: 'bg-yellow-100 text-yellow-800',
            retired: 'bg-blue-100 text-blue-800',
            disposed: 'bg-red-100 text-red-800',
            lost: 'bg-red-100 text-red-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getConditionColor = (condition) => {
        const colors = {
            excellent: 'bg-green-100 text-green-800',
            good: 'bg-blue-100 text-blue-800',
            fair: 'bg-yellow-100 text-yellow-800',
            poor: 'bg-orange-100 text-orange-800',
            needs_repair: 'bg-red-100 text-red-800',
        };
        return colors[condition] || 'bg-gray-100 text-gray-800';
    };

    const handleDelete = (assetId) => {
        if (confirm('Are you sure you want to delete this asset?')) {
            router.delete(`/assets/${assetId}`);
        }
    };

    const [searchTerm, setSearchTerm] = useState(filters?.search || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.visit(`/assets?search=${searchTerm}`);
    };

    return (
        <SectionLayout sectionName="Inventory">
            <Head title="Assets" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Internal Assets</h1>
                        <p className="text-gray-500 mt-1">Manage your organization's internal assets</p>
                    </div>
                    <Button onClick={() => router.visit('/assets/create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        New Asset
                    </Button>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Total Assets</p>
                                <p className="text-3xl font-bold text-gray-900 mt-1">{stats?.total_assets || 0}</p>
                            </div>
                            <Package className="h-8 w-8 text-teal-500" />
                        </div>
                    </Card>
                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Active Assets</p>
                                <p className="text-3xl font-bold text-green-600 mt-1">{stats?.active_assets || 0}</p>
                            </div>
                            <Package className="h-8 w-8 text-green-500" />
                        </div>
                    </Card>
                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Total Value</p>
                                <p className="text-3xl font-bold text-blue-600 mt-1">{formatCurrency(stats?.total_value)}</p>
                            </div>
                            <Package className="h-8 w-8 text-blue-500" />
                        </div>
                    </Card>
                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Needs Maintenance</p>
                                <p className="text-3xl font-bold text-yellow-600 mt-1">{stats?.needs_maintenance || 0}</p>
                            </div>
                            <AlertTriangle className="h-8 w-8 text-yellow-500" />
                        </div>
                    </Card>
                </div>

                {/* Filters */}
                <Card className="p-4 mb-6">
                    <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <input
                                    type="text"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder="Name, asset number, tag, or serial..."
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                value={filters?.status || ''}
                                onChange={(e) => router.visit(`/assets?status=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="retired">Retired</option>
                                <option value="disposed">Disposed</option>
                                <option value="lost">Lost</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <input
                                type="text"
                                value={filters?.category || ''}
                                onChange={(e) => router.visit(`/assets?category=${e.target.value}`)}
                                placeholder="Filter by category..."
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>
                    </form>
                </Card>

                {/* Assets Table */}
                <Card className="overflow-hidden">
                    {assets.data && assets.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {assets.data.map((asset) => (
                                        <tr key={asset.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <div className="text-sm font-medium text-gray-900">{asset.name}</div>
                                                    {asset.asset_number && (
                                                        <div className="text-sm text-gray-500">#{asset.asset_number}</div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="text-sm text-gray-900">{asset.category || '-'}</span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="text-sm text-gray-900">{asset.location || '-'}</span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {asset.assigned_to_user ? (
                                                    <span className="text-sm text-gray-900">{asset.assigned_to_user.name}</span>
                                                ) : asset.assigned_to_department ? (
                                                    <span className="text-sm text-gray-900">{asset.assigned_to_department.name}</span>
                                                ) : (
                                                    <span className="text-sm text-gray-400">Unassigned</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="text-sm font-medium text-gray-900">{formatCurrency(asset.current_value)}</span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(asset.status)}`}>
                                                    {asset.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${getConditionColor(asset.condition)}`}>
                                                    {asset.condition}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link href={`/assets/${asset.id}`}>
                                                        <button className="text-teal-600 hover:text-teal-900">
                                                            <Eye className="h-4 w-4" />
                                                        </button>
                                                    </Link>
                                                    <Link href={`/assets/${asset.id}/edit`}>
                                                        <button className="text-blue-600 hover:text-blue-900">
                                                            <Edit className="h-4 w-4" />
                                                        </button>
                                                    </Link>
                                                    <button
                                                        onClick={() => handleDelete(asset.id)}
                                                        className="text-red-600 hover:text-red-900"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <Package className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No Assets Found</h3>
                            <p className="text-gray-500 mb-6">Get started by adding your first asset</p>
                            <Button onClick={() => router.visit('/assets/create')}>
                                <Plus className="h-4 w-4 mr-2" />
                                Add Asset
                            </Button>
                        </div>
                    )}

                    {/* Pagination */}
                    {assets.links && assets.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-gray-700">
                                    Showing {assets.from} to {assets.to} of {assets.total} results
                                </div>
                                <div className="flex gap-2">
                                    {assets.links.map((link, index) => (
                                        <button
                                            key={index}
                                            onClick={() => link.url && router.visit(link.url)}
                                            disabled={!link.url}
                                            className={`px-3 py-2 text-sm rounded-lg ${
                                                link.active
                                                    ? 'bg-teal-500 text-white'
                                                    : link.url
                                                    ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </SectionLayout>
    );
}

