import React from 'react';
import { cn } from '@/lib/utils';

const variants = {
    primary: 'bg-teal-500',
    positive: 'bg-green-500',
    warning: 'bg-amber-500',
    danger: 'bg-red-500',
};

const trackColors = {
    primary: 'bg-teal-100',
    positive: 'bg-green-100',
    warning: 'bg-amber-100',
    danger: 'bg-red-100',
};

const sizes = {
    xs: 'h-1',
    sm: 'h-1.5',
    md: 'h-2',
    lg: 'h-3',
};

export function ProgressBar({ value = 0, max = 100, variant = 'primary', size = 'md', showLabel = false, animated = false, className = '' }) {
    const percentage = Math.min(Math.max((value / max) * 100, 0), 100);
    return (
        <div className={cn('w-full', className)}>
            {showLabel && (
                <div className="flex justify-between text-sm text-gray-600 mb-1">
                    <span>{value}</span>
                    <span>{percentage.toFixed(0)}%</span>
                </div>
            )}
            <div className={cn('w-full rounded-full overflow-hidden', trackColors[variant], sizes[size])}>
                <div
                    className={cn('h-full rounded-full transition-all duration-500 ease-out', variants[variant], animated && 'animate-pulse')}
                    style={{ width: `${percentage}%` }}
                />
            </div>
        </div>
    );
}

export default ProgressBar;
