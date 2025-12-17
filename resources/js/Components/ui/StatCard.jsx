import React from 'react';
import { cn } from '@/lib/utils';
import { ArrowUpRight, ArrowDownRight } from 'lucide-react';

const formatValue = (value, prefix = '', suffix = '') => {
    if (typeof value === 'number') {
        return `${prefix}${value.toLocaleString()}${suffix}`;
    }
    return `${prefix}${value}${suffix}`;
};

export function StatCard({ 
    title, 
    value, 
    prefix = '', 
    suffix = '', 
    change, 
    changeType = 'auto', // 'auto', 'positive', 'negative', 'neutral'
    subtitle,
    icon: Icon,
    variant = 'glass', // 'glass', 'gradient-positive', 'gradient-negative', 'highlight', 'feature'
    className = '',
}) {
    // Determine change color
    let changeColor = 'text-gray-600';
    let changeBg = 'bg-gray-100';
    
    if (changeType === 'auto' && typeof change === 'number') {
        if (change > 0) {
            changeColor = 'text-teal-600';
            changeBg = 'bg-teal-50';
        } else if (change < 0) {
            changeColor = 'text-red-600';
            changeBg = 'bg-red-50';
        }
    } else if (changeType === 'positive') {
        changeColor = 'text-teal-600';
        changeBg = 'bg-teal-50';
    } else if (changeType === 'negative') {
        changeColor = 'text-red-600';
        changeBg = 'bg-red-50';
    }

    const ChangeIcon = change >= 0 ? ArrowUpRight : ArrowDownRight;

    // Variant styles
    const variantStyles = {
        glass: 'bg-white/90 backdrop-blur-sm border border-gray-200/50 hover:border-teal-200',
        solid: 'bg-white border border-gray-200',
        'gradient-positive': 'bg-gradient-to-br from-teal-50 to-mint-50 border border-teal-100/50',
        'gradient-negative': 'bg-gradient-to-br from-red-50 to-orange-50 border border-red-100/50',
        highlight: 'bg-gradient-to-br from-teal-500 to-teal-600 text-white border-0',
        feature: 'bg-gradient-to-br from-teal-500 to-teal-600 text-white border-0',
    };

    const isHighlight = variant === 'highlight' || variant === 'feature';
    const textColor = isHighlight ? 'text-white' : 'text-gray-900';
    const subtitleColor = isHighlight ? 'text-teal-100' : 'text-gray-500';
    const titleColor = isHighlight ? 'text-teal-100' : 'text-gray-600';

    return (
        <div className={cn(
            'rounded-2xl p-6 relative overflow-hidden transition-all duration-300 hover:shadow-lg',
            variantStyles[variant],
            className
        )}>
            {/* Background icon decoration */}
            {Icon && (
                <div 
                    className="absolute bottom-0 right-0 opacity-[0.08] pointer-events-none" 
                    style={{ marginBottom: '-20%', marginRight: '-10%' }}
                >
                    <Icon 
                        className={isHighlight ? 'text-white' : 'text-teal-500'} 
                        strokeWidth={1.5} 
                        style={{ width: '140px', height: '140px' }} 
                    />
                </div>
            )}

            <div className="relative z-10">
                {/* Title */}
                <p className={cn('text-sm font-semibold mb-4', titleColor)}>
                    {title}
                </p>

                {/* Value */}
                <div className="flex items-baseline gap-2 mb-2 flex-wrap">
                    <p className={cn('text-2xl font-black tracking-tight whitespace-nowrap', textColor)}>
                        {formatValue(value, prefix, suffix)}
                    </p>
                    {typeof change === 'number' && (
                        <div className={cn(
                            'flex items-center text-xs font-semibold px-1.5 py-0.5 rounded-md',
                            isHighlight ? 'bg-white/20 text-white' : `${changeBg} ${changeColor}`
                        )}>
                            <ChangeIcon className="h-3 w-3 mr-0.5" />
                            {Math.abs(change)}%
                        </div>
                    )}
                </div>

                {/* Subtitle */}
                {subtitle && (
                    <p className={cn('text-sm', subtitleColor)}>
                        {subtitle}
                    </p>
                )}
            </div>
        </div>
    );
}

export default StatCard;
