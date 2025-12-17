import React from 'react';
import { cn } from '@/lib/utils';

const sizes = {
    xs: 'w-6 h-6 text-xs',
    sm: 'w-8 h-8 text-sm',
    md: 'w-10 h-10 text-base',
    lg: 'w-12 h-12 text-lg',
    xl: 'w-16 h-16 text-xl',
};

export function Avatar({ 
    name = '', 
    src, 
    size = 'md',
    gradient = 'from-teal-400 to-teal-600',
    className = '',
}) {
    const initial = name ? name.charAt(0).toUpperCase() : '?';

    if (src) {
        return (
            <img
                src={src}
                alt={name}
                className={cn(
                    'rounded-full object-cover ring-2 ring-white',
                    sizes[size],
                    className
                )}
            />
        );
    }

    return (
        <div className={cn(
            'rounded-full flex items-center justify-center font-semibold text-white ring-2 ring-white',
            `bg-gradient-to-br ${gradient}`,
            sizes[size],
            className
        )}>
            {initial}
        </div>
    );
}

export function AvatarStack({ 
    members = [], 
    max = 4, 
    size = 'md',
    className = '',
}) {
    const visibleMembers = members.slice(0, max);
    const remainingCount = members.length - max;

    const stackSizes = {
        sm: '-space-x-2',
        md: '-space-x-3',
        lg: '-space-x-4',
    };

    return (
        <div className={cn('flex items-center', stackSizes[size], className)}>
            {visibleMembers.map((member, index) => (
                <Avatar
                    key={member.id || index}
                    name={member.name}
                    src={member.avatar}
                    size={size}
                />
            ))}
            {remainingCount > 0 && (
                <div className={cn(
                    'rounded-full flex items-center justify-center font-semibold bg-gray-200 text-gray-600 ring-2 ring-white',
                    sizes[size]
                )}>
                    +{remainingCount}
                </div>
            )}
        </div>
    );
}

export default Avatar;
