import { Link } from '@inertiajs/react';
import { Card } from '@/Components/ui/Card';
import { Badge } from '@/Components/ui/Badge';
import { Avatar, AvatarFallback } from '@/Components/ui/Avatar';
import { CheckCircle2, Circle, Clock, AlertCircle, XCircle } from 'lucide-react';

export function TaskCard({ task, onStatusChange, onPriorityChange, showActions = true }) {
    const getStatusIcon = (status) => {
        const icons = {
            todo: Circle,
            in_progress: Clock,
            review: AlertCircle,
            done: CheckCircle2,
            blocked: XCircle,
        };
        const Icon = icons[status] || Circle;
        return <Icon className="h-4 w-4" />;
    };

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
            low: 'bg-gray-100 text-gray-700',
            medium: 'bg-blue-100 text-blue-700',
            high: 'bg-orange-100 text-orange-700',
            urgent: 'bg-red-100 text-red-700',
        };
        return colors[priority] || colors.medium;
    };

    const getInitials = (name) => {
        return name
            .split(' ')
            .map(n => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    return (
        <Link href={`/projects/${task.project_id}/tasks/${task.id}`}>
            <Card className="p-4 hover:shadow-md transition-shadow cursor-pointer">
                <div className="flex items-start justify-between gap-3">
                    <div className="flex-1">
                        <div className="flex items-center gap-2 mb-2">
                            <span className={getStatusColor(task.status)}>
                                {getStatusIcon(task.status)}
                            </span>
                            <h4 className="font-medium text-gray-900 hover:text-teal-600">{task.title}</h4>
                        </div>
                    
                    {task.description && (
                        <p className="text-sm text-gray-600 mb-3 line-clamp-2">
                            {task.description}
                        </p>
                    )}

                    <div className="flex items-center gap-2 flex-wrap">
                        <Badge className={getStatusColor(task.status)}>
                            {task.status.replace('_', ' ')}
                        </Badge>
                        <Badge className={getPriorityColor(task.priority)}>
                            {task.priority}
                        </Badge>
                        {task.assigned_to && (
                            <Avatar className="h-6 w-6">
                                <AvatarFallback className="text-xs">
                                    {getInitials(task.assigned_to.name)}
                                </AvatarFallback>
                            </Avatar>
                        )}
                        {task.due_date && (
                            <span className="text-xs text-gray-500">
                                Due: {new Date(task.due_date).toLocaleDateString()}
                            </span>
                        )}
                        {task.estimated_hours && (
                            <span className="text-xs text-gray-500">
                                {task.estimated_hours}h
                            </span>
                        )}
                    </div>
                </div>
            </div>
        </Card>
        </Link>
    );
}

