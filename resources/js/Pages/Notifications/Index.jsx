import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Bell, CheckCircle, Trash2, Filter, ExternalLink, Trophy, Star, Flame, Award } from 'lucide-react';

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

    const getNotificationIcon = (type) => {
        const icons = {
            gamification_xp: { icon: Star, color: 'text-yellow-500', bg: 'bg-yellow-100' },
            gamification_badge: { icon: Trophy, color: 'text-amber-500', bg: 'bg-amber-100' },
            gamification_streak: { icon: Flame, color: 'text-orange-500', bg: 'bg-orange-100' },
            sale: { icon: Bell, color: 'text-teal-500', bg: 'bg-teal-100' },
            invoice: { icon: Bell, color: 'text-blue-500', bg: 'bg-blue-100' },
            payment: { icon: Bell, color: 'text-green-500', bg: 'bg-green-100' },
            leave: { icon: Bell, color: 'text-purple-500', bg: 'bg-purple-100' },
            system: { icon: Bell, color: 'text-gray-500', bg: 'bg-gray-100' },
        };
        return icons[type] || { icon: Bell, color: 'text-gray-500', bg: 'bg-gray-100' };
    };

    const isGamificationNotification = (type) => {
        return type?.startsWith('gamification_');
    };

    return (
        <SectionLayout sectionName="Compliance">
            <Head title="Notifications" />
            <div className="max-w-4xl mx-auto">
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div className="flex items-center gap-4">
                        <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center relative">
                            <Bell className="h-7 w-7 text-white" />
                            {unreadCount > 0 && (
                                <span className="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full text-xs font-bold text-white flex items-center justify-center">
                                    {unreadCount > 9 ? '9+' : unreadCount}
                                </span>
                            )}
                        </div>
                        <div>
                            <h1 className="text-3xl font-black text-gray-900 tracking-tight">Notifications</h1>
                            <p className="text-gray-500 mt-1">
                                {unreadCount > 0 ? `${unreadCount} unread notification${unreadCount > 1 ? 's' : ''}` : 'All caught up!'}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Filters Card */}
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 mb-6">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
                            <Filter className="w-5 h-5 text-teal-600" />
                        </div>
                        <h3 className="text-sm font-bold text-gray-900">Filters</h3>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Status</label>
                            <select
                                value={filters?.is_read || ''}
                                onChange={(e) => router.visit(`/notifications?is_read=${e.target.value}`)}
                                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            >
                                <option value="">All</option>
                                <option value="false">Unread</option>
                                <option value="true">Read</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Type</label>
                            <select
                                value={filters?.type || ''}
                                onChange={(e) => router.visit(`/notifications?type=${e.target.value}`)}
                                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            >
                                <option value="">All Types</option>
                                <option value="gamification_xp">⭐ XP Earned</option>
                                <option value="gamification_badge">🏆 Badges</option>
                                <option value="gamification_streak">🔥 Streaks</option>
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
                        <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-12 border border-gray-200/50 text-center">
                            <div className="w-16 h-16 rounded-2xl bg-teal-100 flex items-center justify-center mx-auto mb-4">
                                <Bell className="h-8 w-8 text-teal-500" />
                            </div>
                            <h3 className="text-lg font-bold text-gray-900 mb-2">No notifications</h3>
                            <p className="text-gray-500">You're all caught up!</p>
                        </div>
                    ) : (
                        notifications.data.map((notification) => {
                            const iconConfig = getNotificationIcon(notification.type);
                            const IconComponent = iconConfig.icon;
                            const isGamification = isGamificationNotification(notification.type);

                            return (
                                <div
                                    key={notification.id}
                                    className={`bg-white/90 backdrop-blur-sm rounded-2xl p-5 border transition-all duration-300 ${
                                        !notification.is_read 
                                            ? isGamification 
                                                ? 'border-yellow-300 bg-gradient-to-r from-yellow-50/50 to-white/90 hover:shadow-lg'
                                                : 'border-teal-300 bg-gradient-to-r from-teal-50/50 to-white/90 hover:shadow-lg'
                                            : 'border-gray-200/50 hover:border-teal-200'
                                    }`}
                                >
                                    <div className="flex items-start gap-4">
                                        <div className={`w-10 h-10 rounded-xl ${iconConfig.bg} flex items-center justify-center flex-shrink-0`}>
                                            <IconComponent className={`h-5 w-5 ${iconConfig.color}`} />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2 mb-1">
                                                {!notification.is_read && (
                                                    <span className={`h-2 w-2 ${isGamification ? 'bg-yellow-500' : 'bg-teal-500'} rounded-full animate-pulse`}></span>
                                                )}
                                                <h3 className="font-bold text-gray-900 truncate">{notification.title}</h3>
                                                {isGamification && (
                                                    <span className="px-2 py-0.5 bg-gradient-to-r from-yellow-100 to-amber-100 text-amber-700 text-xs font-semibold rounded-full">
                                                        Gamification
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-gray-600 text-sm mb-3">{notification.message}</p>
                                            <div className="flex items-center gap-4 text-xs text-gray-500">
                                                <span className="font-medium">{new Date(notification.created_at).toLocaleString()}</span>
                                                {notification.action_url && (
                                                    <Link 
                                                        href={notification.action_url} 
                                                        className="flex items-center gap-1 text-teal-600 hover:text-teal-700 font-semibold"
                                                    >
                                                        View details
                                                        <ExternalLink className="h-3 w-3" />
                                                    </Link>
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-1 flex-shrink-0">
                                            {!notification.is_read && (
                                                <button
                                                    onClick={() => handleMarkAsRead(notification.id)}
                                                    className="p-2 rounded-lg text-teal-600 hover:bg-teal-100 transition-colors"
                                                    title="Mark as read"
                                                >
                                                    <CheckCircle className="h-4 w-4" />
                                                </button>
                                            )}
                                            <button
                                                onClick={() => handleDelete(notification.id)}
                                                className="p-2 rounded-lg text-red-500 hover:bg-red-100 transition-colors"
                                                title="Delete"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>

                {/* Pagination */}
                {notifications.links && notifications.links.length > 3 && (
                    <div className="mt-6 flex items-center justify-between">
                        <p className="text-sm text-gray-600">
                            Showing <span className="font-semibold">{notifications.from}</span> to <span className="font-semibold">{notifications.to}</span> of <span className="font-semibold">{notifications.total}</span>
                        </p>
                        <div className="flex gap-1">
                            {notifications.links.map((link, index) => (
                                <button
                                    key={index}
                                    onClick={() => link.url && router.visit(link.url)}
                                    disabled={!link.url}
                                    className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all ${
                                        link.active
                                            ? 'bg-teal-500 text-white shadow-sm'
                                            : link.url
                                            ? 'bg-white border border-gray-200 text-gray-700 hover:bg-teal-50 hover:border-teal-200'
                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'
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
