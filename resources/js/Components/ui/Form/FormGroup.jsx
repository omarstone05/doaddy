import React from 'react';
import { cn } from '@/lib/utils';

const columnClasses = {
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
};

export function FormGroup({ 
    columns = 1,
    gap = 4,
    className = '',
    children,
}) {
    return (
        <div className={cn(
            'grid',
            columnClasses[columns],
            `gap-${gap}`,
            className
        )}>
            {children}
        </div>
    );
}

const alignClasses = {
    left: 'justify-start',
    center: 'justify-center',
    right: 'justify-end',
    between: 'justify-between',
};

export function FormActions({ 
    align = 'right',
    className = '',
    children,
}) {
    return (
        <div className={cn(
            'flex items-center gap-3 pt-4',
            alignClasses[align],
            className
        )}>
            {children}
        </div>
    );
}

export default FormGroup;

