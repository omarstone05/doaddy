import { Head, Link, router, usePage } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card, CardContent } from '@/Components/ui/Card';
import { 
    ProjectManagementTabs, 
    ProjectsListView, 
    ProjectsFilterBar, 
    ProjectsSummaryStats 
} from '@/Components/projects';
import { Plus, Grid3x3, List } from 'lucide-react';
import { useState } from 'react';

export default function ProjectsIndex({ projects, filters }) {
    const { url } = usePage().props;
    const currentPath = (url || (typeof window !== 'undefined' ? window.location.pathname : '')).replace(/\/$/, '') || '/';
    
    const [viewMode, setViewMode] = useState('grid'); // 'grid' or 'table'
    const [localFilters, setLocalFilters] = useState({
        search: filters?.search || '',
        status: filters?.status || '',
        priority: filters?.priority || '',
    });

    const handleFilterChange = (key, value) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        
        // Build query string
        const params = new URLSearchParams();
        Object.entries(newFilters).forEach(([k, v]) => {
            if (v) params.append(k, v);
        });
        
        router.visit(`/projects?${params.toString()}`, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleDelete = (projectId) => {
        if (confirm('Are you sure you want to delete this project?')) {
            router.delete(`/projects/${projectId}`, {
                preserveScroll: true,
            });
        }
    };


    return (
        <SectionLayout sectionName="Decisions">
            <Head title="All Projects" />
            <div>
                {/* Project Management Tabs */}
                <ProjectManagementTabs currentPath={currentPath} />

                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">All Projects</h1>
                        <p className="text-gray-500 mt-1">Track and manage all your projects</p>
                    </div>
                    <div className="flex items-center gap-3">
                        {/* View Toggle */}
                        <div className="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                            <button
                                onClick={() => setViewMode('grid')}
                                className={`p-2 rounded ${viewMode === 'grid' ? 'bg-white shadow-sm' : ''}`}
                                title="Grid View"
                            >
                                <Grid3x3 className="h-4 w-4" />
                            </button>
                            <button
                                onClick={() => setViewMode('table')}
                                className={`p-2 rounded ${viewMode === 'table' ? 'bg-white shadow-sm' : ''}`}
                                title="Table View"
                            >
                                <List className="h-4 w-4" />
                            </button>
                        </div>
                        <Button onClick={() => router.visit('/projects/create')}>
                            <Plus className="h-4 w-4 mr-2" />
                            New Project
                        </Button>
                    </div>
                </div>

                {/* Filters - Using Modular Component */}
                <ProjectsFilterBar 
                    filters={localFilters}
                    onFilterChange={handleFilterChange}
                />

                {/* Projects Display - Using Modular Component */}
                {projects.data.length === 0 ? (
                    <Card>
                        <CardContent className="pt-6 text-center py-12">
                            <div className="text-gray-400 mb-4">
                                <svg className="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <p className="text-gray-500 text-lg mb-2">No projects found</p>
                            <p className="text-gray-400 text-sm mb-4">
                                {localFilters.search || localFilters.status || localFilters.priority
                                    ? 'Try adjusting your filters'
                                    : 'Create your first project to get started'}
                            </p>
                            {!(localFilters.search || localFilters.status || localFilters.priority) && (
                                <Button onClick={() => router.visit('/projects/create')}>
                                    <Plus className="h-4 w-4 mr-2" />
                                    Create Project
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <ProjectsListView
                        projects={projects.data}
                        viewMode={viewMode}
                        onDelete={handleDelete}
                        showActions={true}
                        className="mb-6"
                    />
                )}

                {/* Pagination */}
                {projects.links && projects.links.length > 3 && (
                    <div className="mt-6 flex items-center justify-between">
                        <div className="text-sm text-gray-500">
                            Showing {projects.from || 0} to {projects.to || 0} of {projects.total || 0} projects
                        </div>
                        <div className="flex gap-2">
                            {projects.links.map((link, index) => (
                                <button
                                    key={index}
                                    onClick={() => link.url && router.visit(link.url)}
                                    disabled={!link.url}
                                    className={`px-3 py-2 text-sm border rounded-lg transition-colors ${
                                        link.active
                                            ? 'bg-blue-500 text-white border-blue-500'
                                            : link.url
                                            ? 'border-gray-300 hover:bg-gray-50 text-gray-700'
                                            : 'border-gray-200 text-gray-400 cursor-not-allowed'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}

                {/* Summary Stats - Using Modular Component */}
                {projects.data.length > 0 && (
                    <ProjectsSummaryStats projects={projects.data} className="mt-6" />
                )}
            </div>
        </SectionLayout>
    );
}
