import React from 'react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Head } from '@inertiajs/react';
import { ProjectQuickActions } from './ProjectQuickActions';

/**
 * Modular Project Section Layout Component
 * Wraps project-related pages with consistent layout and quick actions
 */
export function ProjectSectionLayout({ 
    children, 
    title, 
    description, 
    showQuickActions = true,
    quickActionsProps = {} 
}) {
    return (
        <SectionLayout sectionName="Decisions">
            <Head title={title} />
            <div>
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900">{title}</h1>
                    {description && (
                        <p className="text-gray-500 mt-1">{description}</p>
                    )}
                </div>

                {children}

                {showQuickActions && (
                    <ProjectQuickActions {...quickActionsProps} className="mt-8" />
                )}
            </div>
        </SectionLayout>
    );
}

