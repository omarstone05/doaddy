import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Eye, Edit, Trash2, FolderKanban, Search, Filter } from 'lucide-react';

export default function ProjectsIndex({ projects, filters }) {
    const handleDelete = (projectId) => {
        if (confirm('Are you sure you want to delete this project?')) {
            router.delete(`/projects/${projectId}`);
        }
    };

    const getStatusBadge = (status) => {
        const badges = {
            planning: 'bg-gray-100 text-gray-700',
            active: 'bg-teal-100 text-teal-700',
            on_hold: 'bg-amber-100 text-amber-700',
            completed: 'bg-blue-100 text-blue-700',
            cancelled: 'bg-red-100 text-red-600',
        };
        return badges[status] || 'bg-gray-100 text-gray-700';
    };

    const getPriorityBadge = (priority) => {
        const badges = {
            low: 'bg-gray-100 text-gray-700',
            medium: 'bg-blue-100 text-blue-700',
            high: 'bg-orange-100 text-orange-700',
            urgent: 'bg-red-100 text-red-600',
        };
        return badges[priority] || 'bg-gray-100 text-gray-700';
    };

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Projects" />
            <div>
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-black text-gray-900 tracking-tight">Projects</h1>
                        <p className="text-gray-500 mt-1">Manage your organization's projects</p>
                    </div>
                    <Button onClick={() => router.visit('/projects/create')} className="gap-2">
                        <Plus className="h-4 w-4" />
                        New Project
                    </Button>
                </div>

                {/* Filters Card */}
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 mb-6">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
                            <Filter className="w-5 h-5 text-teal-600" />
                        </div>
                        <h3 className="text-sm font-bold text-gray-900">Filters</h3>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Search</label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <input
                                    type="text"
                                    value={filters?.search || ''}
                                    onChange={(e) => router.visit(`/projects?search=${e.target.value}`)}
                                    placeholder="Search projects..."
                                    className="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Status</label>
                            <select
                                value={filters?.status || ''}
                                onChange={(e) => router.visit(`/projects?status=${e.target.value}`)}
                                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            >
                                <option value="">All Status</option>
                                <option value="planning">Planning</option>
                                <option value="active">Active</option>
                                <option value="on_hold">On Hold</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Priority</label>
                            <select
                                value={filters?.priority || ''}
                                onChange={(e) => router.visit(`/projects?priority=${e.target.value}`)}
                                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            >
                                <option value="">All Priorities</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Projects Table */}
                {projects.data.length === 0 ? (
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-12 border border-gray-200/50 text-center">
                        <div className="w-16 h-16 rounded-2xl bg-teal-100 flex items-center justify-center mx-auto mb-4">
                            <FolderKanban className="h-8 w-8 text-teal-500" />
                        </div>
                        <h3 className="text-lg font-bold text-gray-900 mb-2">No projects yet</h3>
                        <p className="text-gray-500 mb-6">Create your first project to get started</p>
                        <Button onClick={() => router.visit('/projects/create')} className="gap-2">
                            <Plus className="h-4 w-4" />
                            Create Project
                        </Button>
                    </div>
                ) : (
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 overflow-hidden">
                        <table className="w-full">
                            <thead className="bg-gray-50/80 border-b border-gray-200/50">
                                <tr>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Project</th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Manager</th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Priority</th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Progress</th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {projects.data.map((project) => (
                                    <tr key={project.id} className="hover:bg-teal-50/30 transition-colors">
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
                                                    <FolderKanban className="h-5 w-5 text-teal-600" />
                                                </div>
                                                <div>
                                                    <p className="font-bold text-gray-900">{project.name}</p>
                                                    {project.description && (
                                                        <p className="text-xs text-gray-500 truncate max-w-xs">{project.description.substring(0, 50)}...</p>
                                                    )}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                            {project.project_manager?.name || '-'}
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${getPriorityBadge(project.priority)}`}>
                                                {project.priority?.charAt(0).toUpperCase() + project.priority?.slice(1)}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-3 min-w-32">
                                                <div className="flex-1 bg-gray-200 rounded-full h-2 overflow-hidden">
                                                    <div
                                                        className="bg-gradient-to-r from-teal-400 to-teal-600 h-2 rounded-full transition-all duration-500"
                                                        style={{ width: `${project.progress_percentage || 0}%` }}
                                                    />
                                                </div>
                                                <span className="text-sm font-bold text-gray-900 min-w-10">{project.progress_percentage || 0}%</span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${getStatusBadge(project.status)}`}>
                                                {project.status?.replace('_', ' ').charAt(0).toUpperCase() + project.status?.replace('_', ' ').slice(1)}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            <div className="flex items-center justify-center gap-1">
                                                <Link 
                                                    href={`/projects/${project.id}`}
                                                    className="p-2 rounded-lg text-teal-600 hover:bg-teal-100 transition-colors"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                                <Link 
                                                    href={`/projects/${project.id}/edit`}
                                                    className="p-2 rounded-lg text-blue-600 hover:bg-blue-100 transition-colors"
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(project.id)}
                                                    className="p-2 rounded-lg text-red-500 hover:bg-red-100 transition-colors"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        {projects.links && projects.links.length > 3 && (
                            <div className="px-6 py-4 border-t border-gray-200/50 flex items-center justify-between bg-gray-50/50">
                                <p className="text-sm text-gray-600">
                                    Showing <span className="font-semibold">{projects.from}</span> to <span className="font-semibold">{projects.to}</span> of <span className="font-semibold">{projects.total}</span>
                                </p>
                                <div className="flex gap-1">
                                    {projects.links.map((link, index) => (
                                        <button
                                            key={index}
                                            onClick={() => link.url && router.visit(link.url)}
                                            disabled={!link.url}
                                            className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all ${
                                                link.active
                                                    ? 'bg-teal-500 text-white shadow-sm'
                                                    : link.url
                                                    ? 'bg-white border border-gray-200 text-gray-700 hover:bg-teal-50 hover:border-teal-200'
                                                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}
