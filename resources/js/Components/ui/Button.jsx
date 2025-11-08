import React from 'react';
import { cn } from '@/lib/utils';

const buttonVariants = {
    primary: 'bg-teal-500 text-white hover:bg-teal-600 shadow-sm hover:shadow-md',
    success: 'bg-green-500 text-white hover:bg-green-600',
    secondary: 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50',
    ghost: 'bg-transparent text-gray-600 hover:bg-gray-100 hover:text-gray-900',
};

const sizeVariants = {
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2',
    lg: 'px-6 py-3 text-lg',
};

export function Button({ 
    variant = 'primary', 
    size = 'md', 
    className, 
    children,
    ...props 
}) {
    return (
        <button
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-all duration-200',
                buttonVariants[variant],
                sizeVariants[size],
                className
            )}
            {...props}
        >
            {children}
        </button>
    );
}

