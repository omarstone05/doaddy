import React from 'react';
import { cn } from '@/lib/utils';
import { TrendingUp, TrendingDown } from 'lucide-react';

const variants = {
    positive: 'bg-teal-50 text-teal-700 border-teal-200',
    negative: 'bg-red-50 text-red-700 border-red-200',
    warning: 'bg-amber-50 text-amber-700 border-amber-200',
    neutral: 'bg-gray-100 text-gray-700 border-gray-200',
    feature: 'bg-teal-500 text-white border-teal-600',
};

const sizes = {
    xs: 'px-1.5 py-0.5 text-[10px]',
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-1 text-sm',
};

export function Badge({ children, variant = 'neutral', size = 'sm', className = '' }) {
    return (
        <span className={cn(
            'inline-flex items-center font-medium rounded-full border',
            variants[variant],
            sizes[size],
            className
        )}>
            {children}
        </span>
    );
}

export function ChangeBadge({ value, type = 'positive', showIcon = true, className = '' }) {
    const isPositive = type === 'positive';
    const Icon = isPositive ? TrendingUp : TrendingDown;
    return (
        <span className={cn(
            'inline-flex items-center gap-1 text-sm font-semibold',
            isPositive ? 'text-teal-600' : 'text-red-500',
            className
        )}>
            {showIcon && <Icon className="w-3.5 h-3.5" />}
            {value}
        </span>
    );
}

export function StatusDot({ status = 'active', size = 'md', pulse = false, className = '' }) {
    const statusColors = {
        active: 'bg-teal-500',
        success: 'bg-green-500',
        paused: 'bg-gray-400',
        warning: 'bg-amber-500',
        completed: 'bg-blue-500',
        critical: 'bg-red-500',
        error: 'bg-red-500',
    };
    const dotSizes = {
        sm: 'w-1.5 h-1.5',
        md: 'w-2 h-2',
        lg: 'w-2.5 h-2.5',
    };
    return (
        <span className={cn(
            'inline-block rounded-full',
            statusColors[status],
            dotSizes[size],
            pulse && 'animate-pulse',
            className
        )} />
    );
}

export default Badge;
