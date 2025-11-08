import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Eye, Edit, Trash2, Calendar } from 'lucide-react';

export default function LeaveTypesIndex({ leaveTypes, filters }) {
    const handleDelete = (leaveTypeId) => {
        if (confirm('Are you sure you want to delete this leave type?')) {
            router.delete(`/leave/types/${leaveTypeId}`);
        }
    };

    return (
        <SectionLayout sectionName="People">
            <Head title="Leave Types" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Leave Types</h1>
                        <p className="text-gray-500 mt-1">Manage types of leave available to your team</p>
                    </div>
                    <Button onClick={() => router.visit('/leave/types/create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        New Leave Type
                    </Button>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input
                                type="text"
                                value={filters?.search || ''}
                                onChange={(e) => router.visit(`/leave/types?search=${e.target.value}`)}
                                placeholder="Search leave types..."
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                value={filters?.is_active || ''}
                                onChange={(e) => router.visit(`/leave/types?is_active=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Status</option>
                                <option value="true">Active</option>
                                <option value="false">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Leave Types Table */}
                <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Max Days/Year</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Carry Forward</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {leaveTypes.data.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="px-6 py-12 text-center text-gray-500">
                                        No leave types found. Create your first leave type to get started.
                                    </td>
                                </tr>
                            ) : (
                                leaveTypes.data.map((leaveType) => (
                                    <tr key={leaveType.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-gray-900">{leaveType.name}</div>
                                            {leaveType.description && (
                                                <div className="text-sm text-gray-500 mt-1">{leaveType.description}</div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900">
                                            {leaveType.maximum_days_per_year} days
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900">
                                            {leaveType.can_carry_forward ? (
                                                <span className="text-green-600">
                                                    Yes {leaveType.max_carry_forward_days && `(${leaveType.max_carry_forward_days} max)`}
                                                </span>
                                            ) : (
                                                <span className="text-gray-400">No</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                leaveType.is_active
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-gray-100 text-gray-700'
                                            }`}>
                                                {leaveType.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link href={`/leave/types/${leaveType.id}/edit`}>
                                                    <button className="text-gray-400 hover:text-teal-600">
                                                        <Edit className="h-4 w-4" />
                                                    </button>
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(leaveType.id)}
                                                    className="text-gray-400 hover:text-red-600"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {leaveTypes.links && leaveTypes.links.length > 3 && (
                    <div className="mt-4 flex items-center justify-between">
                        <div className="text-sm text-gray-500">
                            Showing {leaveTypes.from} to {leaveTypes.to} of {leaveTypes.total} results
                        </div>
                        <div className="flex gap-2">
                            {leaveTypes.links.map((link, index) => (
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

