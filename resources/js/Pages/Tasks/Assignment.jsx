import { Head } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Badge } from '@/Components/ui/Badge';
import { Avatar, AvatarFallback } from '@/Components/ui/Avatar';
import { 
    CheckSquare, 
    Clock, 
    CheckCircle2, 
    Circle, 
    AlertCircle, 
    XCircle,
    Play,
    Square,
    Filter,
    User,
    UserPlus,
    Users,
} from 'lucide-react';
import { useState, useEffect } from 'react';
import axios from 'axios';
import { Link, router } from '@inertiajs/react';

export default function TaskAssignment({ initialTasks = [], initialStats = {}, users = [], canAssignTasks = false }) {
    const [tasks, setTasks] = useState(initialTasks);
    const [stats, setStats] = useState(initialStats);
    const [loading, setLoading] = useState(false);
    const [filter, setFilter] = useState('all'); // all, active, todo, in_progress, done
    const [selectedUser, setSelectedUser] = useState(null);
    const [assigningTask, setAssigningTask] = useState(null);

    useEffect(() => {
        loadTasks();
    }, [filter, selectedUser]);

    const loadTasks = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (filter === 'active') {
                params.append('active_only', 'true');
            } else if (filter !== 'all') {
                params.append('status', filter);
            }

            const response = await axios.get(`/api/tasks/my-tasks?${params.toString()}`);
            setTasks(response.data.tasks || []);
            setStats(response.data.stats || {});
        } catch (error) {
            console.error('Error loading tasks:', error);
        } finally {
            setLoading(false);
        }
    };

    const startWork = async (taskId, projectId) => {
        try {
            await axios.post(`/api/projects/${projectId}/tasks/${taskId}/start-work`);
            loadTasks();
        } catch (error) {
            console.error('Error starting work:', error);
            alert('Failed to start work on task');
        }
    };

    const stopWork = async (taskId, projectId, newStatus = 'todo') => {
        try {
            await axios.post(`/api/projects/${projectId}/tasks/${taskId}/stop-work`, {
                status: newStatus,
            });
            loadTasks();
        } catch (error) {
            console.error('Error stopping work:', error);
            alert('Failed to stop work on task');
        }
    };

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

    const formatDuration = (minutes) => {
        if (!minutes) return null;
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        if (hours > 0) {
            return `${hours}h ${mins}m`;
        }
        return `${mins}m`;
    };

    const filterOptions = [
        { value: 'all', label: 'All Tasks' },
        { value: 'active', label: 'Active' },
        { value: 'todo', label: 'To Do' },
        { value: 'in_progress', label: 'In Progress' },
        { value: 'done', label: 'Done' },
    ];

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Task Assignment" />
            
            {/* Header */}
            <div className="flex items-center justify-between mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Task Assignment</h1>
                    <p className="text-gray-500 mt-1">
                        {canAssignTasks 
                            ? 'Assign tasks to users and manage task assignments' 
                            : 'View and manage your assigned tasks'}
                    </p>
                </div>
            </div>

            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Total Tasks</p>
                                <p className="text-2xl font-bold text-gray-900">{stats.total || 0}</p>
                            </div>
                            <CheckSquare className="h-8 w-8 text-blue-500" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Active</p>
                                <p className="text-2xl font-bold text-gray-900">{stats.active || 0}</p>
                            </div>
                            <Play className="h-8 w-8 text-green-500" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">In Progress</p>
                                <p className="text-2xl font-bold text-gray-900">{stats.in_progress || 0}</p>
                            </div>
                            <Clock className="h-8 w-8 text-orange-500" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Completed</p>
                                <p className="text-2xl font-bold text-gray-900">{stats.done || 0}</p>
                            </div>
                            <CheckCircle2 className="h-8 w-8 text-green-500" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Filters */}
            <div className="flex items-center gap-4 mb-6">
                <div className="flex items-center gap-2">
                    <Filter className="h-4 w-4 text-gray-500" />
                    <span className="text-sm font-medium text-gray-700">Filter:</span>
                </div>
                <div className="flex gap-2">
                    {filterOptions.map((option) => (
                        <Button
                            key={option.value}
                            variant={filter === option.value ? 'primary' : 'secondary'}
                            size="sm"
                            onClick={() => setFilter(option.value)}
                        >
                            {option.label}
                        </Button>
                    ))}
                </div>
            </div>

            {/* Tasks List */}
            {loading ? (
                <Card>
                    <CardContent className="pt-6 text-center py-12">
                        <p className="text-gray-500">Loading tasks...</p>
                    </CardContent>
                </Card>
            ) : tasks.length === 0 ? (
                <Card>
                    <CardContent className="pt-6 text-center py-12">
                        <p className="text-gray-500">No tasks found. You don't have any assigned tasks yet.</p>
                    </CardContent>
                </Card>
            ) : (
                <div className="space-y-4">
                    {tasks.map((task) => {
                        const isActive = task.status === 'in_progress' && task.started_working_at;
                        const workDuration = task.started_working_at 
                            ? formatDuration(Math.floor((new Date() - new Date(task.started_working_at)) / 60000))
                            : null;

                        return (
                            <Card key={task.id} className="hover:shadow-md transition-shadow">
                                <CardContent className="pt-6">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2 mb-2">
                                                <span className={getStatusColor(task.status)}>
                                                    {getStatusIcon(task.status)}
                                                </span>
                                                <Link 
                                                    href={`/projects/${task.project_id}/tasks/${task.id}`}
                                                    className="font-medium text-gray-900 hover:text-blue-600"
                                                >
                                                    {task.title}
                                                </Link>
                                                {isActive && (
                                                    <Badge className="bg-green-100 text-green-700">
                                                        <Play className="h-3 w-3 mr-1" />
                                                        Active
                                                    </Badge>
                                                )}
                                            </div>

                                            {task.description && (
                                                <p className="text-sm text-gray-600 mb-3 line-clamp-2">
                                                    {task.description}
                                                </p>
                                            )}

                                            <div className="flex items-center gap-3 flex-wrap">
                                                <Badge className={getStatusColor(task.status)}>
                                                    {task.status.replace('_', ' ')}
                                                </Badge>
                                                <Badge className={getPriorityColor(task.priority)}>
                                                    {task.priority}
                                                </Badge>
                                                {task.project && (
                                                    <Link 
                                                        href={`/projects/${task.project_id}`}
                                                        className="text-sm text-blue-600 hover:underline"
                                                    >
                                                        {task.project.name}
                                                    </Link>
                                                )}
                                                {task.due_date && (
                                                    <span className="text-xs text-gray-500">
                                                        Due: {new Date(task.due_date).toLocaleDateString()}
                                                    </span>
                                                )}
                                                {task.estimated_hours && (
                                                    <span className="text-xs text-gray-500">
                                                        Est: {task.estimated_hours}h
                                                    </span>
                                                )}
                                                {isActive && workDuration && (
                                                    <span className="text-xs text-green-600 font-medium">
                                                        Working: {workDuration}
                                                    </span>
                                                )}
                                            </div>

                                            {/* Assigned Users */}
                                            {canAssignTasks && task.assigned_users && task.assigned_users.length > 0 && (
                                                <div className="mt-3 pt-3 border-t border-gray-200">
                                                    <div className="flex items-center gap-2 mb-2">
                                                        <Users className="h-4 w-4 text-gray-500" />
                                                        <span className="text-sm font-medium text-gray-700">Assigned Users:</span>
                                                    </div>
                                                    <div className="flex flex-wrap gap-2">
                                                        {task.assigned_users.map((assignedUser) => (
                                                            <Badge key={assignedUser.id} className="bg-blue-100 text-blue-700">
                                                                {assignedUser.name}
                                                            </Badge>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                            {/* Fallback to assigned_to for backward compatibility */}
                                            {canAssignTasks && (!task.assigned_users || task.assigned_users.length === 0) && task.assigned_to && (
                                                <div className="mt-3 pt-3 border-t border-gray-200">
                                                    <div className="flex items-center gap-2 mb-2">
                                                        <Users className="h-4 w-4 text-gray-500" />
                                                        <span className="text-sm font-medium text-gray-700">Assigned To:</span>
                                                    </div>
                                                    <div className="flex flex-wrap gap-2">
                                                        <Badge className="bg-blue-100 text-blue-700">
                                                            {task.assigned_to.name}
                                                        </Badge>
                                                    </div>
                                                </div>
                                            )}
                                        </div>

                                        <div className="flex items-center gap-2">
                                            {canAssignTasks && (
                                                <Link href={`/projects/${task.project_id}/tasks/${task.id}/assign`}>
                                                    <Button
                                                        size="sm"
                                                        variant="secondary"
                                                    >
                                                        <UserPlus className="h-4 w-4 mr-2" />
                                                        Assign
                                                    </Button>
                                                </Link>
                                            )}
                                            {!canAssignTasks && isActive ? (
                                                <Button
                                                    size="sm"
                                                    variant="secondary"
                                                    onClick={() => stopWork(task.id, task.project_id)}
                                                >
                                                    <Square className="h-4 w-4 mr-2" />
                                                    Stop
                                                </Button>
                                            ) : !canAssignTasks && task.status !== 'done' && task.status !== 'blocked' ? (
                                                <Button
                                                    size="sm"
                                                    variant="primary"
                                                    onClick={() => startWork(task.id, task.project_id)}
                                                >
                                                    <Play className="h-4 w-4 mr-2" />
                                                    Start
                                                </Button>
                                            ) : null}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            )}
        </SectionLayout>
    );
}

