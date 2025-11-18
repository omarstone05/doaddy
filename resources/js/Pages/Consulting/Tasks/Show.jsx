import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ArrowLeft, Edit, Calendar, User, Clock, CheckSquare, MessageSquare, Paperclip, Plus, X, Bell, BellOff, Users, CheckCircle2, Circle, FileText, Download } from 'lucide-react';
import axios from 'axios';

export default function Show({ auth, project, task, users }) {
    const [showCommentForm, setShowCommentForm] = useState(false);
    const [showStepForm, setShowStepForm] = useState(false);
    const [replyingTo, setReplyingTo] = useState(null);
    const [editingDueDate, setEditingDueDate] = useState(false);
    const [editingAssignee, setEditingAssignee] = useState(false);
    const [editingTeam, setEditingTeam] = useState(false);
    const [selectedFiles, setSelectedFiles] = useState([]);

    const commentForm = useForm({
        comment: '',
        is_internal: false,
        parent_comment_id: null,
        attachments: [],
    });

    const stepForm = useForm({
        title: '',
        description: '',
    });

    const dueDateForm = useForm({
        due_date: task.due_date || '',
    });

    const assigneeForm = useForm({
        assigned_to_id: task.assigned_to_id || '',
    });

    const teamForm = useForm({
        assigned_team: task.assigned_team?.map(u => u.id) || [],
    });

    const isFollowing = task.followers?.some(f => f.id === auth.user.id) || false;

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

    const formatFileSize = (bytes) => {
        if (!bytes) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    };

    const handleCommentSubmit = (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('comment', commentForm.data.comment);
        formData.append('is_internal', commentForm.data.is_internal ? '1' : '0');
        if (replyingTo) {
            formData.append('parent_comment_id', replyingTo);
        }
        selectedFiles.forEach(file => {
            formData.append('attachments[]', file);
        });

        router.post(route('consulting.projects.tasks.comments.store', [project.id, task.id]), formData, {
            forceFormData: true,
            onSuccess: () => {
                commentForm.reset();
                setShowCommentForm(false);
                setReplyingTo(null);
                setSelectedFiles([]);
            },
        });
    };

    const handleStepSubmit = (e) => {
        e.preventDefault();
        stepForm.post(route('consulting.projects.tasks.steps.store', [project.id, task.id]), {
            onSuccess: () => {
                stepForm.reset();
                setShowStepForm(false);
            },
        });
    };

    const handleToggleStep = (stepId) => {
        router.patch(route('consulting.projects.tasks.steps.toggle', [project.id, task.id, stepId]), {}, {
            preserveScroll: true,
        });
    };

    const handleToggleFollower = () => {
        router.post(route('consulting.projects.tasks.toggle-follower', [project.id, task.id]), {}, {
            preserveScroll: true,
        });
    };

    const handleDueDateUpdate = (e) => {
        e.preventDefault();
        dueDateForm.patch(route('consulting.projects.tasks.update-due-date', [project.id, task.id]), {
            onSuccess: () => {
                setEditingDueDate(false);
            },
        });
    };

    const handleAssigneeUpdate = (e) => {
        e.preventDefault();
        router.put(route('consulting.projects.tasks.update', [project.id, task.id]), {
            assigned_to_id: assigneeForm.data.assigned_to_id,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setEditingAssignee(false);
            },
        });
    };

    const handleTeamUpdate = (e) => {
        e.preventDefault();
        router.put(route('consulting.projects.tasks.update', [project.id, task.id]), {
            assigned_team: teamForm.data.assigned_team,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setEditingTeam(false);
            },
        });
    };

    const handleFileSelect = (e) => {
        const files = Array.from(e.target.files);
        setSelectedFiles([...selectedFiles, ...files]);
    };

    const removeFile = (index) => {
        setSelectedFiles(selectedFiles.filter((_, i) => i !== index));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link
                            href={route('consulting.projects.tasks.index', project.id)}
                            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            <ArrowLeft size={20} />
                        </Link>
                        <div>
                            <h2 className="text-xl font-semibold leading-tight text-gray-800">
                                {task.title}
                            </h2>
                            <Link
                                href={route('consulting.projects.show', project.id)}
                                className="text-sm text-gray-500 hover:text-teal-600"
                            >
                                {project.name}
                            </Link>
                        </div>
                    </div>
                    <Link
                        href={route('consulting.projects.tasks.edit', [project.id, task.id])}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors"
                    >
                        <Edit size={18} />
                        Edit
                    </Link>
                </div>
            }
        >
            <Head title={task.title} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Task Details */}
                            <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6">
                                <div className="flex items-center gap-3 mb-4">
                                    <CheckSquare 
                                        size={24} 
                                        className={task.status === 'done' ? 'text-green-600' : 'text-gray-400'} 
                                    />
                                    <div className="flex-1">
                                        <h3 className="text-xl font-semibold text-gray-900 mb-2">
                                            {task.title}
                                        </h3>
                                        <div className="flex items-center gap-2">
                                            <span className={`px-3 py-1 rounded-lg text-sm font-medium ${getStatusColor(task.status)}`}>
                                                {task.status.replace('_', ' ')}
                                            </span>
                                            <span className={`text-sm font-medium ${getPriorityColor(task.priority)}`}>
                                                {task.priority} priority
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {task.description && (
                                    <div className="mt-4">
                                        <h4 className="text-sm font-medium text-gray-700 mb-2">Description</h4>
                                        <p className="text-gray-600 whitespace-pre-wrap">{task.description}</p>
                                    </div>
                                )}
                            </div>

                            {/* Task Steps */}
                            <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="text-lg font-semibold text-gray-900">Steps</h3>
                                    <button
                                        onClick={() => setShowStepForm(!showStepForm)}
                                        className="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors"
                                    >
                                        <Plus size={16} />
                                        Add Step
                                    </button>
                                </div>

                                {showStepForm && (
                                    <form onSubmit={handleStepSubmit} className="mb-4 p-4 bg-gray-50 rounded-lg">
                                        <div className="space-y-3">
                                            <input
                                                type="text"
                                                placeholder="Step title"
                                                value={stepForm.data.title}
                                                onChange={(e) => stepForm.setData('title', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                required
                                            />
                                            <textarea
                                                placeholder="Step description (optional)"
                                                value={stepForm.data.description}
                                                onChange={(e) => stepForm.setData('description', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                rows="2"
                                            />
                                            <div className="flex gap-2">
                                                <button
                                                    type="submit"
                                                    className="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700"
                                                >
                                                    Add Step
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setShowStepForm(false);
                                                        stepForm.reset();
                                                    }}
                                                    className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                )}

                                <div className="space-y-2">
                                    {task.steps && task.steps.length > 0 ? (
                                        task.steps.map((step) => (
                                            <div
                                                key={step.id}
                                                className="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                                            >
                                                <button
                                                    onClick={() => handleToggleStep(step.id)}
                                                    className="mt-0.5"
                                                >
                                                    {step.is_completed ? (
                                                        <CheckCircle2 size={20} className="text-green-600" />
                                                    ) : (
                                                        <Circle size={20} className="text-gray-400" />
                                                    )}
                                                </button>
                                                <div className="flex-1">
                                                    <div className={`font-medium ${step.is_completed ? 'line-through text-gray-500' : 'text-gray-900'}`}>
                                                        {step.title}
                                                    </div>
                                                    {step.description && (
                                                        <div className="text-sm text-gray-600 mt-1">{step.description}</div>
                                                    )}
                                                    {step.is_completed && step.completed_by_name && (
                                                        <div className="text-xs text-gray-500 mt-1">
                                                            Completed by {step.completed_by_name}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-gray-500 text-sm">No steps added yet</p>
                                    )}
                                </div>
                            </div>

                            {/* Comments Section */}
                            <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="text-lg font-semibold text-gray-900">Comments</h3>
                                    <button
                                        onClick={() => setShowCommentForm(!showCommentForm)}
                                        className="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors"
                                    >
                                        <MessageSquare size={16} />
                                        Add Comment
                                    </button>
                                </div>

                                {showCommentForm && (
                                    <form onSubmit={handleCommentSubmit} className="mb-6 p-4 bg-gray-50 rounded-lg">
                                        <div className="space-y-3">
                                            <textarea
                                                placeholder="Write a comment..."
                                                value={commentForm.data.comment}
                                                onChange={(e) => commentForm.setData('comment', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                                rows="4"
                                                required
                                            />
                                            <div className="flex items-center gap-2">
                                                <label className="flex items-center gap-2 text-sm text-gray-700">
                                                    <input
                                                        type="checkbox"
                                                        checked={commentForm.data.is_internal}
                                                        onChange={(e) => commentForm.setData('is_internal', e.target.checked)}
                                                        className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                                    />
                                                    Internal note
                                                </label>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Attachments
                                                </label>
                                                <input
                                                    type="file"
                                                    multiple
                                                    onChange={handleFileSelect}
                                                    className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100"
                                                />
                                                {selectedFiles.length > 0 && (
                                                    <div className="mt-2 space-y-1">
                                                        {selectedFiles.map((file, index) => (
                                                            <div key={index} className="flex items-center gap-2 text-sm text-gray-600 bg-white p-2 rounded">
                                                                <Paperclip size={14} />
                                                                <span className="flex-1 truncate">{file.name}</span>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => removeFile(index)}
                                                                    className="text-red-600 hover:text-red-700"
                                                                >
                                                                    <X size={14} />
                                                                </button>
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                            <div className="flex gap-2">
                                                <button
                                                    type="submit"
                                                    className="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700"
                                                >
                                                    Post Comment
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setShowCommentForm(false);
                                                        setReplyingTo(null);
                                                        commentForm.reset();
                                                        setSelectedFiles([]);
                                                    }}
                                                    className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                )}

                                <div className="space-y-4">
                                    {task.comments && task.comments.length > 0 ? (
                                        task.comments.map((comment) => (
                                            <div key={comment.id} className="border-b border-gray-200 pb-4 last:border-0">
                                                <div className="flex items-start gap-3">
                                                    <div className="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center text-teal-700 font-semibold text-sm">
                                                        {comment.user_name.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-2 mb-1">
                                                            <span className="font-semibold text-gray-900">{comment.user_name}</span>
                                                            {comment.is_internal && (
                                                                <span className="px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded">Internal</span>
                                                            )}
                                                            <span className="text-xs text-gray-500">{comment.created_at_human}</span>
                                                        </div>
                                                        <p className="text-gray-700 whitespace-pre-wrap mb-2">{comment.comment}</p>
                                                        
                                                        {comment.attachments && comment.attachments.length > 0 && (
                                                            <div className="space-y-1 mb-2">
                                                                {comment.attachments.map((attachment) => (
                                                                    <a
                                                                        key={attachment.id}
                                                                        href={attachment.file_url}
                                                                        target="_blank"
                                                                        rel="noopener noreferrer"
                                                                        className="inline-flex items-center gap-2 text-sm text-teal-600 hover:text-teal-700 bg-teal-50 px-3 py-1.5 rounded-lg"
                                                                    >
                                                                        <FileText size={14} />
                                                                        <span>{attachment.original_name}</span>
                                                                        <span className="text-gray-500">({formatFileSize(attachment.file_size)})</span>
                                                                        <Download size={12} />
                                                                    </a>
                                                                ))}
                                                            </div>
                                                        )}

                                                        <button
                                                            onClick={() => {
                                                                setReplyingTo(replyingTo === comment.id ? null : comment.id);
                                                                setShowCommentForm(true);
                                                            }}
                                                            className="text-sm text-teal-600 hover:text-teal-700"
                                                        >
                                                            Reply
                                                        </button>

                                                        {comment.replies && comment.replies.length > 0 && (
                                                            <div className="mt-3 ml-4 space-y-2 border-l-2 border-gray-200 pl-4">
                                                                {comment.replies.map((reply) => (
                                                                    <div key={reply.id} className="flex items-start gap-2">
                                                                        <div className="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-semibold text-xs">
                                                                            {reply.user_name.charAt(0).toUpperCase()}
                                                                        </div>
                                                                        <div className="flex-1">
                                                                            <div className="flex items-center gap-2 mb-1">
                                                                                <span className="font-medium text-sm text-gray-900">{reply.user_name}</span>
                                                                                <span className="text-xs text-gray-500">{reply.created_at_human}</span>
                                                                            </div>
                                                                            <p className="text-sm text-gray-700">{reply.comment}</p>
                                                                        </div>
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-gray-500 text-sm">No comments yet</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Task Info */}
                            <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Details</h3>
                                
                                <div className="space-y-4">
                                    {/* Due Date */}
                                    <div>
                                        <div className="flex items-center justify-between mb-1">
                                            <div className="flex items-center gap-2">
                                                <Calendar size={16} className="text-gray-400" />
                                                <span className="text-xs text-gray-500">Due Date</span>
                                            </div>
                                            {!editingDueDate && (
                                                <button
                                                    onClick={() => setEditingDueDate(true)}
                                                    className="text-xs text-teal-600 hover:text-teal-700"
                                                >
                                                    Edit
                                                </button>
                                            )}
                                        </div>
                                        {editingDueDate ? (
                                            <form onSubmit={handleDueDateUpdate} className="space-y-2">
                                                <input
                                                    type="date"
                                                    value={dueDateForm.data.due_date}
                                                    onChange={(e) => dueDateForm.setData('due_date', e.target.value)}
                                                    className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-teal-500"
                                                />
                                                <div className="flex gap-2">
                                                    <button
                                                        type="submit"
                                                        className="px-2 py-1 text-xs bg-teal-600 text-white rounded hover:bg-teal-700"
                                                    >
                                                        Save
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setEditingDueDate(false);
                                                            dueDateForm.reset();
                                                        }}
                                                        className="px-2 py-1 text-xs bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                                                    >
                                                        Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        ) : (
                                            <div className="text-sm font-medium text-gray-900">
                                                {task.due_date ? new Date(task.due_date).toLocaleDateString() : 'No due date'}
                                            </div>
                                        )}
                                    </div>

                                    {/* Estimated Hours */}
                                    {task.estimated_hours && (
                                        <div className="flex items-center gap-2">
                                            <Clock size={16} className="text-gray-400" />
                                            <div>
                                                <div className="text-xs text-gray-500">Estimated Hours</div>
                                                <div className="text-sm font-medium text-gray-900">{task.estimated_hours}h</div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Primary Assignee */}
                                    <div>
                                        <div className="flex items-center justify-between mb-1">
                                            <div className="flex items-center gap-2">
                                                <User size={16} className="text-gray-400" />
                                                <span className="text-xs text-gray-500">Assigned To</span>
                                            </div>
                                            {!editingAssignee && (
                                                <button
                                                    onClick={() => setEditingAssignee(true)}
                                                    className="text-xs text-teal-600 hover:text-teal-700"
                                                >
                                                    Edit
                                                </button>
                                            )}
                                        </div>
                                        {editingAssignee ? (
                                            <form onSubmit={handleAssigneeUpdate} className="space-y-2">
                                                <select
                                                    value={assigneeForm.data.assigned_to_id}
                                                    onChange={(e) => assigneeForm.setData('assigned_to_id', e.target.value)}
                                                    className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-teal-500"
                                                >
                                                    <option value="">Unassigned</option>
                                                    {users.map((user) => (
                                                        <option key={user.id} value={user.id}>{user.name}</option>
                                                    ))}
                                                </select>
                                                <div className="flex gap-2">
                                                    <button
                                                        type="submit"
                                                        className="px-2 py-1 text-xs bg-teal-600 text-white rounded hover:bg-teal-700"
                                                    >
                                                        Save
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setEditingAssignee(false);
                                                            assigneeForm.reset();
                                                        }}
                                                        className="px-2 py-1 text-xs bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                                                    >
                                                        Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        ) : (
                                            <div className="text-sm font-medium text-gray-900">
                                                {task.assigned_to_name || 'Unassigned'}
                                            </div>
                                        )}
                                    </div>

                                    {/* Team Assignees */}
                                    <div>
                                        <div className="flex items-center justify-between mb-1">
                                            <div className="flex items-center gap-2">
                                                <Users size={16} className="text-gray-400" />
                                                <span className="text-xs text-gray-500">Team</span>
                                            </div>
                                            {!editingTeam && (
                                                <button
                                                    onClick={() => setEditingTeam(true)}
                                                    className="text-xs text-teal-600 hover:text-teal-700"
                                                >
                                                    Edit
                                                </button>
                                            )}
                                        </div>
                                        {editingTeam ? (
                                            <form onSubmit={handleTeamUpdate} className="space-y-2">
                                                <select
                                                    multiple
                                                    value={teamForm.data.assigned_team}
                                                    onChange={(e) => {
                                                        const selected = Array.from(e.target.selectedOptions, option => option.value);
                                                        teamForm.setData('assigned_team', selected);
                                                    }}
                                                    className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-teal-500"
                                                    size="4"
                                                >
                                                    {users.map((user) => (
                                                        <option key={user.id} value={user.id}>{user.name}</option>
                                                    ))}
                                                </select>
                                                <div className="text-xs text-gray-500">Hold Ctrl/Cmd to select multiple</div>
                                                <div className="flex gap-2">
                                                    <button
                                                        type="submit"
                                                        className="px-2 py-1 text-xs bg-teal-600 text-white rounded hover:bg-teal-700"
                                                    >
                                                        Save
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setEditingTeam(false);
                                                            teamForm.reset();
                                                        }}
                                                        className="px-2 py-1 text-xs bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                                                    >
                                                        Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        ) : (
                                            <div className="space-y-1">
                                                {task.assigned_team && task.assigned_team.length > 0 ? (
                                                    task.assigned_team.map((member) => (
                                                        <div key={member.id} className="text-sm text-gray-900">
                                                            {member.name}
                                                        </div>
                                                    ))
                                                ) : (
                                                    <div className="text-sm text-gray-500">No team members assigned</div>
                                                )}
                                            </div>
                                        )}
                                    </div>

                                    {/* Followers */}
                                    <div>
                                        <div className="flex items-center justify-between mb-1">
                                            <div className="flex items-center gap-2">
                                                <Bell size={16} className="text-gray-400" />
                                                <span className="text-xs text-gray-500">Followers</span>
                                            </div>
                                            <button
                                                onClick={handleToggleFollower}
                                                className={`text-xs ${isFollowing ? 'text-red-600 hover:text-red-700' : 'text-teal-600 hover:text-teal-700'}`}
                                            >
                                                {isFollowing ? 'Unfollow' : 'Follow'}
                                            </button>
                                        </div>
                                        <div className="space-y-1">
                                            {task.followers && task.followers.length > 0 ? (
                                                task.followers.map((follower) => (
                                                    <div key={follower.id} className="text-sm text-gray-900">
                                                        {follower.name}
                                                    </div>
                                                ))
                                            ) : (
                                                <div className="text-sm text-gray-500">No followers</div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Created By */}
                                    {task.created_by_name && (
                                        <div className="flex items-center gap-2">
                                            <User size={16} className="text-gray-400" />
                                            <div>
                                                <div className="text-xs text-gray-500">Created By</div>
                                                <div className="text-sm font-medium text-gray-900">{task.created_by_name}</div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Created Date */}
                                    {task.created_at && (
                                        <div className="flex items-center gap-2">
                                            <Calendar size={16} className="text-gray-400" />
                                            <div>
                                                <div className="text-xs text-gray-500">Created</div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {new Date(task.created_at).toLocaleDateString()}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
