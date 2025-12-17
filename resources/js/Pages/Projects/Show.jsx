import { Head, Link, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Calendar, User, FolderKanban } from 'lucide-react';

export default function ProjectsShow({ project }) {
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
            <Head title={project.name} />
            <div className="max-w-4xl mx-auto">
                <Link href="/projects">
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Projects
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{project.name}</h1>
                            {project.description && (
                                <p className="text-gray-500 mt-2">{project.description}</p>
                            )}
                        </div>
                        <div className="flex gap-2">
                            <span className={`px-3 py-1 text-sm font-medium rounded-full ${getStatusBadge(project.status)}`}>
                                {project.status.replace('_', ' ').charAt(0).toUpperCase() + project.status.replace('_', ' ').slice(1)}
                            </span>
                            <span className={`px-3 py-1 text-sm font-medium rounded-full ${getPriorityBadge(project.priority)}`}>
                                {project.priority.charAt(0).toUpperCase() + project.priority.slice(1)}
                            </span>
                        </div>
                    </div>

                    <div className="mb-6">
                        <div className="text-sm text-gray-600 mb-2">Progress</div>
                        <div className="flex items-center gap-2">
                            <div className="flex-1 bg-gray-200 rounded-full h-3">
                                <div
                                    className="bg-teal-500 h-3 rounded-full"
                                    style={{ width: `${project.progress_percentage}%` }}
                                />
                            </div>
                            <span className="font-medium text-gray-900">{project.progress_percentage}%</span>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <User className="h-4 w-4" />
                                <span className="text-sm font-medium">Project Manager</span>
                            </div>
                            <p className="text-gray-900">{project.project_manager?.name || '-'}</p>
                        </div>

                        {project.start_date && (
                            <div>
                                <div className="flex items-center gap-2 text-gray-600 mb-2">
                                    <Calendar className="h-4 w-4" />
                                    <span className="text-sm font-medium">Start Date</span>
                                </div>
                                <p className="text-gray-900">{new Date(project.start_date).toLocaleDateString()}</p>
                            </div>
                        )}

                        {project.end_date && (
                            <div>
                                <div className="flex items-center gap-2 text-gray-600 mb-2">
                                    <Calendar className="h-4 w-4" />
                                    <span className="text-sm font-medium">End Date</span>
                                </div>
                                <p className="text-gray-900">{new Date(project.end_date).toLocaleDateString()}</p>
                            </div>
                        )}

                        {project.target_completion_date && (
                            <div>
                                <div className="flex items-center gap-2 text-gray-600 mb-2">
                                    <Calendar className="h-4 w-4" />
                                    <span className="text-sm font-medium">Target Completion</span>
                                </div>
                                <p className="text-gray-900">{new Date(project.target_completion_date).toLocaleDateString()}</p>
                            </div>
                        )}
                    </div>

                    {project.notes && (
                        <div className="mt-6">
                            <div className="text-sm font-medium text-gray-700 mb-2">Notes</div>
                            <p className="text-gray-900">{project.notes}</p>
                        </div>
                    )}
                </div>

                <div className="flex gap-3">
                    <Link href={`/projects/${project.id}/edit`}>
                        <Button>Edit Project</Button>
                    </Link>
                </div>
            </div>
        </SectionLayout>
    );
}

