import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export function TabNavigation({ tabs, basePath }) {
  const { url } = usePage().props;
  const pathFromInertia = url || '';
  const pathFromWindow = typeof window !== 'undefined' ? window.location.pathname : '';
  const currentPath = pathFromWindow || (pathFromInertia ? '/' + pathFromInertia : '');
  const normalizedPath = currentPath.replace(/\/$/, '') || '/';

  return (
    <div className="border-b border-gray-200 bg-white">
      <div className="max-w-[1600px] mx-auto px-6">
        <nav className="flex space-x-8" aria-label="Tabs">
          {tabs.map((tab) => {
            const isActive = tab.href === normalizedPath || normalizedPath.startsWith(tab.href + '/');
            
            return (
              <Link
                key={tab.href}
                href={tab.href}
                className={cn(
                  'flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                  isActive
                    ? 'border-teal-500 text-teal-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                )}
              >
                {tab.icon && <tab.icon className="h-4 w-4" />}
                <span>{tab.name}</span>
              </Link>
            );
          })}
        </nav>
      </div>
    </div>
  );
}

