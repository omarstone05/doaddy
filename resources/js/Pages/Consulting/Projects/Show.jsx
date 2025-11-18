import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    ArrowLeft, 
    Edit, 
    Calendar, 
    Users, 
    DollarSign, 
    TrendingUp,
    CheckSquare,
    FileText,
    Clock,
    AlertTriangle,
    Package,
    MessageSquare
} from 'lucide-react';

export default function Show({ auth, project }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 0,
        }).format(amount || 0);
    };

    const getStatusColor = (status) => {
        const colors = {
            proposed: 'bg-gray-100 text-gray-700',
            active: 'bg-green-100 text-green-700',
            paused: 'bg-yellow-100 text-yellow-700',
            complete: 'bg-blue-100 text-blue-700',
            cancelled: 'bg-red-100 text-red-700',
        };
        return colors[status] || colors.proposed;
    };

    const stats = [
        {
            label: 'Tasks',
            value: project.tasks?.length || 0,
            icon: CheckSquare,
            color: 'text-blue-600',
            bgColor: 'bg-blue-100',
        },
        {
            label: 'Deliverables',
            value: project.deliverables?.length || 0,
            icon: FileText,
            color: 'text-green-600',
            bgColor: 'bg-green-100',
        },
        {
            label: 'Time Entries',
            value: project.time_entries?.length || 0,
            icon: Clock,
            color: 'text-purple-600',
            bgColor: 'bg-purple-100',
        },
        {
            label: 'Expenses',
            value: project.expenses?.length || 0,
            icon: DollarSign,
            color: 'text-red-600',
            bgColor: 'bg-red-100',
        },
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link
                            href={route('consulting.projects.index')}
                            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            <ArrowLeft size={20} />
                        </Link>
                        <div>
                            <h2 className="text-xl font-semibold leading-tight text-gray-800">
                                {project.name}
                            </h2>
                            {project.code && (
                                <p className="text-sm text-gray-500">#{project.code}</p>
                            )}
                        </div>
                    </div>
                    <Link
                        href={route('consulting.projects.edit', project.id)}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors"
                    >
                        <Edit size={18} />
                        Edit
                    </Link>
                </div>
            }
        >
            <Head title={project.name} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header Stats */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        {stats.map((stat, index) => {
                            const Icon = stat.icon;
                            return (
                                <div
                                    key={index}
                                    className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-xl p-4"
                                >
                                    <div className="flex items-center justify-between mb-2">
                                        <div className={`w-10 h-10 rounded-lg ${stat.bgColor} flex items-center justify-center`}>
                                            <Icon size={20} className={stat.color} />
                                        </div>
                                        <span className="text-2xl font-bold text-gray-900">
                                            {stat.value}
                                        </span>
                                    </div>
                                    <p className="text-sm text-gray-600">{stat.label}</p>
                                </div>
                            );
                        })}
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Project Overview */}
                            <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Overview</h3>
                                
                                {project.description && (
                                    <p className="text-gray-700 mb-6">{project.description}</p>
                                )}

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <div className="text-xs text-gray-500 mb-1">Status</div>
                                        <span className={`inline-block px-3 py-1 rounded-lg text-sm font-medium ${getStatusColor(project.status)}`}>
                                            {project.status}
                                        </span>
                                    </div>
                                    <div>
                                        <div className="text-xs text-gray-500 mb-1">Progress</div>
                                        <div className="text-lg font-semibold text-gray-900">
                                            {project.progress_percentage}%
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-xs text-gray-500 mb-1">Budget</div>
                                        <div className="text-lg font-semibold text-gray-900">
                                            {formatCurrency(project.budget_total)}
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-xs text-gray-500 mb-1">Remaining</div>
                                        <div className="text-lg font-semibold text-gray-900">
                                            {formatCurrency(project.budget_remaining)}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Recent Tasks */}
                            <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="text-lg font-semibold text-gray-900">Recent Tasks</h3>
                                    <Link
                                        href={route('consulting.projects.tasks.index', project.id)}
                                        className="text-sm text-teal-600 hover:text-teal-700 font-medium"
                                    >
                                        View All
                                    </Link>
                                </div>
                                
                                {project.tasks && project.tasks.length > 0 ? (
                                    <div className="space-y-3">
                                        {project.tasks.slice(0, 5).map((task) => (
                                            <div
                                                key={task.id}
                                                className="flex items-center justify-between p-3 bg-white/50 rounded-lg hover:bg-white/80 transition-colors"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <CheckSquare size={18} className="text-gray-400" />
                                                    <div>
                                                        <p className="text-sm font-medium text-gray-900">
                                                            {task.title}
                                                        </p>
                                                        <p className="text-xs text-gray-500">
                                                            {task.status} • {task.priority}
                                                        </p>
                                                    </div>
                                                </div>
                                                {task.due_date && (
                                                    <span className="text-xs text-gray-500">
                                                        {new Date(task.due_date).toLocaleDateString()}
                                                    </span>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500 text-center py-8">
                                        No tasks yet. Create your first task to get started.
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Project Details */}
                            <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Details</h3>
                                
                                <div className="space-y-4">
                                    {project.start_date && (
                                        <div className="flex items-center gap-3">
                                            <Calendar size={18} className="text-gray-400" />
                                            <div>
                                                <div className="text-xs text-gray-500">Start Date</div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {new Date(project.start_date).toLocaleDateString()}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                    
                                    {project.end_date && (
                                        <div className="flex items-center gap-3">
                                            <Calendar size={18} className="text-gray-400" />
                                            <div>
                                                <div className="text-xs text-gray-500">End Date</div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {new Date(project.end_date).toLocaleDateString()}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {project.client_name && (
                                        <div className="flex items-center gap-3">
                                            <Users size={18} className="text-gray-400" />
                                            <div>
                                                <div className="text-xs text-gray-500">Client</div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {project.client_name}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    <div className="flex items-center gap-3">
                                        <TrendingUp size={18} className="text-gray-400" />
                                        <div>
                                            <div className="text-xs text-gray-500">Billing Model</div>
                                            <div className="text-sm font-medium text-gray-900 capitalize">
                                                {project.billing_model?.replace('_', ' ')}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Quick Actions */}
                            <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                                
                                <div className="space-y-2">
                                    <button
                                        onClick={() => router.visit(route('consulting.projects.tasks.index', project.id))}
                                        className="w-full text-left px-4 py-2 bg-white/60 hover:bg-white/90 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                                    >
                                        <CheckSquare size={16} />
                                        Manage Tasks
                                    </button>
                                    <button
                                        className="w-full text-left px-4 py-2 bg-white/60 hover:bg-white/90 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                                    >
                                        <Clock size={16} />
                                        Track Time
                                    </button>
                                    <button
                                        className="w-full text-left px-4 py-2 bg-white/60 hover:bg-white/90 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                                    >
                                        <DollarSign size={16} />
                                        Add Expense
                                    </button>
                                    <button
                                        className="w-full text-left px-4 py-2 bg-white/60 hover:bg-white/90 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                                    >
                                        <MessageSquare size={16} />
                                        View Comments
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

