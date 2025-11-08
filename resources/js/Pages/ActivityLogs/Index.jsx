import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Clock, User, FileText } from 'lucide-react';

export default function ActivityLogsIndex({ activityLogs, users, filters }) {
    const formatDate = (date) => {
        return new Date(date).toLocaleString();
    };

    return (
        <SectionLayout sectionName="Compliance">
            <Head title="Activity Logs" />
            <div>
                <div className="mb-6">
                    <h1 className="text-3xl font-bold text-gray-900">Activity Logs</h1>
                    <p className="text-gray-500 mt-1">Track user activities and system events</p>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">User</label>
                            <select
                                value={filters?.user_id || ''}
                                onChange={(e) => router.visit(`/activity-logs?user_id=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Users</option>
                                {users.map((user) => (
                                    <option key={user.id} value={user.id}>
                                        {user.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Action Type</label>
                            <select
                                value={filters?.action_type || ''}
                                onChange={(e) => router.visit(`/activity-logs?action_type=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Actions</option>
                                <option value="create">Create</option>
                                <option value="update">Update</option>
                                <option value="delete">Delete</option>
                                <option value="login">Login</option>
                                <option value="logout">Logout</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                            <input
                                type="date"
                                value={filters?.date_from || ''}
                                onChange={(e) => router.visit(`/activity-logs?date_from=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                            <input
                                type="date"
                                value={filters?.date_to || ''}
                                onChange={(e) => router.visit(`/activity-logs?date_to=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                    </div>
                </div>

                {/* Activity Logs Table */}
                <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">User</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Action</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Description</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Model</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {activityLogs.data.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="px-6 py-12 text-center text-gray-500">
                                        No activity logs found.
                                    </td>
                                </tr>
                            ) : (
                                activityLogs.data.map((log) => (
                                    <tr key={log.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-2">
                                                <User className="h-4 w-4 text-gray-400" />
                                                <span className="text-sm text-gray-900">{log.user?.name || 'System'}</span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                log.action_type === 'create' ? 'bg-green-100 text-green-700' :
                                                log.action_type === 'update' ? 'bg-blue-100 text-blue-700' :
                                                log.action_type === 'delete' ? 'bg-red-100 text-red-700' :
                                                'bg-gray-100 text-gray-700'
                                            }`}>
                                                {log.action_type.charAt(0).toUpperCase() + log.action_type.slice(1)}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-sm text-gray-900">{log.description}</div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-sm text-gray-600">
                                                {log.model_type ? log.model_type.split('\\').pop() : '-'}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-2 text-sm text-gray-600">
                                                <Clock className="h-4 w-4" />
                                                {formatDate(log.created_at)}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {activityLogs.links && activityLogs.links.length > 3 && (
                    <div className="mt-4 flex items-center justify-between">
                        <div className="text-sm text-gray-500">
                            Showing {activityLogs.from} to {activityLogs.to} of {activityLogs.total} results
                        </div>
                        <div className="flex gap-2">
                            {activityLogs.links.map((link, index) => (
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

