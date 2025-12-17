import React from 'react';
import { cn } from '@/lib/utils';
import { Loader2 } from 'lucide-react';

const variants = {
    primary: 'bg-teal-500 text-white hover:bg-teal-600 shadow-sm hover:shadow-md border-transparent',
    secondary: 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 hover:border-gray-400',
    ghost: 'bg-transparent text-gray-600 hover:bg-gray-100 hover:text-gray-900 border-transparent',
    danger: 'bg-red-500 text-white hover:bg-red-600 shadow-sm hover:shadow-md border-transparent',
    feature: 'bg-gradient-to-r from-teal-500 to-teal-600 text-white hover:from-teal-600 hover:to-teal-700 shadow-md hover:shadow-lg border-transparent',
    mint: 'bg-mint-100 text-teal-700 hover:bg-mint-200 border-mint-200',
    outline: 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50',
    success: 'bg-green-500 text-white hover:bg-green-600 border-transparent',
};

const sizes = {
    xs: 'px-2.5 py-1 text-xs',
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base',
};

export function Button({ 
    variant = 'primary', 
    size = 'md', 
    icon: Icon,
    iconPosition = 'left',
    loading = false,
    disabled = false,
    className = '', 
    children,
    ...props 
}) {
    const isDisabled = disabled || loading;

    return (
        <button
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-xl font-semibold border transition-all duration-200',
                variants[variant],
                sizes[size],
                isDisabled && 'opacity-50 cursor-not-allowed',
                className
            )}
            disabled={isDisabled}
            {...props}
        >
            {loading ? (
                <Loader2 className="w-4 h-4 animate-spin" />
            ) : (
                Icon && iconPosition === 'left' && <Icon className="w-4 h-4" />
            )}
            {children}
            {!loading && Icon && iconPosition === 'right' && <Icon className="w-4 h-4" />}
        </button>
    );
}

export function IconButton({ 
    icon: Icon,
    variant = 'ghost',
    size = 'md',
    loading = false,
    disabled = false,
    className = '',
    ...props 
}) {
    const iconSizes = {
        xs: 'w-6 h-6',
        sm: 'w-8 h-8',
        md: 'w-10 h-10',
        lg: 'w-12 h-12',
    };

    const iconInnerSizes = {
        xs: 'w-3 h-3',
        sm: 'w-4 h-4',
        md: 'w-5 h-5',
        lg: 'w-6 h-6',
    };

    const isDisabled = disabled || loading;

    return (
        <button
            className={cn(
                'inline-flex items-center justify-center rounded-xl border transition-all duration-200',
                variants[variant],
                iconSizes[size],
                isDisabled && 'opacity-50 cursor-not-allowed',
                className
            )}
            disabled={isDisabled}
            {...props}
        >
            {loading ? (
                <Loader2 className={cn('animate-spin', iconInnerSizes[size])} />
            ) : (
                Icon && <Icon className={iconInnerSizes[size]} />
            )}
        </button>
    );
}

export default Button;
