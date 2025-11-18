import React, { useState, useEffect } from 'react';
import { X, Loader2, ExternalLink } from 'lucide-react';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import { Badge } from '@/Components/ui/Badge';
import { Progress } from '@/Components/ui/Progress';

export function ProjectDetailsModal({ isOpen, onClose, title, type, filter = {} }) {
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (isOpen) {
            loadData();
        } else {
            setItems([]);
            setError(null);
        }
    }, [isOpen, type, filter]);

    const loadData = async () => {
        setLoading(true);
        setError(null);
        try {
            let response;
            switch (type) {
                case 'all_projects':
                    response = await axios.get('/api/projects/list', { params: {} });
                    setItems(response.data.projects || []);
                    break;
                case 'active_projects':
                    response = await axios.get('/api/projects/list', { params: { status: 'active' } });
                    setItems(response.data.projects || []);
                    break;
                case 'completed_projects':
                    response = await axios.get('/api/projects/list', { params: { status: 'completed' } });
                    setItems(response.data.projects || []);
                    break;
                case 'overdue_projects':
                    response = await axios.get('/api/projects/list', { params: { overdue: true } });
                    setItems(response.data.projects || []);
                    break;
                case 'tasks':
                    response = await axios.get('/api/tasks/all');
                    setItems(response.data.tasks || []);
                    break;
                case 'budget':
                    response = await axios.get('/api/projects/budget-details');
                    setItems(response.data.projects || []);
                    break;
                case 'time':
                    response = await axios.get('/api/projects/time-details');
                    setItems(response.data.projects || []);
                    break;
                default:
                    setItems([]);
            }
        } catch (err) {
            console.error('Error loading data:', err);
            setError('Failed to load data. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const getStatusBadge = (status) => {
        const badges = {
            planning: 'bg-gray-100 text-gray-700',
            active: 'bg-green-100 text-green-700',
            on_hold: 'bg-yellow-100 text-yellow-700',
            completed: 'bg-blue-100 text-blue-700',
            cancelled: 'bg-red-100 text-red-700',
            todo: 'bg-gray-100 text-gray-700',
            in_progress: 'bg-blue-100 text-blue-700',
            review: 'bg-yellow-100 text-yellow-700',
            done: 'bg-green-100 text-green-700',
            blocked: 'bg-red-100 text-red-700',
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

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 0,
        }).format(amount || 0);
    };

    const formatDate = (date) => {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('en-ZM', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" onClick={onClose}>
            <div 
                className="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col"
                onClick={(e) => e.stopPropagation()}
            >
                {/* Header */}
                <div className="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 className="text-2xl font-semibold text-gray-900">{title}</h2>
                    <button
                        onClick={onClose}
                        className="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Content */}
                <div className="flex-1 overflow-y-auto p-6">
                    {loading ? (
                        <div className="flex items-center justify-center py-12">
                            <Loader2 className="w-8 h-8 animate-spin text-blue-500" />
                            <span className="ml-3 text-gray-600">Loading...</span>
                        </div>
                    ) : error ? (
                        <div className="text-center py-12">
                            <p className="text-red-600">{error}</p>
                        </div>
                    ) : items.length === 0 ? (
                        <div className="text-center py-12">
                            <p className="text-gray-500">No items found.</p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {(type === 'all_projects' || type === 'active_projects' || type === 'completed_projects' || type === 'overdue_projects' || type === 'budget' || type === 'time') ? (
                                // Project List View
                                items.map((project) => (
                                    <div
                                        key={project.id}
                                        className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                                    >
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-3 mb-2">
                                                    <Link
                                                        href={`/projects/${project.id}`}
                                                        className="text-lg font-semibold text-gray-900 hover:text-blue-600 flex items-center gap-2"
                                                    >
                                                        {project.name}
                                                        <ExternalLink className="w-4 h-4" />
                                                    </Link>
                                                </div>
                                                {project.description && (
                                                    <p className="text-sm text-gray-600 mb-3 line-clamp-2">
                                                        {project.description}
                                                    </p>
                                                )}
                                                <div className="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium">Status:</span>
                                                        <Badge className={getStatusBadge(project.status)}>
                                                            {project.status?.replace('_', ' ')}
                                                        </Badge>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium">Priority:</span>
                                                        <Badge className={getPriorityBadge(project.priority)}>
                                                            {project.priority}
                                                        </Badge>
                                                    </div>
                                                    {project.project_manager && (
                                                        <div>
                                                            <span className="font-medium">Manager:</span>{' '}
                                                            {project.project_manager.name}
                                                        </div>
                                                    )}
                                                    {project.target_completion_date && (
                                                        <div>
                                                            <span className="font-medium">Due:</span>{' '}
                                                            {formatDate(project.target_completion_date)}
                                                        </div>
                                                    )}
                                                    {project.progress_percentage !== undefined && (
                                                        <div className="flex-1 min-w-[200px]">
                                                            <div className="flex justify-between text-xs mb-1">
                                                                <span>Progress</span>
                                                                <span>{project.progress_percentage}%</span>
                                                            </div>
                                                            <Progress value={project.progress_percentage} className="h-2" />
                                                        </div>
                                                    )}
                                                </div>
                                                {(type === 'budget' || project.budget) && (
                                                    <div className="mt-3 pt-3 border-t border-gray-200 flex gap-6 text-sm">
                                                        {project.budget && (
                                                            <div>
                                                                <span className="text-gray-600">Budget:</span>{' '}
                                                                <span className="font-medium">{formatCurrency(project.budget)}</span>
                                                            </div>
                                                        )}
                                                        {project.spent !== undefined && (
                                                            <div>
                                                                <span className="text-gray-600">Spent:</span>{' '}
                                                                <span className="font-medium">{formatCurrency(project.spent)}</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                                {type === 'time' && project.total_time && (
                                                    <div className="mt-3 pt-3 border-t border-gray-200 text-sm">
                                                        <span className="text-gray-600">Time Logged:</span>{' '}
                                                        <span className="font-medium">{project.total_time}h</span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))
                            ) : type === 'tasks' ? (
                                // Task List View
                                items.map((task) => (
                                    <div
                                        key={task.id}
                                        className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                                    >
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-3 mb-2">
                                                    <Link
                                                        href={`/projects/${task.project_id}/tasks/${task.id}`}
                                                        className="text-lg font-semibold text-gray-900 hover:text-blue-600 flex items-center gap-2"
                                                    >
                                                        {task.title}
                                                        <ExternalLink className="w-4 h-4" />
                                                    </Link>
                                                </div>
                                                {task.description && (
                                                    <p className="text-sm text-gray-600 mb-3 line-clamp-2">
                                                        {task.description}
                                                    </p>
                                                )}
                                                <div className="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium">Status:</span>
                                                        <Badge className={getStatusBadge(task.status)}>
                                                            {task.status?.replace('_', ' ')}
                                                        </Badge>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium">Priority:</span>
                                                        <Badge className={getPriorityBadge(task.priority)}>
                                                            {task.priority}
                                                        </Badge>
                                                    </div>
                                                    {task.project && (
                                                        <div>
                                                            <span className="font-medium">Project:</span>{' '}
                                                            <Link
                                                                href={`/projects/${task.project_id}`}
                                                                className="text-blue-600 hover:underline"
                                                            >
                                                                {task.project.name}
                                                            </Link>
                                                        </div>
                                                    )}
                                                    {task.assigned_to && (
                                                        <div>
                                                            <span className="font-medium">Assigned to:</span>{' '}
                                                            {task.assigned_to.name}
                                                        </div>
                                                    )}
                                                    {task.due_date && (
                                                        <div>
                                                            <span className="font-medium">Due:</span>{' '}
                                                            {formatDate(task.due_date)}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            ) : null}
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="border-t border-gray-200 p-4 flex justify-end">
                    <button
                        onClick={onClose}
                        className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    );
}

