import React from 'react';
import { Link } from '@inertiajs/react';
import { FolderKanban, BarChart3 } from 'lucide-react';

/**
 * Modular Project Management Tabs Component
 * Shows tabs specific to Project Management section
 */
export function ProjectManagementTabs({ currentPath }) {
    // Remove query parameters for matching
    const pathWithoutQuery = currentPath.split('?')[0].replace(/\/$/, '') || '/';
    
    const tabs = [
        { 
            name: 'Overview', 
            href: '/projects/section', 
            icon: FolderKanban,
            matches: ['/projects/section']
        },
        { 
            name: 'All Projects', 
            href: '/projects', 
            icon: FolderKanban,
            matches: ['/projects']
        },
        { 
            name: 'Reports', 
            href: '/projects/reports', 
            icon: BarChart3,
            matches: ['/projects/reports']
        },
    ];

    const isActive = (tab) => {
        // More precise matching to avoid conflicts
        if (tab.matches.includes('/projects/section')) {
            return pathWithoutQuery === '/projects/section';
        }
        if (tab.matches.includes('/projects/reports')) {
            return pathWithoutQuery.startsWith('/projects/reports');
        }
        if (tab.matches.includes('/projects')) {
            // Match /projects (listing page) but not other project routes
            return pathWithoutQuery === '/projects';
        }
        return tab.matches.some(match => pathWithoutQuery === match || pathWithoutQuery.startsWith(match + '/'));
    };

    return (
        <div className="border-b border-gray-200 mb-8">
            <nav className="-mb-px flex space-x-8">
                {tabs.map((tab) => {
                    const Icon = tab.icon;
                    const active = isActive(tab);
                    
                    return (
                        <Link
                            key={tab.name}
                            href={tab.href}
                            className={`
                                flex items-center gap-2 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                ${active
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                }
                            `}
                        >
                            <Icon className="h-4 w-4" />
                            {tab.name}
                        </Link>
                    );
                })}
            </nav>
        </div>
    );
}

