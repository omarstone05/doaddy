import React from 'react';
import { router } from '@inertiajs/react';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Plus, FolderKanban, TrendingUp, BarChart3 } from 'lucide-react';

/**
 * Modular Project Quick Actions Component
 * Can be used anywhere to show project-related quick actions
 */
export function ProjectQuickActions({ projectId = null, showReports = true, className = '' }) {
    const basePath = projectId ? `/projects/${projectId}` : '/projects';
    
    return (
        <Card className={className}>
            <CardContent className="pt-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Button
                        variant="outline"
                        onClick={() => router.visit('/projects/create')}
                        className="justify-start"
                    >
                        <Plus className="h-4 w-4 mr-2" />
                        Create New Project
                    </Button>
                    <Button
                        variant="outline"
                        onClick={() => router.visit('/projects/section')}
                        className="justify-start"
                    >
                        <FolderKanban className="h-4 w-4 mr-2" />
                        View All Projects
                    </Button>
                    {showReports && (
                        <Button
                            variant="outline"
                            onClick={() => router.visit('/projects/reports')}
                            className="justify-start"
                        >
                            <BarChart3 className="h-4 w-4 mr-2" />
                            View Reports
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

