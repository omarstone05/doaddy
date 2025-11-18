import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card, CardContent } from '@/Components/ui/Card';
import { ProjectTabs } from '@/Components/projects/ProjectTabs';
import { ProjectCard } from '@/Components/projects/ProjectCard';
import { TaskCard } from '@/Components/projects/TaskCard';
import { MilestoneCard } from '@/Components/projects/MilestoneCard';
import { ArrowLeft, Edit, Calendar, User, DollarSign, Clock, CheckCircle2, AlertTriangle } from 'lucide-react';
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function ProjectsShow({ project, taskStats, totalTime, totalBudget, totalSpent, activeTab = 'overview', users }) {
    const [tasks, setTasks] = useState(project.tasks || []);
    const [milestones, setMilestones] = useState(project.milestones || []);
    const [members, setMembers] = useState(project.members || []);
    const [timeEntries, setTimeEntries] = useState(project.time_entries || []);
    const [budgets, setBudgets] = useState(project.budgets || []);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (activeTab === 'tasks') {
            loadTasks();
        } else if (activeTab === 'milestones') {
            loadMilestones();
        } else if (activeTab === 'team') {
            loadMembers();
        } else if (activeTab === 'time') {
            loadTimeEntries();
        } else if (activeTab === 'budget') {
            loadBudgets();
        }
    }, [activeTab, project.id]);

    const loadTasks = async () => {
        try {
            const response = await axios.get(`/api/projects/${project.id}/tasks`);
            setTasks(response.data.tasks || []);
        } catch (error) {
            console.error('Error loading tasks:', error);
        }
    };

    const loadMilestones = async () => {
        try {
            const response = await axios.get(`/api/projects/${project.id}/milestones`);
            setMilestones(response.data.milestones || []);
        } catch (error) {
            console.error('Error loading milestones:', error);
        }
    };

    const loadMembers = async () => {
        try {
            const response = await axios.get(`/api/projects/${project.id}/members`);
            setMembers(response.data.members || []);
        } catch (error) {
            console.error('Error loading members:', error);
        }
    };

    const loadTimeEntries = async () => {
        try {
            const response = await axios.get(`/api/projects/${project.id}/time-entries`);
            setTimeEntries(response.data.timeEntries || []);
        } catch (error) {
            console.error('Error loading time entries:', error);
        }
    };

    const loadBudgets = async () => {
        try {
            const response = await axios.get(`/api/projects/${project.id}/budgets`);
            setBudgets(response.data.budgets || []);
        } catch (error) {
            console.error('Error loading budgets:', error);
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

    const renderOverview = () => (
        <div className="space-y-6">
            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Tasks</p>
                                <p className="text-2xl font-bold text-gray-900">
                                    {taskStats?.done || 0} / {taskStats?.total || 0}
                                </p>
                                <p className="text-xs text-gray-500 mt-1">
                                    {taskStats?.total > 0 ? Math.round((taskStats.done / taskStats.total) * 100) : 0}% completed
                                </p>
                            </div>
                            <CheckCircle2 className="h-8 w-8 text-blue-500" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Time Logged</p>
                                <p className="text-2xl font-bold text-gray-900">{totalTime || 0}h</p>
                                <p className="text-xs text-gray-500 mt-1">Total hours</p>
                            </div>
                            <Clock className="h-8 w-8 text-green-500" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Budget</p>
                                <p className="text-2xl font-bold text-gray-900">
                                    {new Intl.NumberFormat('en-ZM', {
                                        style: 'currency',
                                        currency: 'ZMW',
                                        minimumFractionDigits: 0,
                                    }).format(totalBudget || 0)}
                                </p>
                                <p className="text-xs text-gray-500 mt-1">
                                    {totalBudget > 0 ? Math.round((totalSpent / totalBudget) * 100) : 0}% spent
                                </p>
                            </div>
                            <DollarSign className="h-8 w-8 text-amber-500" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Team Members</p>
                                <p className="text-2xl font-bold text-gray-900">{members.length}</p>
                                <p className="text-xs text-gray-500 mt-1">Active members</p>
                            </div>
                            <User className="h-8 w-8 text-purple-500" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Project Details */}
            <Card>
                <CardContent className="pt-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Project Details</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Status</p>
                            <span className={`px-3 py-1 text-sm font-medium rounded-full ${getStatusBadge(project.status)}`}>
                                {project.status.replace('_', ' ').charAt(0).toUpperCase() + project.status.replace('_', ' ').slice(1)}
                            </span>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Priority</p>
                            <span className={`px-3 py-1 text-sm font-medium rounded-full ${getPriorityBadge(project.priority)}`}>
                                {project.priority.charAt(0).toUpperCase() + project.priority.slice(1)}
                            </span>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Project Manager</p>
                            <p className="text-gray-900">{project.project_manager?.name || '-'}</p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Progress</p>
                            <div className="flex items-center gap-2">
                                <div className="flex-1 bg-gray-200 rounded-full h-2">
                                    <div
                                        className="bg-blue-500 h-2 rounded-full"
                                        style={{ width: `${project.progress_percentage}%` }}
                                    />
                                </div>
                                <span className="text-sm font-medium text-gray-900">{project.progress_percentage}%</span>
                            </div>
                        </div>
                        {project.start_date && (
                            <div>
                                <p className="text-sm text-gray-600 mb-1">Start Date</p>
                                <p className="text-gray-900">{new Date(project.start_date).toLocaleDateString()}</p>
                            </div>
                        )}
                        {project.target_completion_date && (
                            <div>
                                <p className="text-sm text-gray-600 mb-1">Target Completion</p>
                                <p className="text-gray-900">{new Date(project.target_completion_date).toLocaleDateString()}</p>
                            </div>
                        )}
                    </div>
                    {project.description && (
                        <div className="mt-6">
                            <p className="text-sm text-gray-600 mb-2">Description</p>
                            <p className="text-gray-900">{project.description}</p>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );

    const renderTasks = () => (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h3 className="text-lg font-semibold text-gray-900">Tasks</h3>
                <Button onClick={() => router.visit(`/projects/${project.id}/tasks/create`)}>
                    Add Task
                </Button>
            </div>
            {tasks.length === 0 ? (
                <Card>
                    <CardContent className="pt-6 text-center py-12">
                        <p className="text-gray-500">No tasks yet. Create your first task to get started.</p>
                    </CardContent>
                </Card>
            ) : (
                <div className="grid grid-cols-1 gap-4">
                    {tasks.map((task) => (
                        <TaskCard key={task.id} task={task} />
                    ))}
                </div>
            )}
        </div>
    );

    const renderMilestones = () => (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h3 className="text-lg font-semibold text-gray-900">Milestones</h3>
                <Button onClick={() => router.visit(`/projects/${project.id}/milestones/create`)}>
                    Add Milestone
                </Button>
            </div>
            {milestones.length === 0 ? (
                <Card>
                    <CardContent className="pt-6 text-center py-12">
                        <p className="text-gray-500">No milestones yet. Create your first milestone to track progress.</p>
                    </CardContent>
                </Card>
            ) : (
                <div className="grid grid-cols-1 gap-4">
                    {milestones.map((milestone) => (
                        <MilestoneCard key={milestone.id} milestone={milestone} />
                    ))}
                </div>
            )}
        </div>
    );

    const renderTeam = () => (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h3 className="text-lg font-semibold text-gray-900">Team Members</h3>
                <Button onClick={() => router.visit(`/projects/${project.id}/members/add`)}>
                    Add Member
                </Button>
            </div>
            {members.length === 0 ? (
                <Card>
                    <CardContent className="pt-6 text-center py-12">
                        <p className="text-gray-500">No team members yet. Add members to collaborate on this project.</p>
                    </CardContent>
                </Card>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {members.map((member) => (
                        <Card key={member.id}>
                            <CardContent className="pt-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="font-medium text-gray-900">{member.user?.name || 'Unknown'}</p>
                                        <p className="text-sm text-gray-500">{member.user?.email || ''}</p>
                                        <span className="inline-block mt-2 px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-700">
                                            {member.role}
                                        </span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </div>
    );

    const renderTime = () => (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h3 className="text-lg font-semibold text-gray-900">Time Entries</h3>
                <Button onClick={() => router.visit(`/projects/${project.id}/time/create`)}>
                    Log Time
                </Button>
            </div>
            {timeEntries.length === 0 ? (
                <Card>
                    <CardContent className="pt-6 text-center py-12">
                        <p className="text-gray-500">No time entries yet. Start logging time to track work on this project.</p>
                    </CardContent>
                </Card>
            ) : (
                <div className="space-y-4">
                    {timeEntries.map((entry) => (
                        <Card key={entry.id}>
                            <CardContent className="pt-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="font-medium text-gray-900">{entry.user?.name || 'Unknown'}</p>
                                        <p className="text-sm text-gray-500">{new Date(entry.date).toLocaleDateString()}</p>
                                        {entry.description && (
                                            <p className="text-sm text-gray-600 mt-1">{entry.description}</p>
                                        )}
                                    </div>
                                    <div className="text-right">
                                        <p className="font-semibold text-gray-900">{entry.hours}h</p>
                                        {entry.is_billable && entry.billable_rate && (
                                            <p className="text-sm text-gray-500">
                                                {new Intl.NumberFormat('en-ZM', {
                                                    style: 'currency',
                                                    currency: 'ZMW',
                                                }).format(entry.hours * entry.billable_rate)}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </div>
    );

    const renderBudget = () => (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h3 className="text-lg font-semibold text-gray-900">Budget</h3>
                <Button onClick={() => router.visit(`/projects/${project.id}/budgets/create`)}>
                    Add Budget Line
                </Button>
            </div>
            
            {/* Overall Budget Summary */}
            <Card>
                <CardContent className="pt-6">
                    <h4 className="font-semibold text-gray-900 mb-4">Overall Budget</h4>
                    <div className="grid grid-cols-3 gap-4">
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Total Budget</p>
                            <p className="text-xl font-bold text-gray-900">
                                {new Intl.NumberFormat('en-ZM', {
                                    style: 'currency',
                                    currency: 'ZMW',
                                }).format(totalBudget || 0)}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Spent</p>
                            <p className="text-xl font-bold text-gray-900">
                                {new Intl.NumberFormat('en-ZM', {
                                    style: 'currency',
                                    currency: 'ZMW',
                                }).format(totalSpent || 0)}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Remaining</p>
                            <p className="text-xl font-bold text-gray-900">
                                {new Intl.NumberFormat('en-ZM', {
                                    style: 'currency',
                                    currency: 'ZMW',
                                }).format((totalBudget || 0) - (totalSpent || 0))}
                            </p>
                        </div>
                    </div>
                    <div className="mt-4">
                        <div className="flex justify-between text-sm mb-1">
                            <span className="text-gray-600">Budget Utilization</span>
                            <span className="font-medium">
                                {totalBudget > 0 ? Math.round((totalSpent / totalBudget) * 100) : 0}%
                            </span>
                        </div>
                        <div className="w-full bg-gray-200 rounded-full h-2">
                            <div
                                className="bg-blue-500 h-2 rounded-full"
                                style={{ width: `${totalBudget > 0 ? (totalSpent / totalBudget) * 100 : 0}%` }}
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Budget Lines */}
            {budgets.length > 0 && (
                <div className="space-y-4">
                    <h4 className="font-semibold text-gray-900">Budget Lines</h4>
                    {budgets.map((budget) => (
                        <Card key={budget.id}>
                            <CardContent className="pt-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="font-medium text-gray-900">{budget.name}</p>
                                        {budget.description && (
                                            <p className="text-sm text-gray-500 mt-1">{budget.description}</p>
                                        )}
                                        {budget.category && (
                                            <span className="inline-block mt-2 px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-700">
                                                {budget.category}
                                            </span>
                                        )}
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm text-gray-600">Allocated</p>
                                        <p className="font-semibold text-gray-900">
                                            {new Intl.NumberFormat('en-ZM', {
                                                style: 'currency',
                                                currency: 'ZMW',
                                            }).format(budget.allocated_amount || 0)}
                                        </p>
                                        <p className="text-sm text-gray-600 mt-1">Spent</p>
                                        <p className="font-semibold text-gray-900">
                                            {new Intl.NumberFormat('en-ZM', {
                                                style: 'currency',
                                                currency: 'ZMW',
                                            }).format(budget.spent_amount || 0)}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </div>
    );

    const renderFiles = () => (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h3 className="text-lg font-semibold text-gray-900">Files & Attachments</h3>
                <Button onClick={() => router.visit(`/projects/${project.id}/files/upload`)}>
                    Upload File
                </Button>
            </div>
            <Card>
                <CardContent className="pt-6 text-center py-12">
                    <p className="text-gray-500">File management coming soon. You'll be able to upload and manage project files here.</p>
                </CardContent>
            </Card>
        </div>
    );

    const renderActivity = () => (
        <div className="space-y-4">
            <h3 className="text-lg font-semibold text-gray-900">Activity Log</h3>
            <Card>
                <CardContent className="pt-6 text-center py-12">
                    <p className="text-gray-500">Activity log coming soon. Track all project activities and changes here.</p>
                </CardContent>
            </Card>
        </div>
    );

    const renderTabContent = () => {
        switch (activeTab) {
            case 'tasks':
                return renderTasks();
            case 'milestones':
                return renderMilestones();
            case 'team':
                return renderTeam();
            case 'time':
                return renderTime();
            case 'budget':
                return renderBudget();
            case 'files':
                return renderFiles();
            case 'activity':
                return renderActivity();
            default:
                return renderOverview();
        }
    };

    return (
        <SectionLayout sectionName="Decisions">
            <Head title={project.name} />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <Link href="/projects/section">
                        <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900">
                            <ArrowLeft className="h-4 w-4" />
                            Back to Projects
                        </button>
                    </Link>
                    <Link href={`/projects/${project.id}/edit`}>
                        <Button>
                            <Edit className="h-4 w-4 mr-2" />
                            Edit Project
                        </Button>
                    </Link>
                </div>

                {/* Project Header */}
                <Card className="mb-6">
                    <CardContent className="pt-6">
                        <div className="flex items-start justify-between">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900">{project.name}</h1>
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
                    </CardContent>
                </Card>

                {/* Tabs */}
                <ProjectTabs projectId={project.id} activeTab={activeTab} />

                {/* Tab Content */}
                {renderTabContent()}
            </div>
        </SectionLayout>
    );
}
