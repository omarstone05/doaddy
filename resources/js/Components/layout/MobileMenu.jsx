import React from 'react';
import { Link, router } from '@inertiajs/react';
import { X, Search, Bell, Settings, LogOut, User, Building2, Ticket } from 'lucide-react';
import { cn } from '@/lib/utils';
import { navigation } from '@/Layouts/navigation';

export function MobileMenu({ isOpen, onClose, auth, unreadNotificationCount, onNotificationClick, onLogout }) {
  if (!isOpen) return null;

  const isActive = (href) => {
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
    const normalizedPath = currentPath.replace(/\/$/, '') || '/';
    const normalizedHref = href.replace(/\/$/, '') || '/';
    
    if (normalizedHref === '/dashboard') {
      return normalizedPath === '/dashboard' || normalizedPath === '/' || normalizedPath === '';
    }
    return normalizedPath === normalizedHref || normalizedPath.startsWith(normalizedHref + '/');
  };

  return (
    <>
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] transition-opacity duration-300"
        onClick={onClose}
      />

      {/* Fullscreen Glass Menu */}
      <div className="fixed inset-0 z-[101] overflow-y-auto">
        <div className="min-h-full bg-gradient-to-br from-white/95 via-white/90 to-gray-50/95 backdrop-blur-xl">
          {/* Header */}
          <div className="sticky top-0 z-10 bg-white/80 backdrop-blur-md border-b border-white/20 shadow-sm">
            <div className="flex items-center justify-between px-6 py-4">
              <div className="flex items-center gap-3">
                {auth?.user?.avatar ? (
                  <img 
                    src={auth.user.avatar} 
                    alt={auth.user.name} 
                    className="h-10 w-10 rounded-full object-cover ring-2 ring-white/50"
                  />
                ) : (
                  <div className="h-10 w-10 rounded-full bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center text-white font-semibold ring-2 ring-white/50">
                    {auth?.user?.name?.charAt(0).toUpperCase() || 'A'}
                  </div>
                )}
                <div>
                  <p className="font-semibold text-gray-900">{auth?.user?.name}</p>
                  <p className="text-xs text-gray-500">{auth?.user?.organization?.name || 'Organization'}</p>
                </div>
              </div>
              <button
                onClick={onClose}
                className="p-2 rounded-full bg-white/80 hover:bg-white/90 backdrop-blur-sm transition-all shadow-sm"
                aria-label="Close menu"
              >
                <X className="h-6 w-6 text-gray-700" />
              </button>
            </div>
          </div>

          {/* Navigation Sections */}
          <div className="px-6 py-6 space-y-6">
            {navigation.map((section) => (
              <div key={section.name} className="space-y-3">
                <div className="flex items-center gap-2 px-3 py-2">
                  <section.icon className="h-5 w-5 text-teal-600" />
                  <h3 className="text-sm font-semibold text-gray-700 uppercase tracking-wider">
                    {section.name}
                  </h3>
                </div>
                <div className="space-y-1">
                  {section.items.map((item) => {
                    const active = isActive(item.href);
                    const Icon = item.icon;
                    
                    return (
                      <Link
                        key={item.href}
                        href={item.href}
                        onClick={onClose}
                        className={cn(
                          "flex items-center gap-4 px-4 py-3 rounded-xl transition-all duration-200",
                          active
                            ? "bg-gradient-to-r from-teal-500/20 to-teal-600/20 text-teal-700 shadow-sm border border-teal-200/50"
                            : "text-gray-700 hover:bg-white/60 hover:shadow-sm"
                        )}
                      >
                        <div className={cn(
                          "p-2 rounded-lg",
                          active ? "bg-teal-500/20" : "bg-gray-100/80"
                        )}>
                          <Icon className={cn(
                            "h-5 w-5",
                            active ? "text-teal-600" : "text-gray-600"
                          )} />
                        </div>
                        <span className={cn(
                          "font-medium flex-1",
                          active ? "text-teal-900" : "text-gray-800"
                        )}>
                          {item.name}
                        </span>
                        {active && (
                          <div className="h-2 w-2 rounded-full bg-teal-500"></div>
                        )}
                      </Link>
                    );
                  })}
                </div>
              </div>
            ))}
          </div>

          {/* Quick Actions */}
          <div className="px-6 py-6 border-t border-white/20">
            <div className="space-y-2">
              <button
                onClick={() => {
                  onNotificationClick();
                  onClose();
                }}
                className="flex items-center gap-4 w-full px-4 py-3 rounded-xl bg-white/60 hover:bg-white/80 transition-all text-gray-700 hover:shadow-sm"
              >
                <div className="p-2 rounded-lg bg-gray-100/80">
                  <Bell className="h-5 w-5 text-gray-600" />
                </div>
                <span className="font-medium text-gray-800 flex-1 text-left">Notifications</span>
                {unreadNotificationCount > 0 && (
                  <span className="px-2 py-1 bg-red-500 text-white text-xs rounded-full font-semibold">
                    {unreadNotificationCount > 9 ? '9+' : unreadNotificationCount}
                  </span>
                )}
              </button>

              <Link
                href="/support/tickets"
                onClick={onClose}
                className="flex items-center gap-4 w-full px-4 py-3 rounded-xl bg-white/60 hover:bg-white/80 transition-all text-gray-700 hover:shadow-sm"
              >
                <div className="p-2 rounded-lg bg-gray-100/80">
                  <Ticket className="h-5 w-5 text-gray-600" />
                </div>
                <span className="font-medium text-gray-800 flex-1 text-left">Support Tickets</span>
              </Link>

              <Link
                href="/settings"
                onClick={onClose}
                className="flex items-center gap-4 w-full px-4 py-3 rounded-xl bg-white/60 hover:bg-white/80 transition-all text-gray-700 hover:shadow-sm"
              >
                <div className="p-2 rounded-lg bg-gray-100/80">
                  <Settings className="h-5 w-5 text-gray-600" />
                </div>
                <span className="font-medium text-gray-800 flex-1 text-left">Settings</span>
              </Link>

              {/* Organization Switcher */}
              {auth?.user?.organizations && auth.user.organizations.length > 1 && (
                <div className="pt-4 border-t border-white/20 mt-4">
                  <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 mb-3">
                    Switch Organization
                  </p>
                  <div className="space-y-1">
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
                          onClose();
                        }}
                        className={cn(
                          "flex items-center gap-4 w-full px-4 py-3 rounded-xl transition-all text-left",
                          org.is_current
                            ? "bg-gradient-to-r from-teal-500/20 to-teal-600/20 text-teal-700 border border-teal-200/50"
                            : "bg-white/60 hover:bg-white/80 text-gray-700"
                        )}
                      >
                        <div className={cn(
                          "p-2 rounded-lg",
                          org.is_current ? "bg-teal-500/20" : "bg-gray-100/80"
                        )}>
                          <Building2 className={cn(
                            "h-5 w-5",
                            org.is_current ? "text-teal-600" : "text-gray-600"
                          )} />
                        </div>
                        <div className="flex-1">
                          <p className={cn(
                            "font-medium",
                            org.is_current ? "text-teal-900" : "text-gray-800"
                          )}>
                            {org.name}
                          </p>
                          {org.role && (
                            <p className="text-xs text-gray-500 capitalize">{org.role}</p>
                          )}
                        </div>
                        {org.is_current && (
                          <div className="h-2 w-2 rounded-full bg-teal-500"></div>
                        )}
                      </button>
                    ))}
                  </div>
                </div>
              )}

              <button
                onClick={() => {
                  onLogout();
                  onClose();
                }}
                className="flex items-center gap-4 w-full px-4 py-3 rounded-xl bg-red-50/80 hover:bg-red-100/80 transition-all text-red-700 hover:shadow-sm mt-4"
              >
                <div className="p-2 rounded-lg bg-red-100/80">
                  <LogOut className="h-5 w-5 text-red-600" />
                </div>
                <span className="font-medium text-red-800 flex-1 text-left">Logout</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}

