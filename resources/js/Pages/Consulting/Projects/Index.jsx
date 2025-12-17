import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Plus, Briefcase, Calendar, Users, TrendingUp, AlertCircle } from 'lucide-react';

export default function Index({ auth, projects }) {
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

    const getHealthColor = (health) => {
        const colors = {
            on_track: 'text-green-600',
            at_risk: 'text-yellow-600',
            delayed: 'text-red-600',
        };
        return colors[health] || colors.on_track;
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Projects
                    </h2>
                    <Link
                        href={route('consulting.projects.create')}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors"
                    >
                        <Plus size={18} />
                        New Project
                    </Link>
                </div>
            }
        >
            <Head title="Projects" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {projects.data.length === 0 ? (
                        <div className="bg-white rounded-2xl shadow-sm p-12 text-center">
                            <Briefcase size={64} className="mx-auto text-gray-400 mb-4" />
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                No projects yet
                            </h3>
                            <p className="text-gray-600 mb-6">
                                Get started by creating your first project
                            </p>
                            <Link
                                href={route('consulting.projects.create')}
                                className="inline-flex items-center gap-2 px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors"
                            >
                                <Plus size={18} />
                                Create Project
                            </Link>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {projects.data.map((project) => (
                                <div
                                    key={project.id}
                                    onClick={() => router.visit(route('consulting.projects.show', project.id))}
                                    className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 hover:shadow-xl transition-all cursor-pointer group"
                                >
                                    {/* Header */}
                                    <div className="flex items-start justify-between mb-4">
                                        <div className="flex-1">
                                            <h3 className="text-lg font-semibold text-gray-900 group-hover:text-teal-600 transition-colors mb-1">
                                                {project.name}
                                            </h3>
                                            {project.code && (
                                                <p className="text-xs text-gray-500">#{project.code}</p>
                                            )}
                                        </div>
                                        <span className={`px-2 py-1 rounded-lg text-xs font-medium ${getStatusColor(project.status)}`}>
                                            {project.status}
                                        </span>
                                    </div>

                                    {/* Description */}
                                    {project.description && (
                                        <p className="text-sm text-gray-600 mb-4 line-clamp-2">
                                            {project.description}
                                        </p>
                                    )}

                                    {/* Stats */}
                                    <div className="grid grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <div className="text-xs text-gray-500 mb-1">Budget</div>
                                            <div className="text-sm font-semibold text-gray-900">
                                                {formatCurrency(project.budget_total)}
                                            </div>
                                        </div>
                                        <div>
                                            <div className="text-xs text-gray-500 mb-1">Progress</div>
                                            <div className="text-sm font-semibold text-gray-900">
                                                {project.progress_percentage}%
                                            </div>
                                        </div>
                                    </div>

                                    {/* Health Status */}
                                    <div className="flex items-center gap-2 pt-4 border-t border-gray-200">
                                        <AlertCircle
                                            size={16}
                                            className={getHealthColor(project.health_status)}
                                        />
                                        <span className={`text-xs font-medium ${getHealthColor(project.health_status)}`}>
                                            {project.health_status.replace('_', ' ')}
                                        </span>
                                        {project.end_date && (
                                            <>
                                                <span className="text-gray-300">•</span>
                                                <Calendar size={14} className="text-gray-400" />
                                                <span className="text-xs text-gray-500">
                                                    {new Date(project.end_date).toLocaleDateString()}
                                                </span>
                                            </>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Pagination */}
                    {projects.links && projects.links.length > 3 && (
                        <div className="mt-6 flex justify-center">
                            <div className="flex gap-2">
                                {projects.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                                            link.active
                                                ? 'bg-teal-600 text-white'
                                                : 'bg-white text-gray-700 hover:bg-gray-50'
                                        } ${!link.url && 'opacity-50 cursor-not-allowed'}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

