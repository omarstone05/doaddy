import React from 'react';
import { X, Plus } from 'lucide-react';
import { cn } from '@/lib/utils';

const variants = {
    glass: 'bg-white/90 backdrop-blur-sm border-gray-200/50',
    solid: 'bg-white border-gray-200',
    'gradient-positive': 'bg-gradient-to-br from-teal-50 to-mint-50 border-teal-200/50',
    'gradient-negative': 'bg-gradient-to-br from-red-50 to-orange-50 border-red-200/50',
    feature: 'bg-gradient-to-br from-teal-500 to-teal-600 border-teal-600 text-white',
    mint: 'bg-gradient-to-br from-mint-50 to-teal-50 border-mint-200/50',
};

const paddingClasses = {
    none: 'p-0',
    sm: 'p-4',
    md: 'p-6',
    lg: 'p-8',
};

export function Card({ 
    children, 
    variant = 'glass',
    hover = true,
    padding = 'md',
    onClick,
    dismissible = false,
    onDismiss,
    className = '',
}) {
    return (
        <div 
            className={cn(
                'rounded-2xl border relative',
                'transition-all duration-200',
                variants[variant],
                paddingClasses[padding],
                hover && 'hover:shadow-lg hover:border-teal-200',
                onClick && 'cursor-pointer',
                className
            )}
            onClick={onClick}
        >
            {dismissible && (
                <button
                    onClick={(e) => {
                        e.stopPropagation();
                        onDismiss?.();
                    }}
                    className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors z-10"
                >
                    <X className="h-5 w-5" />
                </button>
            )}
            {children}
        </div>
    );
}

export function CardHeader({ 
    label,
    badge,
    action,
    onAction,
    className = '',
    children,
}) {
    // If children provided, render them directly
    if (children) {
        return (
            <div className={cn('mb-4', className)}>
                {children}
            </div>
        );
    }

    return (
        <div className={cn('flex items-center justify-between mb-4', className)}>
            <span className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {label}
            </span>
            <div className="flex items-center gap-2">
                {badge}
                {action}
                {onAction && (
                    <button
                        onClick={onAction}
                        className="w-6 h-6 rounded-full bg-teal-100 hover:bg-teal-200 flex items-center justify-center transition-colors"
                    >
                        <Plus className="w-3.5 h-3.5 text-teal-600" />
                    </button>
                )}
            </div>
        </div>
    );
}

export function CardTitle({ className, children, ...props }) {
    return (
        <h3 className={cn('text-lg font-semibold text-gray-900', className)} {...props}>
            {children}
        </h3>
    );
}

export function CardContent({ className, children, ...props }) {
    return (
        <div className={cn('', className)} {...props}>
            {children}
        </div>
    );
}

export default Card;
