import { Head, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { ReportFilters } from '@/Components/reports/ReportFilters';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

export default function ProjectPerformanceReport({ projects, dateFrom, dateTo }) {
    const [filters, setFilters] = useState({ dateFrom, dateTo });

    const handleFilterChange = (key, value) => {
        const newFilters = { ...filters, [key]: value };
        setFilters(newFilters);
        router.get('/projects/reports/performance', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Project Performance Report" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-4">
                        <button
                            onClick={() => router.visit('/projects/reports')}
                            className="flex items-center gap-2 text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Reports
                        </button>
                        <h1 className="text-3xl font-bold text-gray-900">Project Performance Report</h1>
                    </div>
                </div>

                {/* Filters - Using Modular Component */}
                <ReportFilters
                    filters={filters}
                    onFilterChange={handleFilterChange}
                    filterConfig={[
                        { key: 'dateFrom', label: 'Date From', type: 'date' },
                        { key: 'dateTo', label: 'Date To', type: 'date' },
                    ]}
                />

                {/* Projects Table */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-200">
                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">Project</th>
                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                        <th className="text-right py-3 px-4 font-semibold text-gray-700">Progress</th>
                                        <th className="text-right py-3 px-4 font-semibold text-gray-700">Tasks</th>
                                        <th className="text-right py-3 px-4 font-semibold text-gray-700">Task Completion</th>
                                        <th className="text-right py-3 px-4 font-semibold text-gray-700">Time Logged</th>
                                        <th className="text-right py-3 px-4 font-semibold text-gray-700">Milestones</th>
                                        <th className="text-right py-3 px-4 font-semibold text-gray-700">Budget Utilization</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {projects && projects.length > 0 ? (
                                        projects.map((project) => (
                                            <tr key={project.id} className="border-b border-gray-100 hover:bg-gray-50">
                                                <td className="py-3 px-4">
                                                    <div className="font-medium text-gray-900">{project.name}</div>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                        project.status === 'active' ? 'bg-green-100 text-green-700' :
                                                        project.status === 'completed' ? 'bg-blue-100 text-blue-700' :
                                                        'bg-gray-100 text-gray-700'
                                                    }`}>
                                                        {project.status}
                                                    </span>
                                                </td>
                                                <td className="py-3 px-4 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <div className="w-24 bg-gray-200 rounded-full h-2">
                                                            <div
                                                                className="bg-blue-500 h-2 rounded-full"
                                                                style={{ width: `${project.progress_percentage}%` }}
                                                            />
                                                        </div>
                                                        <span className="text-sm font-medium">{project.progress_percentage}%</span>
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4 text-right text-sm">
                                                    {project.completed_tasks} / {project.total_tasks}
                                                </td>
                                                <td className="py-3 px-4 text-right text-sm font-medium">
                                                    {project.task_completion_rate}%
                                                </td>
                                                <td className="py-3 px-4 text-right text-sm">
                                                    {project.total_time}h
                                                </td>
                                                <td className="py-3 px-4 text-right text-sm">
                                                    {project.milestones_completed} / {project.total_milestones}
                                                </td>
                                                <td className="py-3 px-4 text-right text-sm font-medium">
                                                    {project.budget_utilization}%
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="8" className="py-12 text-center text-gray-500">
                                                No projects found for the selected date range.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SectionLayout>
    );
}

