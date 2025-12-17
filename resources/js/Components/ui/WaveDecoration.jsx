import React from 'react';
import { cn } from '@/lib/utils';

const variantColors = {
    positive: ['#14b8a6', '#5eead4'],
    negative: ['#ef4444', '#fca5a5'],
    warning: ['#f59e0b', '#fcd34d'],
};

export function WaveDecoration({ variant = 'positive', className = '' }) {
    const [color1, color2] = variantColors[variant] || variantColors.positive;
    return (
        <div className={cn('absolute bottom-0 left-0 right-0 overflow-hidden pointer-events-none', className)}>
            <svg viewBox="0 0 400 80" className="w-full h-16 opacity-30" preserveAspectRatio="none">
                <defs>
                    <linearGradient id={`wave-${variant}`} x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stopColor={color1} stopOpacity="0.3" />
                        <stop offset="100%" stopColor={color2} stopOpacity="0.1" />
                    </linearGradient>
                </defs>
                <path d="M0,40 C100,60 200,20 300,40 C350,50 380,45 400,40 L400,80 L0,80 Z" fill={`url(#wave-${variant})`} />
            </svg>
        </div>
    );
}

export default WaveDecoration;
