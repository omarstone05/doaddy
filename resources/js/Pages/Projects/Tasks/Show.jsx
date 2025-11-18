import { Head, Link, router, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { 
    ArrowLeft, 
    Edit, 
    Trash2, 
    User, 
    Calendar, 
    Clock, 
    Tag, 
    CheckCircle2, 
    AlertCircle,
    FileText,
    List,
    TrendingUp,
    Users,
    UserPlus
} from 'lucide-react';
import { useState } from 'react';

export default function TaskShow({ task, totalTime, subtaskStats, users, canEdit, canDelete }) {
    const { data, setData, put, processing, delete: destroy } = useForm({
        title: task.title,
        description: task.description || '',
        status: task.status,
        priority: task.priority,
        assigned_to_id: task.assigned_to_id || '',
        due_date: task.due_date || '',
        start_date: task.start_date || '',
        estimated_hours: task.estimated_hours || '',
        actual_hours: task.actual_hours || '',
    });

    const [isEditing, setIsEditing] = useState(false);

    const handleUpdate = (e) => {
        e.preventDefault();
        put(`/api/projects/${task.project_id}/tasks/${task.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setIsEditing(false);
            },
        });
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
            destroy(`/api/projects/${task.project_id}/tasks/${task.id}`, {
                onSuccess: () => {
                    router.visit(`/projects/${task.project_id}`);
                },
            });
        }
    };

    const getStatusBadge = (status) => {
        const styles = {
            todo: 'bg-gray-100 text-gray-800',
            in_progress: 'bg-blue-100 text-blue-800',
            review: 'bg-yellow-100 text-yellow-800',
            done: 'bg-green-100 text-green-800',
            blocked: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`px-3 py-1 text-sm font-medium rounded-full ${styles[status] || styles.todo}`}>
                {status.replace('_', ' ').toUpperCase()}
            </span>
        );
    };

    const getPriorityBadge = (priority) => {
        const styles = {
            low: 'bg-gray-100 text-gray-800',
            medium: 'bg-yellow-100 text-yellow-800',
            high: 'bg-orange-100 text-orange-800',
            urgent: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-medium rounded-full ${styles[priority] || styles.medium}`}>
                {priority.toUpperCase()}
            </span>
        );
    };

    const progressPercentage = task.subtasks?.length > 0
        ? Math.round((subtaskStats.done / subtaskStats.total) * 100)
        : (task.status === 'done' ? 100 : 0);

    return (
        <SectionLayout sectionName="Decisions">
            <Head title={`Task: ${task.title}`} />
            
            <div className="px-6 py-8 max-w-7xl mx-auto">
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-4">
                            <Link
                                href={`/projects/${task.project_id}`}
                                className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                            >
                                <ArrowLeft className="w-4 h-4 mr-1" />
                                Back to Project
                            </Link>
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900">
                                    {isEditing ? (
                                        <input
                                            type="text"
                                            value={data.title}
                                            onChange={(e) => setData('title', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                        />
                                    ) : (
                                        task.title
                                    )}
                                </h1>
                                <p className="mt-1 text-sm text-gray-500">
                                    Project: <Link href={`/projects/${task.project_id}`} className="text-teal-600 hover:text-teal-700">
                                        {task.project?.name}
                                    </Link>
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center space-x-3">
                            {getStatusBadge(task.status)}
                            {getPriorityBadge(task.priority)}
                            {(canEdit || canDelete) && (
                                <Link href={`/projects/${task.project_id}/tasks/${task.id}/assign`}>
                                    <Button variant="secondary">
                                        <UserPlus className="w-4 h-4 mr-2" />
                                        Assign Users
                                    </Button>
                                </Link>
                            )}
                            {canEdit && !isEditing && (
                                <Button onClick={() => setIsEditing(true)} variant="outline">
                                    <Edit className="w-4 h-4 mr-2" />
                                    Edit
                                </Button>
                            )}
                            {canDelete && (
                                <Button onClick={handleDelete} variant="outline" className="text-red-600 hover:text-red-700">
                                    <Trash2 className="w-4 h-4 mr-2" />
                                    Delete
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Main Content Grid */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Left Column - Main Details */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Description */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <FileText className="w-5 h-5 mr-2" />
                                        Description
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {isEditing ? (
                                        <textarea
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            rows={6}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                        />
                                    ) : (
                                        <p className="text-gray-700 whitespace-pre-wrap">
                                            {task.description || 'No description provided.'}
                                        </p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Subtasks */}
                            {task.subtasks && task.subtasks.length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center justify-between">
                                            <div className="flex items-center">
                                                <List className="w-5 h-5 mr-2" />
                                                Subtasks ({subtaskStats.done}/{subtaskStats.total})
                                            </div>
                                            <div className="text-sm font-normal text-gray-500">
                                                {progressPercentage}% Complete
                                            </div>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-3">
                                            {task.subtasks.map((subtask) => (
                                                <div
                                                    key={subtask.id}
                                                    className="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                                                >
                                                    <div className="flex items-center space-x-3">
                                                        <input
                                                            type="checkbox"
                                                            checked={subtask.status === 'done'}
                                                            readOnly
                                                            className="w-4 h-4 text-teal-600 rounded"
                                                        />
                                                        <span className={subtask.status === 'done' ? 'line-through text-gray-500' : 'text-gray-900'}>
                                                            {subtask.title}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center space-x-2">
                                                        {getStatusBadge(subtask.status)}
                                                        {subtask.assigned_to && (
                                                            <span className="text-sm text-gray-500">
                                                                {subtask.assigned_to.name}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Time Entries */}
                            {task.time_entries && task.time_entries.length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center">
                                            <Clock className="w-5 h-5 mr-2" />
                                            Time Entries ({task.time_entries.length})
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-3">
                                            {task.time_entries.slice(0, 10).map((entry) => (
                                                <div
                                                    key={entry.id}
                                                    className="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                                                >
                                                    <div>
                                                        <div className="font-medium text-gray-900">
                                                            {entry.user?.name || 'Unknown User'}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {new Date(entry.date).toLocaleDateString()}
                                                        </div>
                                                        {entry.description && (
                                                            <div className="text-sm text-gray-600 mt-1">
                                                                {entry.description}
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div className="text-lg font-semibold text-gray-900">
                                                        {entry.hours}h
                                                    </div>
                                                </div>
                                            ))}
                                            {task.time_entries.length > 10 && (
                                                <p className="text-sm text-gray-500 text-center">
                                                    +{task.time_entries.length - 10} more entries
                                                </p>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            )}
                        </div>

                        {/* Right Column - Sidebar */}
                        <div className="space-y-6">
                            {/* Task Details */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Task Details</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {isEditing ? (
                                        <form onSubmit={handleUpdate} className="space-y-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Status
                                                </label>
                                                <select
                                                    value={data.status}
                                                    onChange={(e) => setData('status', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                                >
                                                    <option value="todo">Todo</option>
                                                    <option value="in_progress">In Progress</option>
                                                    <option value="review">Review</option>
                                                    <option value="done">Done</option>
                                                    <option value="blocked">Blocked</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Priority
                                                </label>
                                                <select
                                                    value={data.priority}
                                                    onChange={(e) => setData('priority', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                                >
                                                    <option value="low">Low</option>
                                                    <option value="medium">Medium</option>
                                                    <option value="high">High</option>
                                                    <option value="urgent">Urgent</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Assigned To
                                                </label>
                                                <select
                                                    value={data.assigned_to_id}
                                                    onChange={(e) => setData('assigned_to_id', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                                >
                                                    <option value="">Unassigned</option>
                                                    {users.map((user) => (
                                                        <option key={user.id} value={user.id}>
                                                            {user.name}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Due Date
                                                </label>
                                                <input
                                                    type="date"
                                                    value={data.due_date}
                                                    onChange={(e) => setData('due_date', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Estimated Hours
                                                </label>
                                                <input
                                                    type="number"
                                                    value={data.estimated_hours}
                                                    onChange={(e) => setData('estimated_hours', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Actual Hours
                                                </label>
                                                <input
                                                    type="number"
                                                    value={data.actual_hours}
                                                    onChange={(e) => setData('actual_hours', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div className="flex space-x-2">
                                                <Button type="submit" disabled={processing}>
                                                    Save Changes
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() => {
                                                        setIsEditing(false);
                                                        setData({
                                                            title: task.title,
                                                            description: task.description || '',
                                                            status: task.status,
                                                            priority: task.priority,
                                                            assigned_to_id: task.assigned_to_id || '',
                                                            due_date: task.due_date || '',
                                                            start_date: task.start_date || '',
                                                            estimated_hours: task.estimated_hours || '',
                                                            actual_hours: task.actual_hours || '',
                                                        });
                                                    }}
                                                >
                                                    Cancel
                                                </Button>
                                            </div>
                                        </form>
                                    ) : (
                                        <>
                                            <div>
                                                <div className="text-sm font-medium text-gray-500 mb-1">Status</div>
                                                <div>{getStatusBadge(task.status)}</div>
                                            </div>

                                            <div>
                                                <div className="text-sm font-medium text-gray-500 mb-1">Priority</div>
                                                <div>{getPriorityBadge(task.priority)}</div>
                                            </div>

                                            <div>
                                                <div className="text-sm font-medium text-gray-500 mb-1 flex items-center">
                                                    <User className="w-4 h-4 mr-1" />
                                                    Assigned To
                                                </div>
                                                <div className="text-gray-900">
                                                    {task.assigned_to ? task.assigned_to.name : 'Unassigned'}
                                                </div>
                                            </div>

                                            <div>
                                                <div className="text-sm font-medium text-gray-500 mb-1 flex items-center">
                                                    <User className="w-4 h-4 mr-1" />
                                                    Created By
                                                </div>
                                                <div className="text-gray-900">
                                                    {task.created_by?.name || 'Unknown'}
                                                </div>
                                            </div>

                                            {task.due_date && (
                                                <div>
                                                    <div className="text-sm font-medium text-gray-500 mb-1 flex items-center">
                                                        <Calendar className="w-4 h-4 mr-1" />
                                                        Due Date
                                                    </div>
                                                    <div className="text-gray-900">
                                                        {new Date(task.due_date).toLocaleDateString()}
                                                    </div>
                                                </div>
                                            )}

                                            {task.start_date && (
                                                <div>
                                                    <div className="text-sm font-medium text-gray-500 mb-1 flex items-center">
                                                        <Calendar className="w-4 h-4 mr-1" />
                                                        Start Date
                                                    </div>
                                                    <div className="text-gray-900">
                                                        {new Date(task.start_date).toLocaleDateString()}
                                                    </div>
                                                </div>
                                            )}

                                            <div>
                                                <div className="text-sm font-medium text-gray-500 mb-1 flex items-center">
                                                    <Clock className="w-4 h-4 mr-1" />
                                                    Time Tracking
                                                </div>
                                                <div className="space-y-1">
                                                    {task.estimated_hours && (
                                                        <div className="text-gray-900">
                                                            Estimated: {task.estimated_hours}h
                                                        </div>
                                                    )}
                                                    {task.actual_hours && (
                                                        <div className="text-gray-900">
                                                            Actual: {task.actual_hours}h
                                                        </div>
                                                    )}
                                                    {totalTime > 0 && (
                                                        <div className="text-gray-900 font-semibold">
                                                            Total Logged: {totalTime}h
                                                        </div>
                                                    )}
                                                </div>
                                            </div>

                                            {task.tags && task.tags.length > 0 && (
                                                <div>
                                                    <div className="text-sm font-medium text-gray-500 mb-2 flex items-center">
                                                        <Tag className="w-4 h-4 mr-1" />
                                                        Tags
                                                    </div>
                                                    <div className="flex flex-wrap gap-2">
                                                        {task.tags.map((tag, index) => (
                                                            <span
                                                                key={index}
                                                                className="px-2 py-1 bg-teal-100 text-teal-800 text-xs rounded-full"
                                                            >
                                                                {tag}
                                                            </span>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Statistics */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <TrendingUp className="w-5 h-5 mr-2" />
                                        Statistics
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {task.subtasks && task.subtasks.length > 0 && (
                                        <div>
                                            <div className="text-sm font-medium text-gray-500 mb-2">Subtasks Progress</div>
                                            <div className="w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    className="bg-teal-600 h-2 rounded-full"
                                                    style={{ width: `${progressPercentage}%` }}
                                                ></div>
                                            </div>
                                            <div className="text-xs text-gray-500 mt-1">
                                                {subtaskStats.done} of {subtaskStats.total} completed
                                            </div>
                                        </div>
                                    )}

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <div className="text-sm text-gray-500">Total Time</div>
                                            <div className="text-2xl font-bold text-gray-900">{totalTime}h</div>
                                        </div>
                                        {task.estimated_hours && (
                                            <div>
                                                <div className="text-sm text-gray-500">Estimated</div>
                                                <div className="text-2xl font-bold text-gray-900">{task.estimated_hours}h</div>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </SectionLayout>
    );
}

