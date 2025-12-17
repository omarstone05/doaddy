import React from 'react';
import { cn } from '@/lib/utils';
import { Inbox } from 'lucide-react';
import { Button } from './Button';

export function EmptyState({ icon: Icon = Inbox, title = 'No items yet', description = 'Get started by creating your first item.', action, onAction, className = '' }) {
    return (
        <div className={cn('flex flex-col items-center justify-center py-12 px-4 text-center', className)}>
            <div className="w-16 h-16 rounded-2xl bg-teal-50 flex items-center justify-center mb-4">
                <Icon className="w-8 h-8 text-teal-500" />
            </div>
            <h3 className="text-lg font-semibold text-gray-900 mb-1">{title}</h3>
            <p className="text-sm text-gray-500 max-w-sm mb-6">{description}</p>
            {action && onAction && (
                <Button variant="primary" onClick={onAction}>{action}</Button>
            )}
        </div>
    );
}

export default EmptyState;
