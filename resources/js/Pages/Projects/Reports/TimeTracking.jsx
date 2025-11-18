import { Head, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { ReportFilters } from '@/Components/reports/ReportFilters';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

export default function ProjectTimeTrackingReport({ byProject, byUser, dailyBreakdown, projects, selectedProjectId, dateFrom, dateTo }) {
    const [filters, setFilters] = useState({ 
        projectId: selectedProjectId || '', 
        dateFrom, 
        dateTo 
    });

    const handleFilterChange = (key, value) => {
        const newFilters = { ...filters, [key]: value };
        setFilters(newFilters);
        router.get('/projects/reports/time-tracking', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <SectionLayout sectionName="Decisions">
            <Head title="Time Tracking Report" />
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
                        <h1 className="text-3xl font-bold text-gray-900">Time Tracking Report</h1>
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
                                ...(projects || []).map(p => ({ value: p.id, label: p.name }))
                            ]
                        },
                        { key: 'dateFrom', label: 'Date From', type: 'date' },
                        { key: 'dateTo', label: 'Date To', type: 'date' },
                    ]}
                />

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* By Project */}
                    <Card>
                        <CardContent className="pt-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Time by Project</h3>
                            {byProject && byProject.length > 0 ? (
                                <div className="space-y-4">
                                    {byProject.map((item) => (
                                        <div key={item.project_id} className="border-b border-gray-100 pb-4 last:border-0">
                                            <div className="flex items-center justify-between mb-2">
                                                <span className="font-medium text-gray-900">{item.project_name}</span>
                                                <span className="text-sm font-semibold text-gray-900">{item.total_hours}h</span>
                                            </div>
                                            <div className="text-sm text-gray-600">
                                                {item.billable_hours}h billable • {item.entries_count} entries
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-gray-500 text-center py-8">No time entries found</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* By User */}
                    <Card>
                        <CardContent className="pt-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Time by User</h3>
                            {byUser && byUser.length > 0 ? (
                                <div className="space-y-4">
                                    {byUser.map((item) => (
                                        <div key={item.user_id} className="border-b border-gray-100 pb-4 last:border-0">
                                            <div className="flex items-center justify-between mb-2">
                                                <span className="font-medium text-gray-900">{item.user_name}</span>
                                                <span className="text-sm font-semibold text-gray-900">{item.total_hours}h</span>
                                            </div>
                                            <div className="text-sm text-gray-600">
                                                {item.billable_hours}h billable • {item.entries_count} entries
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-gray-500 text-center py-8">No time entries found</p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </SectionLayout>
    );
}

