import React from 'react';
import { router } from '@inertiajs/react';
import { Card, CardContent } from '@/Components/ui/Card';

/**
 * Modular Report Filters Component
 * Can be used for any report that needs filtering
 */
export function ReportFilters({ filters, onFilterChange, filterConfig, className = '' }) {
    const handleChange = (key, value) => {
        if (onFilterChange) {
            onFilterChange(key, value);
        } else {
            // Default behavior: update URL with new filters
            const newFilters = { ...filters, [key]: value };
            router.get(window.location.pathname, newFilters, {
                preserveState: true,
                preserveScroll: true,
            });
        }
    };

    return (
        <Card className={`mb-6 ${className}`}>
            <CardContent className="pt-6">
                <div className={`grid grid-cols-1 md:grid-cols-${filterConfig.length} gap-4`}>
                    {filterConfig.map((config) => (
                        <div key={config.key}>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                {config.label}
                            </label>
                            {config.type === 'select' ? (
                                <select
                                    value={filters[config.key] || ''}
                                    onChange={(e) => handleChange(config.key, e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                >
                                    {config.options.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            ) : config.type === 'date' ? (
                                <input
                                    type="date"
                                    value={filters[config.key] || ''}
                                    onChange={(e) => handleChange(config.key, e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                />
                            ) : (
                                <input
                                    type={config.type || 'text'}
                                    value={filters[config.key] || ''}
                                    onChange={(e) => handleChange(config.key, e.target.value)}
                                    placeholder={config.placeholder}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                />
                            )}
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

