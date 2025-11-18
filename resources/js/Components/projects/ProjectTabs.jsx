import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { 
    LayoutDashboard, 
    CheckSquare, 
    Target, 
    Users, 
    Clock, 
    DollarSign, 
    FileText, 
    Activity 
} from 'lucide-react';

export function ProjectTabs({ projectId, activeTab = 'overview' }) {
    const tabs = [
        { id: 'overview', name: 'Overview', icon: LayoutDashboard, href: `/projects/${projectId}` },
        { id: 'tasks', name: 'Tasks', icon: CheckSquare, href: `/projects/${projectId}?tab=tasks` },
        { id: 'milestones', name: 'Milestones', icon: Target, href: `/projects/${projectId}?tab=milestones` },
        { id: 'team', name: 'Team', icon: Users, href: `/projects/${projectId}?tab=team` },
        { id: 'time', name: 'Time', icon: Clock, href: `/projects/${projectId}?tab=time` },
        { id: 'budget', name: 'Budget', icon: DollarSign, href: `/projects/${projectId}?tab=budget` },
        { id: 'files', name: 'Files', icon: FileText, href: `/projects/${projectId}?tab=files` },
        { id: 'activity', name: 'Activity', icon: Activity, href: `/projects/${projectId}?tab=activity` },
    ];

    return (
        <div className="border-b border-gray-200 mb-6">
            <nav className="-mb-px flex space-x-8 overflow-x-auto">
                {tabs.map((tab) => {
                    const Icon = tab.icon;
                    const isActive = activeTab === tab.id;
                    
                    return (
                        <Link
                            key={tab.id}
                            href={tab.href}
                            className={`
                                flex items-center gap-2 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                ${isActive
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

