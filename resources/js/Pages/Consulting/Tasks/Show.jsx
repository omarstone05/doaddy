import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ArrowLeft, Edit, Calendar, User, Clock, CheckSquare } from 'lucide-react';

export default function Show({ auth, project, task }) {
    const getStatusColor = (status) => {
        const colors = {
            todo: 'bg-gray-100 text-gray-700',
            in_progress: 'bg-blue-100 text-blue-700',
            review: 'bg-yellow-100 text-yellow-700',
            done: 'bg-green-100 text-green-700',
            blocked: 'bg-red-100 text-red-700',
        };
        return colors[status] || colors.todo;
    };

    const getPriorityColor = (priority) => {
        const colors = {
            low: 'text-gray-500',
            medium: 'text-yellow-600',
            high: 'text-orange-600',
            urgent: 'text-red-600',
        };
        return colors[priority] || colors.medium;
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link
                            href={route('consulting.projects.tasks.index', project.id)}
                            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            <ArrowLeft size={20} />
                        </Link>
                        <div>
                            <h2 className="text-xl font-semibold leading-tight text-gray-800">
                                {task.title}
                            </h2>
                            <Link
                                href={route('consulting.projects.show', project.id)}
                                className="text-sm text-gray-500 hover:text-teal-600"
                            >
                                {project.name}
                            </Link>
                        </div>
                    </div>
                    <Link
                        href={route('consulting.projects.tasks.edit', [project.id, task.id])}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors"
                    >
                        <Edit size={18} />
                        Edit
                    </Link>
                </div>
            }
        >
            <Head title={task.title} />

            <div className="py-6">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Task Details */}
                            <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6">
                                <div className="flex items-center gap-3 mb-4">
                                    <CheckSquare 
                                        size={24} 
                                        className={task.status === 'done' ? 'text-green-600' : 'text-gray-400'} 
                                    />
                                    <div className="flex-1">
                                        <h3 className="text-xl font-semibold text-gray-900 mb-2">
                                            {task.title}
                                        </h3>
                                        <div className="flex items-center gap-2">
                                            <span className={`px-3 py-1 rounded-lg text-sm font-medium ${getStatusColor(task.status)}`}>
                                                {task.status.replace('_', ' ')}
                                            </span>
                                            <span className={`text-sm font-medium ${getPriorityColor(task.priority)}`}>
                                                {task.priority} priority
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {task.description && (
                                    <div className="mt-4">
                                        <h4 className="text-sm font-medium text-gray-700 mb-2">Description</h4>
                                        <p className="text-gray-600 whitespace-pre-wrap">{task.description}</p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Task Info */}
                            <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Details</h3>
                                
                                <div className="space-y-4">
                                    {task.due_date && (
                                        <div className="flex items-center gap-3">
                                            <Calendar size={18} className="text-gray-400" />
                                            <div>
                                                <div className="text-xs text-gray-500">Due Date</div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {new Date(task.due_date).toLocaleDateString()}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {task.estimated_hours && (
                                        <div className="flex items-center gap-3">
                                            <Clock size={18} className="text-gray-400" />
                                            <div>
                                                <div className="text-xs text-gray-500">Estimated Hours</div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {task.estimated_hours}h
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {task.assigned_to_name && (
                                        <div className="flex items-center gap-3">
                                            <User size={18} className="text-gray-400" />
                                            <div>
                                                <div className="text-xs text-gray-500">Assigned To</div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {task.assigned_to_name}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {task.created_at && (
                                        <div className="flex items-center gap-3">
                                            <Calendar size={18} className="text-gray-400" />
                                            <div>
                                                <div className="text-xs text-gray-500">Created</div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {new Date(task.created_at).toLocaleDateString()}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

