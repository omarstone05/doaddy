import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Eye, Edit, Trash2, Target } from 'lucide-react';

export default function StrategicGoalsIndex({ goals, filters }) {
    const handleDelete = (goalId) => {
        if (confirm('Are you sure you want to delete this strategic goal?')) {
            router.delete(`/decisions/goals/${goalId}`);
        }
    };

    const getStatusBadge = (status) => {
        const badges = {
            draft: 'bg-gray-100 text-gray-700',
            active: 'bg-green-100 text-green-700',
            completed: 'bg-blue-100 text-blue-700',
            cancelled: 'bg-red-100 text-red-700',
        };
        return badges[status] || 'bg-gray-100 text-gray-700';
    };

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Strategic Goals" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Strategic Goals</h1>
                        <p className="text-gray-500 mt-1">Track your organization's strategic objectives</p>
                    </div>
                    <Button onClick={() => router.visit('/decisions/goals/create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        New Strategic Goal
                    </Button>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input
                                type="text"
                                value={filters?.search || ''}
                                onChange={(e) => router.visit(`/decisions/goals?search=${e.target.value}`)}
                                placeholder="Search goals..."
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                value={filters?.status || ''}
                                onChange={(e) => router.visit(`/decisions/goals?status=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Goals Table */}
                <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Goal</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Target Date</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Milestones</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Progress</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {goals.data.length === 0 ? (
                                <tr>
                                    <td colSpan="6" className="px-6 py-12 text-center text-gray-500">
                                        No strategic goals found. Create your first goal to get started.
                                    </td>
                                </tr>
                            ) : (
                                goals.data.map((goal) => (
                                    <tr key={goal.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-gray-900">{goal.title}</div>
                                            {goal.description && (
                                                <div className="text-sm text-gray-500 mt-1">{goal.description.substring(0, 60)}...</div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {new Date(goal.target_date).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {goal.milestones?.length || 0} milestones
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-2">
                                                <div className="flex-1 bg-gray-200 rounded-full h-2">
                                                    <div
                                                        className="bg-teal-500 h-2 rounded-full"
                                                        style={{ width: `${goal.progress_percentage}%` }}
                                                    />
                                                </div>
                                                <span className="text-sm font-medium text-gray-900">{goal.progress_percentage}%</span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusBadge(goal.status)}`}>
                                                {goal.status.charAt(0).toUpperCase() + goal.status.slice(1)}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link href={`/decisions/goals/${goal.id}`}>
                                                    <button className="text-gray-400 hover:text-teal-600">
                                                        <Eye className="h-4 w-4" />
                                                    </button>
                                                </Link>
                                                <Link href={`/decisions/goals/${goal.id}/edit`}>
                                                    <button className="text-gray-400 hover:text-teal-600">
                                                        <Edit className="h-4 w-4" />
                                                    </button>
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(goal.id)}
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
                {goals.links && goals.links.length > 3 && (
                    <div className="mt-4 flex items-center justify-between">
                        <div className="text-sm text-gray-500">
                            Showing {goals.from} to {goals.to} of {goals.total} results
                        </div>
                        <div className="flex gap-2">
                            {goals.links.map((link, index) => (
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

