import { Link } from '@inertiajs/react';
import { Card } from '@/Components/ui/Card';
import { Badge } from '@/Components/ui/Badge';
import { Progress } from '@/Components/ui/Progress';
import { Calendar, User, FolderKanban } from 'lucide-react';

export function ProjectCard({ project, showActions = true }) {
    const getStatusColor = (status) => {
        const colors = {
            planning: 'bg-gray-100 text-gray-700',
            active: 'bg-green-100 text-green-700',
            on_hold: 'bg-yellow-100 text-yellow-700',
            completed: 'bg-blue-100 text-blue-700',
            cancelled: 'bg-red-100 text-red-700',
        };
        return colors[status] || colors.planning;
    };

    const getPriorityColor = (priority) => {
        const colors = {
            low: 'bg-gray-100 text-gray-700',
            medium: 'bg-blue-100 text-blue-700',
            high: 'bg-orange-100 text-orange-700',
            urgent: 'bg-red-100 text-red-700',
        };
        return colors[priority] || colors.medium;
    };

    const projectColor = project.color || '#3b82f6';

    return (
        <Card className="hover:shadow-lg transition-shadow">
            <Link href={`/projects/${project.id}`} className="block p-6">
                <div className="flex items-start justify-between mb-4">
                    <div className="flex items-center gap-3">
                        <div 
                            className="w-3 h-3 rounded-full" 
                            style={{ backgroundColor: projectColor }}
                        />
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900">{project.name}</h3>
                            {project.description && (
                                <p className="text-sm text-gray-500 mt-1 line-clamp-2">
                                    {project.description}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Badge className={getStatusColor(project.status)}>
                            {project.status.replace('_', ' ')}
                        </Badge>
                        <Badge className={getPriorityColor(project.priority)}>
                            {project.priority}
                        </Badge>
                    </div>
                </div>

                <div className="space-y-3">
                    {/* Progress */}
                    <div>
                        <div className="flex justify-between text-sm mb-1">
                            <span className="text-gray-600">Progress</span>
                            <span className="font-medium">{project.progress_percentage}%</span>
                        </div>
                        <Progress value={project.progress_percentage} className="h-2" />
                    </div>

                    {/* Meta Info */}
                    <div className="flex items-center gap-4 text-sm text-gray-500">
                        {project.project_manager && (
                            <div className="flex items-center gap-1">
                                <User className="h-4 w-4" />
                                <span>{project.project_manager.name}</span>
                            </div>
                        )}
                        {project.target_completion_date && (
                            <div className="flex items-center gap-1">
                                <Calendar className="h-4 w-4" />
                                <span>{new Date(project.target_completion_date).toLocaleDateString()}</span>
                            </div>
                        )}
                    </div>

                    {/* Budget */}
                    {project.budget && (
                        <div className="flex items-center justify-between text-sm pt-2 border-t">
                            <span className="text-gray-600">Budget</span>
                            <span className="font-medium">
                                {new Intl.NumberFormat('en-ZM', {
                                    style: 'currency',
                                    currency: 'ZMW',
                                }).format(project.budget)}
                            </span>
                        </div>
                    )}
                </div>
            </Link>
        </Card>
    );
}

