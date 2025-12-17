import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PrintShopLayout from '../PrintShopLayout';
import { Button } from '@/Components/ui';
import { 
    ArrowLeft, 
    Edit, 
    Printer, 
    CheckCircle, 
    Clock,
    XCircle,
    User,
    Calendar,
    Package,
    Droplets,
    DollarSign,
    TrendingUp
} from 'lucide-react';

export default function JobsShow({ job, statusOptions }) {
    const formatCurrency = (amount) => {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(num);
    };

    const getStatusColor = (status) => {
        const colors = {
            draft: 'bg-gray-100 text-gray-700 border-gray-300',
            quoted: 'bg-blue-100 text-blue-700 border-blue-300',
            approved: 'bg-teal-100 text-teal-700 border-teal-300',
            in_progress: 'bg-amber-100 text-amber-700 border-amber-300',
            completed: 'bg-green-100 text-green-700 border-green-300',
            cancelled: 'bg-red-100 text-red-700 border-red-300',
        };
        return colors[status] || colors.draft;
    };

    const handleStatusUpdate = (newStatus) => {
        router.post(`/print-shop/jobs/${job.id}/status`, { status: newStatus });
    };

    return (
        <PrintShopLayout>
            <Head title={`Job ${job.job_number}`} />

            {/* Header */}
            <div className="flex items-center justify-between mb-8">
                <div className="flex items-center gap-4">
                    <Link
                        href="/print-shop/jobs"
                        className="p-2 rounded-xl hover:bg-gray-100 transition-colors"
                    >
                        <ArrowLeft className="h-5 w-5 text-gray-600" />
                    </Link>
                    <div>
                        <div className="flex items-center gap-3">
                            <h2 className="text-2xl font-black text-gray-900 tracking-tight">{job.job_number}</h2>
                            <span className={`inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold border ${getStatusColor(job.status)}`}>
                                {job.status?.replace('_', ' ')}
                            </span>
                        </div>
                        <p className="text-gray-500 mt-1">
                            Created {new Date(job.created_at).toLocaleDateString()} 
                            {job.created_by && ` by ${job.created_by.name}`}
                        </p>
                    </div>
                </div>
                <div className="flex gap-3">
                    <Link
                        href={`/print-shop/jobs/${job.id}/edit`}
                        className="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        <Edit className="h-4 w-4" />
                        Edit
                    </Link>
                </div>
            </div>

            <div className="grid lg:grid-cols-3 gap-8">
                {/* Main Content */}
                <div className="lg:col-span-2 space-y-6">
                    {/* Customer & Material Info */}
                    <div className="grid md:grid-cols-2 gap-6">
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                                    <User className="w-5 h-5 text-blue-600" />
                                </div>
                                <h3 className="font-bold text-gray-900">Customer</h3>
                            </div>
                            {job.customer ? (
                                <div>
                                    <p className="font-semibold text-gray-900">{job.customer.name}</p>
                                    {job.customer.email && (
                                        <p className="text-sm text-gray-500">{job.customer.email}</p>
                                    )}
                                    {job.customer.phone && (
                                        <p className="text-sm text-gray-500">{job.customer.phone}</p>
                                    )}
                                </div>
                            ) : (
                                <p className="text-gray-400 italic">Walk-in Customer</p>
                            )}
                        </div>

                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center">
                                    <Package className="w-5 h-5 text-violet-600" />
                                </div>
                                <h3 className="font-bold text-gray-900">Material</h3>
                            </div>
                            <p className="font-semibold text-gray-900">{job.print_material?.name}</p>
                            <p className="text-sm text-gray-500">{job.print_material?.material_type}</p>
                            <div className="mt-2 flex items-center gap-2">
                                <Droplets className="w-4 h-4 text-blue-500" />
                                <span className="text-sm text-gray-600">{job.ink_configuration?.name}</span>
                            </div>
                        </div>
                    </div>

                    {/* Dimensions */}
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                        <h3 className="font-bold text-gray-900 mb-4">Dimensions</h3>
                        <div className="grid grid-cols-4 gap-4">
                            <div className="bg-gray-50 rounded-xl p-4 text-center">
                                <p className="text-sm text-gray-500 mb-1">Width</p>
                                <p className="text-xl font-bold text-gray-900">{job.width}m</p>
                            </div>
                            <div className="bg-gray-50 rounded-xl p-4 text-center">
                                <p className="text-sm text-gray-500 mb-1">Height</p>
                                <p className="text-xl font-bold text-gray-900">{job.height}m</p>
                            </div>
                            <div className="bg-gray-50 rounded-xl p-4 text-center">
                                <p className="text-sm text-gray-500 mb-1">Quantity</p>
                                <p className="text-xl font-bold text-gray-900">{job.quantity}</p>
                            </div>
                            <div className="bg-violet-50 rounded-xl p-4 text-center">
                                <p className="text-sm text-violet-600 mb-1">Total Area</p>
                                <p className="text-xl font-bold text-violet-700">{job.total_area} sqm</p>
                            </div>
                        </div>
                    </div>

                    {/* Cost Breakdown */}
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                                <DollarSign className="w-5 h-5 text-green-600" />
                            </div>
                            <h3 className="font-bold text-gray-900">Cost Breakdown</h3>
                        </div>
                        
                        <div className="space-y-3">
                            <div className="flex justify-between py-2 border-b border-gray-100">
                                <span className="text-gray-600">Material Cost per sqm</span>
                                <span className="font-medium">{formatCurrency(job.material_unit_cost)}</span>
                            </div>
                            <div className="flex justify-between py-2 border-b border-gray-100">
                                <span className="text-gray-600">Ink Cost per sqm</span>
                                <span className="font-medium">{formatCurrency(job.ink_unit_cost)}</span>
                            </div>
                            <div className="flex justify-between py-2 border-b border-gray-100">
                                <span className="text-gray-600">Off-Cut Cost</span>
                                <span className="font-medium">{formatCurrency(job.off_cut_cost)}</span>
                            </div>
                            <div className="flex justify-between py-2 border-b border-gray-100">
                                <span className="font-semibold text-gray-900">Base Cost per sqm</span>
                                <span className="font-bold text-gray-900">{formatCurrency(job.base_unit_cost)}</span>
                            </div>
                            <div className="flex justify-between py-2 bg-gray-50 -mx-6 px-6 border-b border-gray-100">
                                <span className="font-semibold text-gray-900">Total Production Cost</span>
                                <span className="font-bold text-gray-900">{formatCurrency(job.total_cost)}</span>
                            </div>
                        </div>

                        {job.additional_costs > 0 && (
                            <div className="mt-4 pt-4 border-t border-gray-200 space-y-2">
                                <h4 className="font-semibold text-gray-700 mb-2">Additional Costs</h4>
                                {job.setup_cost > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">Setup</span>
                                        <span>{formatCurrency(job.setup_cost)}</span>
                                    </div>
                                )}
                                {job.finishing_cost > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">Finishing</span>
                                        <span>{formatCurrency(job.finishing_cost)}</span>
                                    </div>
                                )}
                                {job.delivery_cost > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">Delivery</span>
                                        <span>{formatCurrency(job.delivery_cost)}</span>
                                    </div>
                                )}
                                {job.other_costs > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">Other</span>
                                        <span>{formatCurrency(job.other_costs)}</span>
                                    </div>
                                )}
                            </div>
                        )}

                        {job.notes && (
                            <div className="mt-4 pt-4 border-t border-gray-200">
                                <h4 className="font-semibold text-gray-700 mb-2">Notes</h4>
                                <p className="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">{job.notes}</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Sidebar */}
                <div className="space-y-6">
                    {/* Pricing Summary */}
                    <div className="bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl p-6 text-white">
                        <h3 className="text-lg font-bold mb-4">Pricing Summary</h3>
                        <div className="space-y-4">
                            <div className="bg-white/10 rounded-xl p-4">
                                <p className="text-sm text-white/70">Price per sqm</p>
                                <p className="text-2xl font-black">{formatCurrency(job.price_per_sqm)}</p>
                            </div>
                            <div className="bg-white/10 rounded-xl p-4">
                                <p className="text-sm text-white/70">Print Total</p>
                                <p className="text-2xl font-black">{formatCurrency(job.total_price)}</p>
                            </div>
                            {job.additional_costs > 0 && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-white/70">Additional Costs</span>
                                    <span>{formatCurrency(job.additional_costs)}</span>
                                </div>
                            )}
                            <div className="bg-white/20 rounded-xl p-4">
                                <p className="text-sm text-white/70">Grand Total</p>
                                <p className="text-3xl font-black">{formatCurrency(job.grand_total)}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2 mt-4 bg-green-500/20 rounded-xl p-4">
                            <TrendingUp className="w-5 h-5" />
                            <div>
                                <p className="text-sm text-white/70">Profit Margin</p>
                                <p className="font-bold text-lg">
                                    {formatCurrency(job.total_margin)} ({job.margin_percentage}%)
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Status Actions */}
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                        <h3 className="font-bold text-gray-900 mb-4">Update Status</h3>
                        <div className="space-y-2">
                            {Object.entries(statusOptions).map(([key, label]) => (
                                <button
                                    key={key}
                                    onClick={() => handleStatusUpdate(key)}
                                    disabled={job.status === key}
                                    className={`w-full px-4 py-3 rounded-xl text-sm font-semibold transition-all ${
                                        job.status === key
                                            ? 'bg-violet-100 text-violet-700 border-2 border-violet-500'
                                            : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border-2 border-transparent'
                                    }`}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Timeline */}
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-6">
                        <h3 className="font-bold text-gray-900 mb-4">Timeline</h3>
                        <div className="space-y-4">
                            <div className="flex items-start gap-3">
                                <div className="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                    <Calendar className="w-4 h-4 text-green-600" />
                                </div>
                                <div>
                                    <p className="font-semibold text-gray-900">Created</p>
                                    <p className="text-sm text-gray-500">{new Date(job.created_at).toLocaleString()}</p>
                                </div>
                            </div>
                            {job.quoted_at && (
                                <div className="flex items-start gap-3">
                                    <div className="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                        <Clock className="w-4 h-4 text-blue-600" />
                                    </div>
                                    <div>
                                        <p className="font-semibold text-gray-900">Quoted</p>
                                        <p className="text-sm text-gray-500">{new Date(job.quoted_at).toLocaleString()}</p>
                                    </div>
                                </div>
                            )}
                            {job.approved_at && (
                                <div className="flex items-start gap-3">
                                    <div className="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0">
                                        <CheckCircle className="w-4 h-4 text-teal-600" />
                                    </div>
                                    <div>
                                        <p className="font-semibold text-gray-900">Approved</p>
                                        <p className="text-sm text-gray-500">{new Date(job.approved_at).toLocaleString()}</p>
                                    </div>
                                </div>
                            )}
                            {job.completed_at && (
                                <div className="flex items-start gap-3">
                                    <div className="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                        <CheckCircle className="w-4 h-4 text-green-600" />
                                    </div>
                                    <div>
                                        <p className="font-semibold text-gray-900">Completed</p>
                                        <p className="text-sm text-gray-500">{new Date(job.completed_at).toLocaleString()}</p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </PrintShopLayout>
    );
}

