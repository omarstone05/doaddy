import React from 'react';
import { cn } from '@/lib/utils';

export function DotPattern({ id = 'dot-pattern', opacity = 0.1, size = 20, color = '#14b8a6', className = '' }) {
    return (
        <div className={cn('absolute inset-0 overflow-hidden pointer-events-none', className)}>
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id={id} x="0" y="0" width={size} height={size} patternUnits="userSpaceOnUse">
                        <circle cx={size / 2} cy={size / 2} r="1" fill={color} opacity={opacity} />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill={`url(#${id})`} />
            </svg>
        </div>
    );
}

export default DotPattern;
