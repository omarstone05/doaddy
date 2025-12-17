import React from 'react';
import { cn } from '@/lib/utils';

const sizes = {
    sm: 'text-2xl',
    md: 'text-3xl',
    lg: 'text-4xl',
    xl: 'text-5xl',
};

export function StatValue({ value, size = 'lg', prefix = '', suffix = '', className = '' }) {
    return (
        <div className={cn('font-black text-gray-900 tracking-tight', sizes[size], className)}>
            {prefix && <span className="text-gray-500 font-semibold">{prefix}</span>}
            {value}
            {suffix && <span className="text-gray-500 font-semibold text-[0.6em]">{suffix}</span>}
        </div>
    );
}

export function StatSubtitle({ text, accentType = 'positive', className = '' }) {
    const accentColors = {
        positive: 'text-teal-600',
        negative: 'text-red-500',
        warning: 'text-amber-500',
        neutral: 'text-gray-600',
    };
    const words = text.split(' ');
    const firstWord = words[0];
    const restOfText = words.slice(1).join(' ');
    return (
        <p className={cn('text-sm text-gray-500', className)}>
            <span className={cn('font-semibold', accentColors[accentType])}>{firstWord}</span>
            {restOfText && ` ${restOfText}`}
        </p>
    );
}

export default StatValue;
