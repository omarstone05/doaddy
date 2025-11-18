import React from 'react';
import { Link } from '@inertiajs/react';
import { Card, CardContent } from '@/Components/ui/Card';

/**
 * Modular Report Card Component
 * Can be used anywhere to display report options
 */
export function ReportCard({ report, className = '' }) {
    const Icon = report.icon;
    
    return (
        <Link href={report.href} className={className}>
            <Card className="hover:shadow-lg transition-shadow cursor-pointer h-full">
                <CardContent className="pt-6">
                    <div className={`w-12 h-12 ${report.bgColor} rounded-lg flex items-center justify-center mb-4`}>
                        <Icon className={`h-6 w-6 ${report.color}`} />
                    </div>
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">{report.name}</h3>
                    <p className="text-sm text-gray-600">{report.description}</p>
                </CardContent>
            </Card>
        </Link>
    );
}

/**
 * Modular Report Cards Grid Component
 * Displays a grid of report cards
 */
export function ReportCardsGrid({ reports, className = '' }) {
    return (
        <div className={`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 ${className}`}>
            {reports.map((report) => (
                <ReportCard key={report.id} report={report} />
            ))}
        </div>
    );
}

