import React, { forwardRef } from 'react';
import { cn } from '@/lib/utils';

export const Textarea = forwardRef(({ 
    label,
    required = false,
    error,
    rows = 4,
    className = '',
    ...props 
}, ref) => {
    return (
        <div className="w-full">
            {label && (
                <label className="block text-sm font-medium text-gray-700 mb-1.5">
                    {label}
                    {required && <span className="text-red-500 ml-1">*</span>}
                </label>
            )}
            <textarea
                ref={ref}
                rows={rows}
                className={cn(
                    'w-full px-4 py-2.5 rounded-xl border bg-white text-gray-900 resize-none',
                    'placeholder:text-gray-400',
                    'focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500',
                    'transition-all duration-200',
                    error ? 'border-red-300 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300',
                    className
                )}
                {...props}
            />
            {error && (
                <p className="mt-1.5 text-sm text-red-500">{error}</p>
            )}
        </div>
    );
});

Textarea.displayName = 'Textarea';

export default Textarea;

