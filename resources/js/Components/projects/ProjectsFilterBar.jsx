import React from 'react';
import { Card, CardContent } from '@/Components/ui/Card';
import { Search, Filter } from 'lucide-react';

/**
 * Modular Projects Filter Bar Component
 * Can be used anywhere to filter projects
 */
export function ProjectsFilterBar({ 
    filters, 
    onFilterChange, 
    className = '' 
}) {
    return (
        <Card className={`mb-6 ${className}`}>
            <CardContent className="pt-6">
                <div className="flex items-center gap-2 mb-4">
                    <Filter className="h-4 w-4 text-gray-500" />
                    <h3 className="text-sm font-semibold text-gray-700">Filters</h3>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                                type="text"
                                value={filters.search || ''}
                                onChange={(e) => onFilterChange('search', e.target.value)}
                                placeholder="Search projects..."
                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select
                            value={filters.status || ''}
                            onChange={(e) => onFilterChange('status', e.target.value)}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">All Status</option>
                            <option value="planning">Planning</option>
                            <option value="active">Active</option>
                            <option value="on_hold">On Hold</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                        <select
                            value={filters.priority || ''}
                            onChange={(e) => onFilterChange('priority', e.target.value)}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">All Priorities</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

