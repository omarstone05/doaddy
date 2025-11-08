import React from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Search, Bell, ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';

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
  const { auth, url } = usePage().props;
  // Inertia's url prop is the path without leading slash (e.g., "dashboard" not "/dashboard")
  // Fallback to window.location.pathname which includes leading slash
  const pathFromInertia = url || '';
  const pathFromWindow = typeof window !== 'undefined' ? window.location.pathname : '';
  const currentPath = pathFromWindow || (pathFromInertia ? '/' + pathFromInertia : '');
  const normalizedPath = currentPath.replace(/\/$/, '') || '/';

  const handleLogout = () => {
    router.post('/logout');
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
            <button 
              type="button"
              className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors relative"
              aria-label="Notifications"
            >
              <Bell className="h-5 w-5" />
              <span className="absolute top-1 right-1 h-2 w-2 bg-teal-500 rounded-full"></span>
            </button>
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
              <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                <div className="py-2">
                  <div className="px-4 py-2 border-b border-gray-200">
                    <p className="text-sm font-medium text-gray-900">{auth?.user?.name}</p>
                    <p className="text-xs text-gray-500 truncate">
                      {auth?.user?.organization?.name || 'Organization'}
                    </p>
                  </div>
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
