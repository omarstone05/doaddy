import React, { useState, useEffect, useRef } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Search, Bell, ChevronDown, Settings, CheckCircle, XCircle, Info, AlertTriangle, X, Ticket } from 'lucide-react';
import { cn } from '@/lib/utils';
import axios from 'axios';

// Navigation items - main sections only for pill navigation
// Note: dashboard is PNG, others are SVG
const navItems = [
  { name: 'Dashboard', icon: 'dashboard', extension: 'png', href: '/dashboard' },
  { name: 'Money', icon: 'money', extension: 'svg', href: '/money' },
  { name: 'Sales', icon: 'sales', extension: 'svg', href: '/sales' },
  { name: 'People', icon: 'people', extension: 'svg', href: '/people' },
  { name: 'Inventory', icon: 'inventory', extension: 'svg', href: '/inventory' },
  { name: 'Decisions', icon: 'decisions', extension: 'svg', href: '/decisions' },
  { name: 'Compliance', icon: 'compliance', extension: 'svg', href: '/compliance' },
];

export function Navigation() {
  const { auth, url, unreadNotificationCount: initialUnreadCount } = usePage().props;
  // Inertia's url prop is the path without leading slash (e.g., "dashboard" not "/dashboard")
  // Fallback to window.location.pathname which includes leading slash
  const pathFromInertia = url || '';
  const pathFromWindow = typeof window !== 'undefined' ? window.location.pathname : '';
  const currentPath = pathFromWindow || (pathFromInertia ? '/' + pathFromInertia : '');
  const normalizedPath = currentPath.replace(/\/$/, '') || '/';

  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(initialUnreadCount || 0);
  const [showNotifications, setShowNotifications] = useState(false);
  const notificationRef = useRef(null);

  // Fetch notifications
  const fetchNotifications = async () => {
    try {
      const response = await axios.get('/api/notifications/recent');
      setNotifications(response.data.notifications);
      setUnreadCount(response.data.unread_count);
    } catch (error) {
      console.error('Failed to fetch notifications:', error);
    }
  };

  // Fetch notifications on mount and when dropdown opens
  useEffect(() => {
    if (showNotifications) {
      fetchNotifications();
    }
  }, [showNotifications]);

  // Poll for new notifications every 30 seconds
  useEffect(() => {
    const interval = setInterval(() => {
      fetchNotifications();
    }, 30000);

    return () => clearInterval(interval);
  }, []);

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (notificationRef.current && !notificationRef.current.contains(event.target)) {
        setShowNotifications(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleLogout = () => {
    router.post('/logout');
  };

  const handleMarkAsRead = async (id) => {
    try {
      await router.post(`/notifications/${id}/read`, {}, {
        preserveScroll: true,
        onSuccess: () => {
          fetchNotifications();
        },
      });
    } catch (error) {
      console.error('Failed to mark notification as read:', error);
    }
  };

  const handleMarkAllAsRead = async () => {
    try {
      await axios.post('/notifications/mark-all-read');
      fetchNotifications();
    } catch (error) {
      console.error('Failed to mark all as read:', error);
    }
  };

  const handleNotificationClick = (notification) => {
    if (!notification.is_read) {
      handleMarkAsRead(notification.id);
    }
    if (notification.action_url) {
      router.visit(notification.action_url);
      setShowNotifications(false);
    }
  };

  const getNotificationIcon = (type) => {
    switch (type) {
      case 'success':
        return <CheckCircle className="h-5 w-5 text-green-500" />;
      case 'error':
        return <XCircle className="h-5 w-5 text-red-500" />;
      case 'warning':
        return <AlertTriangle className="h-5 w-5 text-amber-500" />;
      case 'info':
      default:
        return <Info className="h-5 w-5 text-blue-500" />;
    }
  };

  const isActive = (href) => {
    const normalizedHref = href.replace(/\/$/, '') || '/';
    // Special handling for dashboard - it should be active on /dashboard or root
    if (normalizedHref === '/dashboard') {
      return normalizedPath === '/dashboard' || normalizedPath === '/' || normalizedPath === '' || normalizedPath.startsWith('/dashboard');
    }
    return normalizedPath === normalizedHref || normalizedPath.startsWith(normalizedHref + '/');
  };

  return (
    <header className="sticky top-0 z-50 pt-6 px-6 bg-gray-50">
      <div className="max-w-[1600px] mx-auto">
        <div className="flex items-center justify-between gap-4">
          {/* Logo */}
          <Link href="/dashboard" className="flex items-center gap-2 flex-shrink-0">
            <img 
              src="/assets/logos/size.png" 
              alt="Addy" 
              className="h-12 object-contain"
              onError={(e) => {
                console.error('Logo failed to load:', e.target.src);
              }}
            />
          </Link>
          
          {/* Floating Navigation Pills - Between Logo and Right Actions */}
          <div className="flex-1 flex justify-center">
            <div className="bg-white rounded-2xl shadow-lg border border-gray-200 px-5 py-3">
              <nav className="flex items-center gap-2">
                {navItems.map((item) => {
                  const active = isActive(item.href);
                  // Dashboard uses different icons for active/inactive states
                  const iconSrc = item.name === 'Dashboard' 
                    ? (active ? '/assets/icons/dashboard.png' : '/assets/icons/not-selected.png')
                    : `/assets/icons/${item.icon}.${item.extension}`;
                  
                  return (
                    <Link
                      key={item.name}
                      href={item.href}
                      className={cn(
                        'flex items-center gap-2 px-4 py-2 rounded-full font-medium text-sm transition-all',
                        active
                          ? 'bg-teal-500 text-white shadow-sm'
                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                      )}
                    >
                    <img 
                      src={iconSrc}
                      alt={item.name}
                      className={cn(
                        "h-5 w-5 object-contain transition-colors",
                        active ? "brightness-0 invert" : ""
                      )}
                      style={active ? { filter: 'brightness(0) invert(1)' } : {}}
                      onError={(e) => {
                        // Fallback: try the other extension if one fails (for non-dashboard items)
                        if (item.name !== 'Dashboard') {
                          const currentExt = item.extension;
                          const altExt = currentExt === 'png' ? 'svg' : 'png';
                          if (e.target.src.includes(currentExt)) {
                            e.target.src = `/assets/icons/${item.icon}.${altExt}`;
                          } else {
                            e.target.style.display = 'none';
                          }
                        } else {
                          e.target.style.display = 'none';
                        }
                      }}
                    />
                      <span>{item.name}</span>
                    </Link>
                  );
                })}
              </nav>
            </div>
          </div>
          
          {/* Right Actions */}
          <div className="flex items-center gap-4 flex-shrink-0">
            <button 
              type="button"
              className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
              aria-label="Search"
            >
              <Search className="h-5 w-5" />
            </button>
            <div className="relative" ref={notificationRef}>
              <button 
                type="button"
                onClick={() => setShowNotifications(!showNotifications)}
                className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors relative"
                aria-label="Notifications"
              >
                <Bell className="h-5 w-5" />
                {unreadCount > 0 && (
                  <span className="absolute top-1 right-1 h-5 w-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-semibold">
                    {unreadCount > 9 ? '9+' : unreadCount}
                  </span>
                )}
              </button>

              {/* Notifications Dropdown */}
              {showNotifications && (
                <div className="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-50 max-h-[600px] flex flex-col">
                  {/* Header */}
                  <div className="flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 className="text-lg font-semibold text-gray-900">Notifications</h3>
                    <div className="flex items-center gap-2">
                      {unreadCount > 0 && (
                        <button
                          onClick={handleMarkAllAsRead}
                          className="text-sm text-teal-600 hover:text-teal-700 font-medium"
                        >
                          Mark all read
                        </button>
                      )}
                      <Link
                        href="/notifications"
                        className="text-sm text-teal-600 hover:text-teal-700 font-medium"
                        onClick={() => setShowNotifications(false)}
                      >
                        View all
                      </Link>
                    </div>
                  </div>

                  {/* Notifications List */}
                  <div className="overflow-y-auto flex-1">
                    {notifications.length === 0 ? (
                      <div className="p-8 text-center text-gray-500">
                        <Bell className="h-12 w-12 mx-auto mb-3 text-gray-300" />
                        <p className="text-sm">No notifications</p>
                        <p className="text-xs mt-1">You're all caught up!</p>
                      </div>
                    ) : (
                      <div className="divide-y divide-gray-100">
                        {notifications.map((notification) => (
                          <div
                            key={notification.id}
                            onClick={() => handleNotificationClick(notification)}
                            className={cn(
                              "p-4 hover:bg-gray-50 cursor-pointer transition-colors",
                              !notification.is_read && "bg-teal-50/50"
                            )}
                          >
                            <div className="flex items-start gap-3">
                              <div className="flex-shrink-0 mt-0.5">
                                {getNotificationIcon(notification.type)}
                              </div>
                              <div className="flex-1 min-w-0">
                                <div className="flex items-start justify-between gap-2">
                                  <div className="flex-1">
                                    <p className={cn(
                                      "text-sm font-medium",
                                      !notification.is_read ? "text-gray-900" : "text-gray-700"
                                    )}>
                                      {notification.title}
                                    </p>
                                    <p className="text-sm text-gray-600 mt-1 line-clamp-2">
                                      {notification.message}
                                    </p>
                                    <p className="text-xs text-gray-400 mt-2">
                                      {notification.created_at}
                                    </p>
                                  </div>
                                  {!notification.is_read && (
                                    <div className="h-2 w-2 bg-teal-500 rounded-full flex-shrink-0 mt-1"></div>
                                  )}
                                </div>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              )}
            </div>
            <div className="relative group">
              <button 
                type="button"
                className="flex items-center gap-2 hover:bg-gray-50 rounded-full p-1 pr-3 transition-colors"
                aria-label="User menu"
              >
                {auth?.user?.avatar ? (
                  <img 
                    src={auth.user.avatar} 
                    alt={auth.user.name} 
                    className="h-8 w-8 rounded-full object-cover"
                  />
                ) : (
                  <div className="h-8 w-8 rounded-full bg-teal-500 flex items-center justify-center text-white font-semibold text-sm">
                    {auth?.user?.name?.charAt(0).toUpperCase() || 'A'}
                  </div>
                )}
                <ChevronDown className="h-4 w-4 text-gray-500" />
              </button>
              
              {/* Dropdown Menu */}
              <div className={`absolute right-0 mt-2 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 ${
                auth?.user?.organizations && auth.user.organizations.length > 1 ? 'w-64' : 'w-48'
              }`}>
                <div className="py-2">
                  <div className="px-4 py-2 border-b border-gray-200">
                    <p className="text-sm font-medium text-gray-900">{auth?.user?.name}</p>
                    <p className="text-xs text-gray-500 truncate">
                      {auth?.user?.organization?.name || 'Organization'}
                    </p>
                  </div>
                  
                  {/* Organization Switcher */}
                  {auth?.user?.organizations && auth.user.organizations.length > 1 && (
                    <div className="px-4 py-2 border-b border-gray-200">
                      <p className="text-xs font-medium text-gray-500 uppercase mb-2">Switch Organization</p>
                      <div className="space-y-1 max-h-48 overflow-y-auto">
                        {auth.user.organizations.map((org) => (
                          <button
                            key={org.id}
                            onClick={() => {
                              router.post(`/organizations/${org.id}/switch`, {}, {
                                preserveScroll: true,
                                onSuccess: () => {
                                  window.location.reload();
                                },
                              });
                            }}
                            className={`w-full text-left px-3 py-1.5 text-sm rounded-md transition-colors ${
                              org.is_current
                                ? 'bg-teal-50 text-teal-700 font-medium'
                                : 'text-gray-700 hover:bg-gray-50'
                            }`}
                          >
                            <div className="flex items-center justify-between">
                              <span className="truncate">{org.name}</span>
                              {org.is_current && (
                                <span className="ml-2 text-teal-600">✓</span>
                              )}
                            </div>
                            {org.role && (
                              <span className="text-xs text-gray-500 capitalize">{org.role}</span>
                            )}
                          </button>
                        ))}
                      </div>
                    </div>
                  )}
                  
                  <Link
                    href="/support/tickets"
                    className="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                  >
                    <Ticket className="h-4 w-4" />
                    Support Tickets
                  </Link>
                  <Link
                    href="/settings"
                    className="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                  >
                    <Settings className="h-4 w-4" />
                    Settings
                  </Link>
                  <button
                    onClick={handleLogout}
                    className="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                  >
                    Logout
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
}
