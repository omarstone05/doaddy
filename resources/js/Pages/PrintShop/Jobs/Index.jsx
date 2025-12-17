import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PrintShopLayout from '../PrintShopLayout';
import { Button } from '@/Components/ui';
import { 
    Plus, 
    Search, 
    Filter,
    Eye,
    Edit,
    Trash2,
    ClipboardList,
    CheckCircle,
    Clock,
    XCircle
} from 'lucide-react';

export default function JobsIndex({ jobs, materials, statusOptions, filters }) {
    const formatCurrency = (amount) => {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(num);
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this print job?')) {
            router.delete(`/print-shop/jobs/${id}`);
        }
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

    const getStatusIcon = (status) => {
        switch (status) {
            case 'completed': return CheckCircle;
            case 'cancelled': return XCircle;
            default: return Clock;
        }
    };

    return (
        <PrintShopLayout>
            <Head title="Print Jobs" />

            {/* Header */}
            <div className="flex items-center justify-between mb-8">
                <div>
                    <h2 className="text-2xl font-black text-gray-900 tracking-tight">Print Jobs</h2>
                    <p className="text-gray-500 mt-1">Manage quotes and print jobs</p>
                </div>
                <Button onClick={() => router.visit('/print-shop/jobs/create')} className="gap-2 bg-violet-500 hover:bg-violet-600">
                    <Plus className="h-4 w-4" />
                    New Job
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
                                onChange={(e) => router.visit(`/print-shop/jobs?search=${e.target.value}`, { preserveState: true })}
                                placeholder="Search job number..."
                                className="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                            />
                        </div>
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-gray-600 mb-2">Status</label>
                        <select
                            defaultValue={filters?.status || ''}
                            onChange={(e) => router.visit(`/print-shop/jobs?status=${e.target.value}`, { preserveState: true })}
                            className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-500"
                        >
                            <option value="">All Status</option>
                            {Object.entries(statusOptions).map(([key, label]) => (
                                <option key={key} value={key}>{label}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-gray-600 mb-2">Material</label>
                        <select
                            defaultValue={filters?.material_id || ''}
                            onChange={(e) => router.visit(`/print-shop/jobs?material_id=${e.target.value}`, { preserveState: true })}
                            className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-500"
                        >
                            <option value="">All Materials</option>
                            {materials.map(m => (
                                <option key={m.id} value={m.id}>{m.name}</option>
                            ))}
                        </select>
                    </div>
                </div>
            </div>

            {/* Jobs Table */}
            {jobs.data.length === 0 ? (
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-12 border border-gray-200/50 text-center">
                    <div className="w-16 h-16 rounded-2xl bg-violet-100 flex items-center justify-center mx-auto mb-4">
                        <ClipboardList className="h-8 w-8 text-violet-500" />
                    </div>
                    <h3 className="text-lg font-bold text-gray-900 mb-2">No print jobs yet</h3>
                    <p className="text-gray-500 mb-6">Create your first print job to get started</p>
                    <Button onClick={() => router.visit('/print-shop/jobs/create')} className="gap-2 bg-violet-500 hover:bg-violet-600">
                        <Plus className="h-4 w-4" />
                        Create Job
                    </Button>
                </div>
            ) : (
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-gray-50/80 border-b border-gray-200/50">
                            <tr>
                                <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Job #</th>
                                <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Customer</th>
                                <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Material</th>
                                <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Dimensions</th>
                                <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Total</th>
                                <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Margin</th>
                                <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                                <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {jobs.data.map((job) => {
                                const StatusIcon = getStatusIcon(job.status);
                                return (
                                    <tr key={job.id} className="hover:bg-violet-50/30 transition-colors">
                                        <td className="px-6 py-4">
                                            <Link 
                                                href={`/print-shop/jobs/${job.id}`}
                                                className="font-bold text-violet-600 hover:text-violet-700"
                                            >
                                                {job.job_number}
                                            </Link>
                                            <p className="text-xs text-gray-500 mt-1">
                                                {new Date(job.created_at).toLocaleDateString()}
                                            </p>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {job.customer?.name || <span className="text-gray-400 italic">Walk-in</span>}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900 font-medium">
                                            {job.print_material?.name || '-'}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            <span className="font-medium">{job.width}m × {job.height}m</span>
                                            {job.quantity > 1 && <span className="text-gray-400"> × {job.quantity}</span>}
                                            <p className="text-xs text-gray-400">{job.total_area} sqm</p>
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <span className="font-bold text-gray-900">{formatCurrency(job.grand_total)}</span>
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <span className={`font-bold ${job.margin_percentage >= 50 ? 'text-green-600' : job.margin_percentage >= 30 ? 'text-amber-600' : 'text-red-600'}`}>
                                                {job.margin_percentage}%
                                            </span>
                                            <p className="text-xs text-gray-400">{formatCurrency(job.total_margin)}</p>
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold ${getStatusColor(job.status)}`}>
                                                <StatusIcon className="h-3 w-3" />
                                                {job.status?.replace('_', ' ')}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center justify-center gap-1">
                                                <Link
                                                    href={`/print-shop/jobs/${job.id}`}
                                                    className="p-2 rounded-lg text-violet-600 hover:bg-violet-100 transition-colors"
                                                    title="View"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                                <Link
                                                    href={`/print-shop/jobs/${job.id}/edit`}
                                                    className="p-2 rounded-lg text-blue-600 hover:bg-blue-100 transition-colors"
                                                    title="Edit"
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(job.id)}
                                                    className="p-2 rounded-lg text-red-500 hover:bg-red-100 transition-colors"
                                                    title="Delete"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>

                    {/* Pagination */}
                    {jobs.links && jobs.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200/50 flex items-center justify-between bg-gray-50/50">
                            <p className="text-sm text-gray-600">
                                Showing <span className="font-semibold">{jobs.from}</span> to <span className="font-semibold">{jobs.to}</span> of <span className="font-semibold">{jobs.total}</span>
                            </p>
                            <div className="flex gap-1">
                                {jobs.links.map((link, index) => (
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

