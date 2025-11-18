import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Plus, CheckSquare, Calendar, User, Filter, Search, ArrowLeft, Clock } from 'lucide-react';

export default function Index({ auth, project, tasks }) {
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [priorityFilter, setPriorityFilter] = useState('all');

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

    const filteredTasks = tasks.filter(task => {
        const matchesSearch = task.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                            (task.description && task.description.toLowerCase().includes(searchTerm.toLowerCase()));
        const matchesStatus = statusFilter === 'all' || task.status === statusFilter;
        const matchesPriority = priorityFilter === 'all' || task.priority === priorityFilter;
        return matchesSearch && matchesStatus && matchesPriority;
    });

    const stats = {
        total: tasks.length,
        todo: tasks.filter(t => t.status === 'todo').length,
        in_progress: tasks.filter(t => t.status === 'in_progress').length,
        done: tasks.filter(t => t.status === 'done').length,
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link
                            href={route('consulting.projects.show', project.id)}
                            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            <ArrowLeft size={20} />
                        </Link>
                        <div>
                            <h2 className="text-xl font-semibold leading-tight text-gray-800">
                                Tasks - {project.name}
                            </h2>
                            {project.code && (
                                <p className="text-sm text-gray-500">#{project.code}</p>
                            )}
                        </div>
                    </div>
                    <Link
                        href={route('consulting.projects.tasks.create', project.id)}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors"
                    >
                        <Plus size={18} />
                        New Task
                    </Link>
                </div>
            }
        >
            <Head title={`Tasks - ${project.name}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-xl p-4">
                            <div className="text-sm text-gray-600 mb-1">Total Tasks</div>
                            <div className="text-2xl font-bold text-gray-900">{stats.total}</div>
                        </div>
                        <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-xl p-4">
                            <div className="text-sm text-gray-600 mb-1">To Do</div>
                            <div className="text-2xl font-bold text-gray-900">{stats.todo}</div>
                        </div>
                        <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-xl p-4">
                            <div className="text-sm text-gray-600 mb-1">In Progress</div>
                            <div className="text-2xl font-bold text-blue-600">{stats.in_progress}</div>
                        </div>
                        <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-xl p-4">
                            <div className="text-sm text-gray-600 mb-1">Completed</div>
                            <div className="text-2xl font-bold text-green-600">{stats.done}</div>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-xl p-4 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={18} />
                                <input
                                    type="text"
                                    placeholder="Search tasks..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                            <select
                                value={statusFilter}
                                onChange={(e) => setStatusFilter(e.target.value)}
                                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                                <option value="all">All Statuses</option>
                                <option value="todo">To Do</option>
                                <option value="in_progress">In Progress</option>
                                <option value="review">Review</option>
                                <option value="done">Done</option>
                                <option value="blocked">Blocked</option>
                            </select>
                            <select
                                value={priorityFilter}
                                onChange={(e) => setPriorityFilter(e.target.value)}
                                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                                <option value="all">All Priorities</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>

                    {/* Tasks List */}
                    {filteredTasks.length === 0 ? (
                        <div className="bg-white rounded-2xl shadow-sm p-12 text-center">
                            <CheckSquare size={64} className="mx-auto text-gray-400 mb-4" />
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                {searchTerm || statusFilter !== 'all' || priorityFilter !== 'all' 
                                    ? 'No tasks match your filters' 
                                    : 'No tasks yet'}
                            </h3>
                            <p className="text-gray-600 mb-6">
                                {searchTerm || statusFilter !== 'all' || priorityFilter !== 'all'
                                    ? 'Try adjusting your search or filters'
                                    : 'Get started by creating your first task'}
                            </p>
                            {!searchTerm && statusFilter === 'all' && priorityFilter === 'all' && (
                                <Link
                                    href={route('consulting.projects.tasks.create', project.id)}
                                    className="inline-flex items-center gap-2 px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors"
                                >
                                    <Plus size={18} />
                                    Create Task
                                </Link>
                            )}
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {filteredTasks.map((task) => (
                                <div
                                    key={task.id}
                                    onClick={() => router.visit(route('consulting.projects.tasks.show', [project.id, task.id]))}
                                    className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-xl p-5 hover:shadow-xl transition-all cursor-pointer group"
                                >
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3 mb-2">
                                                <CheckSquare 
                                                    size={20} 
                                                    className={task.status === 'done' ? 'text-green-600' : 'text-gray-400'} 
                                                />
                                                <h3 className="text-lg font-semibold text-gray-900 group-hover:text-teal-600 transition-colors">
                                                    {task.title}
                                                </h3>
                                                <span className={`px-2 py-1 rounded-lg text-xs font-medium ${getStatusColor(task.status)}`}>
                                                    {task.status.replace('_', ' ')}
                                                </span>
                                                <span className={`text-xs font-medium ${getPriorityColor(task.priority)}`}>
                                                    {task.priority}
                                                </span>
                                            </div>
                                            
                                            {task.description && (
                                                <p className="text-sm text-gray-600 mb-3 line-clamp-2 ml-8">
                                                    {task.description}
                                                </p>
                                            )}

                                            <div className="flex items-center gap-4 ml-8 text-sm text-gray-500">
                                                {task.due_date && (
                                                    <div className="flex items-center gap-1">
                                                        <Calendar size={14} />
                                                        <span>{new Date(task.due_date).toLocaleDateString()}</span>
                                                    </div>
                                                )}
                                                {task.estimated_hours && (
                                                    <div className="flex items-center gap-1">
                                                        <Clock size={14} />
                                                        <span>{task.estimated_hours}h</span>
                                                    </div>
                                                )}
                                                {task.assigned_to_name && (
                                                    <div className="flex items-center gap-1">
                                                        <User size={14} />
                                                        <span>{task.assigned_to_name}</span>
                                                    </div>
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
        </AuthenticatedLayout>
    );
}

