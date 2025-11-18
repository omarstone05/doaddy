import React from 'react';
import { Card, CardContent } from '@/Components/ui/Card';

/**
 * Modular Projects Summary Stats Component
 * Displays summary statistics for a list of projects
 */
export function ProjectsSummaryStats({ projects, className = '' }) {
    if (!projects || projects.length === 0) return null;

    const total = projects.length;
    const active = projects.filter(p => p.status === 'active').length;
    const completed = projects.filter(p => p.status === 'completed').length;
    const avgProgress = Math.round(
        projects.reduce((sum, p) => sum + (p.progress_percentage || 0), 0) / total
    ) || 0;

    return (
        <Card className={className}>
            <CardContent className="pt-6">
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div>
                        <p className="text-2xl font-bold text-gray-900">{total}</p>
                        <p className="text-sm text-gray-500">Total Projects</p>
                    </div>
                    <div>
                        <p className="text-2xl font-bold text-green-600">{active}</p>
                        <p className="text-sm text-gray-500">Active</p>
                    </div>
                    <div>
                        <p className="text-2xl font-bold text-blue-600">{completed}</p>
                        <p className="text-sm text-gray-500">Completed</p>
                    </div>
                    <div>
                        <p className="text-2xl font-bold text-gray-600">{avgProgress}%</p>
                        <p className="text-sm text-gray-500">Avg Progress</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

