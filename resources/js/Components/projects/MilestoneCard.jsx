import { Card } from '@/Components/ui/Card';
import { Badge } from '@/Components/ui/Badge';
import { Calendar, CheckCircle2, Clock, XCircle } from 'lucide-react';

export function MilestoneCard({ milestone, showActions = true }) {
    const getStatusIcon = (status) => {
        const icons = {
            pending: Clock,
            in_progress: Clock,
            completed: CheckCircle2,
            cancelled: XCircle,
        };
        const Icon = icons[status] || Clock;
        return <Icon className="h-4 w-4" />;
    };

    const getStatusColor = (status) => {
        const colors = {
            pending: 'bg-gray-100 text-gray-700',
            in_progress: 'bg-blue-100 text-blue-700',
            completed: 'bg-green-100 text-green-700',
            cancelled: 'bg-red-100 text-red-700',
        };
        return colors[status] || colors.pending;
    };

    const isOverdue = milestone.status !== 'completed' && 
                     new Date(milestone.target_date) < new Date();

    return (
        <Card className={`p-4 hover:shadow-md transition-shadow ${isOverdue ? 'border-l-4 border-l-red-500' : ''}`}>
            <div className="flex items-start justify-between gap-3">
                <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                        <span className={getStatusColor(milestone.status)}>
                            {getStatusIcon(milestone.status)}
                        </span>
                        <h4 className="font-medium text-gray-900">{milestone.name}</h4>
                    </div>
                    
                    {milestone.description && (
                        <p className="text-sm text-gray-600 mb-3">
                            {milestone.description}
                        </p>
                    )}

                    <div className="flex items-center gap-2 flex-wrap">
                        <Badge className={getStatusColor(milestone.status)}>
                            {milestone.status.replace('_', ' ')}
                        </Badge>
                        <div className="flex items-center gap-1 text-sm text-gray-500">
                            <Calendar className="h-4 w-4" />
                            <span>
                                {new Date(milestone.target_date).toLocaleDateString()}
                            </span>
                        </div>
                        {milestone.completed_date && (
                            <span className="text-xs text-gray-500">
                                Completed: {new Date(milestone.completed_date).toLocaleDateString()}
                            </span>
                        )}
                        {isOverdue && (
                            <Badge className="bg-red-100 text-red-700">
                                Overdue
                            </Badge>
                        )}
                    </div>
                </div>
            </div>
        </Card>
    );
}

