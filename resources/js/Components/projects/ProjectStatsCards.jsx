import React, { useState } from 'react';
import { Card, CardContent } from '@/Components/ui/Card';
import { CheckCircle2, Clock, DollarSign, User, AlertTriangle, Target } from 'lucide-react';
import { ProjectDetailsModal } from './ProjectDetailsModal';

/**
 * Modular Project Stats Cards Component
 * Can be used anywhere in the system to display project statistics
 */
export function ProjectStatsCards({ stats, className = '' }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [modalType, setModalType] = useState(null);
    const [modalTitle, setModalTitle] = useState('');

    if (!stats) return null;

    const handleCardClick = (type, title) => {
        setModalType(type);
        setModalTitle(title);
        setModalOpen(true);
    };

    return (
        <>
            <div className={`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 ${className}`}>
                <Card 
                    className="cursor-pointer hover:shadow-lg transition-shadow"
                    onClick={() => handleCardClick('all_projects', 'All Projects')}
                >
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Total Projects</p>
                                <p className="text-3xl font-bold text-blue-500">{stats.total_projects || 0}</p>
                            </div>
                            <div className="p-3 bg-blue-500/10 rounded-lg">
                                <Target className="h-6 w-6 text-blue-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card 
                    className="cursor-pointer hover:shadow-lg transition-shadow"
                    onClick={() => handleCardClick('active_projects', 'Active Projects')}
                >
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Active Projects</p>
                                <p className="text-3xl font-bold text-green-500">{stats.active_projects || 0}</p>
                            </div>
                            <div className="p-3 bg-green-500/10 rounded-lg">
                                <Clock className="h-6 w-6 text-green-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card 
                    className="cursor-pointer hover:shadow-lg transition-shadow"
                    onClick={() => handleCardClick('completed_projects', 'Completed Projects')}
                >
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Completed</p>
                                <p className="text-3xl font-bold text-teal-500">{stats.completed_projects || 0}</p>
                            </div>
                            <div className="p-3 bg-teal-500/10 rounded-lg">
                                <CheckCircle2 className="h-6 w-6 text-teal-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card 
                    className="cursor-pointer hover:shadow-lg transition-shadow"
                    onClick={() => handleCardClick('overdue_projects', 'Overdue Projects')}
                >
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Overdue</p>
                                <p className="text-3xl font-bold text-red-500">{stats.overdue_projects || 0}</p>
                            </div>
                            <div className="p-3 bg-red-500/10 rounded-lg">
                                <AlertTriangle className="h-6 w-6 text-red-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <ProjectDetailsModal
                isOpen={modalOpen}
                onClose={() => setModalOpen(false)}
                title={modalTitle}
                type={modalType}
            />
        </>
    );
}

/**
 * Modular Task Stats Card Component
 */
export function TaskStatsCard({ taskStats, className = '' }) {
    const [modalOpen, setModalOpen] = useState(false);

    if (!taskStats) return null;

    const completionRate = taskStats.total > 0 
        ? Math.round((taskStats.done / taskStats.total) * 100) 
        : 0;

    const handleCardClick = () => {
        setModalOpen(true);
    };

    return (
        <>
            <Card 
                className={`cursor-pointer hover:shadow-lg transition-shadow ${className}`}
                onClick={handleCardClick}
            >
            <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm font-medium text-gray-600 mb-1">Tasks</p>
                        <p className="text-2xl font-bold text-gray-900">
                            {taskStats.done || 0} / {taskStats.total || 0}
                        </p>
                        <p className="text-xs text-gray-500 mt-1">
                            {completionRate}% completed
                        </p>
                    </div>
                    <div className="p-3 bg-purple-500/10 rounded-lg">
                        <CheckCircle2 className="h-6 w-6 text-purple-500" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <ProjectDetailsModal
            isOpen={modalOpen}
            onClose={() => setModalOpen(false)}
            title="All Tasks"
            type="tasks"
        />
        </>
    );
}

/**
 * Modular Budget Stats Card Component
 */
export function BudgetStatsCard({ totalBudget, totalSpent, className = '' }) {
    const [modalOpen, setModalOpen] = useState(false);

    const utilization = totalBudget > 0 
        ? Math.round((totalSpent / totalBudget) * 100) 
        : 0;

    const handleCardClick = () => {
        setModalOpen(true);
    };

    return (
        <>
            <Card 
                className={`cursor-pointer hover:shadow-lg transition-shadow ${className}`}
                onClick={handleCardClick}
            >
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
                            {utilization}% spent
                        </p>
                    </div>
                    <div className="p-3 bg-amber-500/10 rounded-lg">
                        <DollarSign className="h-6 w-6 text-amber-500" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <ProjectDetailsModal
            isOpen={modalOpen}
            onClose={() => setModalOpen(false)}
            title="Budget Details"
            type="budget"
        />
        </>
    );
}

/**
 * Modular Time Stats Card Component
 */
export function TimeStatsCard({ totalTime, className = '' }) {
    const [modalOpen, setModalOpen] = useState(false);

    const handleCardClick = () => {
        setModalOpen(true);
    };

    return (
        <>
            <Card 
                className={`cursor-pointer hover:shadow-lg transition-shadow ${className}`}
                onClick={handleCardClick}
            >
            <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm font-medium text-gray-600 mb-1">Time Logged</p>
                        <p className="text-2xl font-bold text-gray-900">{totalTime || 0}h</p>
                        <p className="text-xs text-gray-500 mt-1">Total hours</p>
                    </div>
                    <div className="p-3 bg-green-500/10 rounded-lg">
                        <Clock className="h-6 w-6 text-green-500" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <ProjectDetailsModal
            isOpen={modalOpen}
            onClose={() => setModalOpen(false)}
            title="Time Tracking Details"
            type="time"
        />
        </>
    );
}

