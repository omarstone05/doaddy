import { Head, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { ReportFilters } from '@/Components/reports/ReportFilters';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

export default function ProjectBudgetReport({ projects, allProjects, selectedProjectId }) {
    const [filters, setFilters] = useState({ projectId: selectedProjectId || '' });

    const handleFilterChange = (key, value) => {
        const newFilters = { ...filters, [key]: value };
        setFilters(newFilters);
        router.get('/projects/reports/budget', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Budget Report" />
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
                        <h1 className="text-3xl font-bold text-gray-900">Budget Report</h1>
                    </div>
                </div>

                {/* Filters - Using Modular Component */}
                <ReportFilters
                    filters={filters}
                    onFilterChange={handleFilterChange}
                    filterConfig={[
                        { 
                            key: 'projectId', 
                            label: 'Project', 
                            type: 'select',
                            options: [
                                { value: '', label: 'All Projects' },
                                ...(allProjects || []).map(p => ({ value: p.id, label: p.name }))
                            ]
                        },
                    ]}
                />

                {/* Projects Budget Table */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-200">
                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">Project</th>
                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                        <th className="text-right py-3 px-4 font-semibold text-gray-700">Total Budget</th>
                                        <th className="text-right py-3 px-4 font-semibold text-gray-700">Spent</th>
                                        <th className="text-right py-3 px-4 font-semibold text-gray-700">Remaining</th>
                                        <th className="text-right py-3 px-4 font-semibold text-gray-700">Utilization</th>
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
                                                <td className="py-3 px-4 text-right font-medium">
                                                    {new Intl.NumberFormat('en-ZM', {
                                                        style: 'currency',
                                                        currency: 'ZMW',
                                                        minimumFractionDigits: 0,
                                                    }).format(project.total_budget)}
                                                </td>
                                                <td className="py-3 px-4 text-right">
                                                    {new Intl.NumberFormat('en-ZM', {
                                                        style: 'currency',
                                                        currency: 'ZMW',
                                                        minimumFractionDigits: 0,
                                                    }).format(project.total_spent)}
                                                </td>
                                                <td className="py-3 px-4 text-right">
                                                    {new Intl.NumberFormat('en-ZM', {
                                                        style: 'currency',
                                                        currency: 'ZMW',
                                                        minimumFractionDigits: 0,
                                                    }).format(project.remaining)}
                                                </td>
                                                <td className="py-3 px-4 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <div className="w-24 bg-gray-200 rounded-full h-2">
                                                            <div
                                                                className={`h-2 rounded-full ${
                                                                    project.utilization_percentage > 100 ? 'bg-red-500' :
                                                                    project.utilization_percentage > 80 ? 'bg-yellow-500' :
                                                                    'bg-green-500'
                                                                }`}
                                                                style={{ width: `${Math.min(project.utilization_percentage, 100)}%` }}
                                                            />
                                                        </div>
                                                        <span className="text-sm font-medium">{project.utilization_percentage}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="6" className="py-12 text-center text-gray-500">
                                                No projects found.
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

