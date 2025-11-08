import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Eye, DollarSign } from 'lucide-react';

export default function PayrollRunsIndex({ payrollRuns, filters }) {
    const getStatusBadge = (status) => {
        const badges = {
            draft: 'bg-gray-100 text-gray-700',
            processing: 'bg-blue-100 text-blue-700',
            completed: 'bg-green-100 text-green-700',
            cancelled: 'bg-red-100 text-red-700',
        };
        return badges[status] || 'bg-gray-100 text-gray-700';
    };

    return (
        <SectionLayout sectionName="People">
            <Head title="Payroll Runs" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Payroll Runs</h1>
                        <p className="text-gray-500 mt-1">Manage payroll processing for your team</p>
                    </div>
                    <Button onClick={() => router.visit('/payroll/runs/create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        New Payroll Run
                    </Button>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select
                            value={filters?.status || ''}
                            onChange={(e) => router.visit(`/payroll/runs?status=${e.target.value}`)}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                        >
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                {/* Payroll Runs Table */}
                <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Pay Period</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Date Range</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Items</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Total Amount</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Created By</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {payrollRuns.data.length === 0 ? (
                                <tr>
                                    <td colSpan="7" className="px-6 py-12 text-center text-gray-500">
                                        No payroll runs found. Create your first payroll run to get started.
                                    </td>
                                </tr>
                            ) : (
                                payrollRuns.data.map((run) => (
                                    <tr key={run.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-gray-900">{run.pay_period}</div>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {new Date(run.start_date).toLocaleDateString()} - {new Date(run.end_date).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900">
                                            {run.items?.length || 0} items
                                        </td>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                            {new Intl.NumberFormat('en-ZM', {
                                                style: 'currency',
                                                currency: 'ZMW',
                                            }).format(run.total_amount)}
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusBadge(run.status)}`}>
                                                {run.status.charAt(0).toUpperCase() + run.status.slice(1)}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {run.created_by?.name || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <Link href={`/payroll/runs/${run.id}`}>
                                                <Button variant="ghost" size="sm">View</Button>
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {payrollRuns.links && payrollRuns.links.length > 3 && (
                    <div className="mt-4 flex items-center justify-between">
                        <div className="text-sm text-gray-500">
                            Showing {payrollRuns.from} to {payrollRuns.to} of {payrollRuns.total} results
                        </div>
                        <div className="flex gap-2">
                            {payrollRuns.links.map((link, index) => (
                                <button
                                    key={index}
                                    onClick={() => link.url && router.visit(link.url)}
                                    disabled={!link.url}
                                    className={`px-3 py-2 text-sm border rounded-lg ${
                                        link.active
                                            ? 'bg-teal-500 text-white border-teal-500'
                                            : link.url
                                            ? 'border-gray-300 hover:bg-gray-50'
                                            : 'border-gray-200 text-gray-400 cursor-not-allowed'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}

