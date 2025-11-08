import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Bell, CheckCircle, XCircle, Trash2 } from 'lucide-react';

export default function NotificationsIndex({ notifications, unreadCount, filters }) {
    const handleMarkAsRead = (id) => {
        router.post(`/notifications/${id}/read`, {}, {
            preserveScroll: true,
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this notification?')) {
            router.delete(`/notifications/${id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <SectionLayout sectionName="Compliance">
            <Head title="Notifications" />
            <div className="max-w-4xl mx-auto">
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Notifications</h1>
                        <p className="text-gray-500 mt-1">
                            {unreadCount > 0 ? `${unreadCount} unread notification${unreadCount > 1 ? 's' : ''}` : 'All caught up!'}
                        </p>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                value={filters?.is_read || ''}
                                onChange={(e) => router.visit(`/notifications?is_read=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All</option>
                                <option value="false">Unread</option>
                                <option value="true">Read</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Type</label>
                            <select
                                value={filters?.type || ''}
                                onChange={(e) => router.visit(`/notifications?type=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Types</option>
                                <option value="sale">Sale</option>
                                <option value="invoice">Invoice</option>
                                <option value="payment">Payment</option>
                                <option value="leave">Leave</option>
                                <option value="system">System</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Notifications List */}
                <div className="space-y-3">
                    {notifications.data.length === 0 ? (
                        <div className="bg-white border border-gray-200 rounded-lg p-12 text-center">
                            <Bell className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <p className="text-gray-500">No notifications found.</p>
                        </div>
                    ) : (
                        notifications.data.map((notification) => (
                            <div
                                key={notification.id}
                                className={`bg-white border rounded-lg p-4 ${
                                    !notification.is_read ? 'border-teal-300 bg-teal-50' : 'border-gray-200'
                                }`}
                            >
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2 mb-1">
                                            {!notification.is_read && (
                                                <span className="h-2 w-2 bg-teal-500 rounded-full"></span>
                                            )}
                                            <h3 className="font-semibold text-gray-900">{notification.title}</h3>
                                        </div>
                                        <p className="text-gray-600 text-sm mb-2">{notification.message}</p>
                                        <div className="flex items-center gap-4 text-xs text-gray-500">
                                            <span>{new Date(notification.created_at).toLocaleString()}</span>
                                            {notification.action_url && (
                                                <Link href={notification.action_url} className="text-teal-600 hover:text-teal-700">
                                                    View →
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 ml-4">
                                        {!notification.is_read && (
                                            <button
                                                onClick={() => handleMarkAsRead(notification.id)}
                                                className="text-gray-400 hover:text-green-600"
                                                title="Mark as read"
                                            >
                                                <CheckCircle className="h-4 w-4" />
                                            </button>
                                        )}
                                        <button
                                            onClick={() => handleDelete(notification.id)}
                                            className="text-gray-400 hover:text-red-600"
                                            title="Delete"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>

                {/* Pagination */}
                {notifications.links && notifications.links.length > 3 && (
                    <div className="mt-4 flex items-center justify-between">
                        <div className="text-sm text-gray-500">
                            Showing {notifications.from} to {notifications.to} of {notifications.total} results
                        </div>
                        <div className="flex gap-2">
                            {notifications.links.map((link, index) => (
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

