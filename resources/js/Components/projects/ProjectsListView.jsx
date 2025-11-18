import React from 'react';
import { Link, router } from '@inertiajs/react';
import { ProjectCard } from './ProjectCard';
import { Card, CardContent } from '@/Components/ui/Card';

/**
 * Modular Projects List View Component
 * Can be used anywhere to display a list of projects
 * Supports both grid and table views
 */
export function ProjectsListView({ 
    projects, 
    viewMode = 'grid', 
    onDelete,
    showActions = true,
    className = '' 
}) {
    const getStatusBadge = (status) => {
        const badges = {
            planning: 'bg-gray-100 text-gray-700',
            active: 'bg-green-100 text-green-700',
            on_hold: 'bg-yellow-100 text-yellow-700',
            completed: 'bg-blue-100 text-blue-700',
            cancelled: 'bg-red-100 text-red-700',
        };
        return badges[status] || 'bg-gray-100 text-gray-700';
    };

    const getPriorityBadge = (priority) => {
        const badges = {
            low: 'bg-gray-100 text-gray-700',
            medium: 'bg-blue-100 text-blue-700',
            high: 'bg-orange-100 text-orange-700',
            urgent: 'bg-red-100 text-red-700',
        };
        return badges[priority] || 'bg-gray-100 text-gray-700';
    };

    if (!projects || projects.length === 0) {
        return (
            <Card>
                <CardContent className="pt-6 text-center py-12">
                    <p className="text-gray-500">No projects found.</p>
                </CardContent>
            </Card>
        );
    }

    if (viewMode === 'grid') {
        return (
            <div className={`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 ${className}`}>
                {projects.map((project) => (
                    <ProjectCard key={project.id} project={project} showActions={showActions} />
                ))}
            </div>
        );
    }

    // Table view
    return (
        <Card className={className}>
            <CardContent className="p-0">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Project Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Manager</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Priority</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Progress</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                {showActions && (
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Actions</th>
                                )}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {projects.map((project) => (
                                <tr key={project.id} className="hover:bg-gray-50 transition-colors">
                                    <td className="px-6 py-4">
                                        <Link href={`/projects/${project.id}`} className="block">
                                            <div className="font-medium text-gray-900 hover:text-blue-600">{project.name}</div>
                                            {project.description && (
                                                <div className="text-sm text-gray-500 mt-1 line-clamp-1">{project.description}</div>
                                            )}
                                        </Link>
                                    </td>
                                    <td className="px-6 py-4 text-sm text-gray-600">
                                        {project.project_manager?.name || '-'}
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${getPriorityBadge(project.priority)}`}>
                                            {project.priority.charAt(0).toUpperCase() + project.priority.slice(1)}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-2">
                                            <div className="flex-1 bg-gray-200 rounded-full h-2 min-w-[100px]">
                                                <div
                                                    className="bg-blue-500 h-2 rounded-full transition-all"
                                                    style={{ width: `${project.progress_percentage || 0}%` }}
                                                />
                                            </div>
                                            <span className="text-sm font-medium text-gray-900 whitespace-nowrap">{project.progress_percentage || 0}%</span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusBadge(project.status)}`}>
                                            {project.status.replace('_', ' ').charAt(0).toUpperCase() + project.status.replace('_', ' ').slice(1)}
                                        </span>
                                    </td>
                                    {showActions && (
                                        <td className="px-6 py-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link href={`/projects/${project.id}`}>
                                                    <button className="text-gray-400 hover:text-blue-600 transition-colors p-1 rounded" title="View">
                                                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </button>
                                                </Link>
                                                <Link href={`/projects/${project.id}/edit`}>
                                                    <button className="text-gray-400 hover:text-blue-600 transition-colors p-1 rounded" title="Edit">
                                                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>
                                                </Link>
                                                {onDelete && (
                                                    <button
                                                        onClick={() => onDelete(project.id)}
                                                        className="text-gray-400 hover:text-red-600 transition-colors p-1 rounded"
                                                        title="Delete"
                                                    >
                                                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}

