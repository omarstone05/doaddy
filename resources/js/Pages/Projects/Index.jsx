import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Eye, Edit, Trash2, FolderKanban } from 'lucide-react';

export default function ProjectsIndex({ projects, filters }) {
    const handleDelete = (projectId) => {
        if (confirm('Are you sure you want to delete this project?')) {
            router.delete(`/projects/${projectId}`);
        }
    };

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

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Projects" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Projects</h1>
                        <p className="text-gray-500 mt-1">Manage your organization's projects</p>
                    </div>
                    <Button onClick={() => router.visit('/projects/create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        New Project
                    </Button>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input
                                type="text"
                                value={filters?.search || ''}
                                onChange={(e) => router.visit(`/projects?search=${e.target.value}`)}
                                placeholder="Search projects..."
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                value={filters?.status || ''}
                                onChange={(e) => router.visit(`/projects?status=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
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
                            <label className="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                            <select
                                value={filters?.priority || ''}
                                onChange={(e) => router.visit(`/projects?priority=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
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
                <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Project Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Project Manager</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Priority</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Progress</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {projects.data.length === 0 ? (
                                <tr>
                                    <td colSpan="6" className="px-6 py-12 text-center text-gray-500">
                                        No projects found. Create your first project to get started.
                                    </td>
                                </tr>
                            ) : (
                                projects.data.map((project) => (
                                    <tr key={project.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-gray-900">{project.name}</div>
                                            {project.description && (
                                                <div className="text-sm text-gray-500 mt-1">{project.description.substring(0, 60)}...</div>
                                            )}
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
                                                <div className="flex-1 bg-gray-200 rounded-full h-2">
                                                    <div
                                                        className="bg-teal-500 h-2 rounded-full"
                                                        style={{ width: `${project.progress_percentage}%` }}
                                                    />
                                                </div>
                                                <span className="text-sm font-medium text-gray-900">{project.progress_percentage}%</span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusBadge(project.status)}`}>
                                                {project.status.replace('_', ' ').charAt(0).toUpperCase() + project.status.replace('_', ' ').slice(1)}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link href={`/projects/${project.id}`}>
                                                    <button className="text-gray-400 hover:text-teal-600">
                                                        <Eye className="h-4 w-4" />
                                                    </button>
                                                </Link>
                                                <Link href={`/projects/${project.id}/edit`}>
                                                    <button className="text-gray-400 hover:text-teal-600">
                                                        <Edit className="h-4 w-4" />
                                                    </button>
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(project.id)}
                                                    className="text-gray-400 hover:text-red-600"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {projects.links && projects.links.length > 3 && (
                    <div className="mt-4 flex items-center justify-between">
                        <div className="text-sm text-gray-500">
                            Showing {projects.from} to {projects.to} of {projects.total} results
                        </div>
                        <div className="flex gap-2">
                            {projects.links.map((link, index) => (
                                <button
                                    key={index}
                                    onClick={() => link.url && router.visit(link.url)}
                                    disabled={!link.url}
                                    className={`px-3 py-2 text-sm border rounded-lg ${
                                        link.active
                                            ? 'bg-teal-500 text-white border-teal-500'
                                            : link.url
                                            ? 'border-gray-300 hover:bg-gray-50'
                                            : 'border-gray-200 text-gray-400 cursor-not-allowed'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}

