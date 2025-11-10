import React from 'react';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';

export function Card({ 
  children, 
  className = '', 
  dismissible = false,
  onDismiss,
  padding = 'default' // 'default' | 'lg' | 'none'
}) {
  const paddingClasses = {
    default: 'p-6',
    lg: 'p-8',
    none: 'p-0',
  };

  return (
    <div className={cn(
      'bg-white',
      'border border-gray-200',
      'rounded-2xl',
      'shadow-[0_1px_3px_0_rgba(0,0,0,0.05)]',
      'hover:shadow-[0_4px_12px_0_rgba(0,0,0,0.08)]',
      'transition-shadow',
      'duration-200',
      'relative',
      'h-full',
      'flex',
      'flex-col',
      paddingClasses[padding],
      className
    )}>
      {dismissible && (
        <button
          onClick={onDismiss}
          className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
        >
          <X className="h-5 w-5" />
        </button>
      )}
      {children}
    </div>
  );
}

export function CardHeader({ className, children, ...props }) {
  return (
    <div className={cn('mb-4', className)} {...props}>
      {children}
    </div>
  );
}

export function CardTitle({ className, children, ...props }) {
  return (
    <h3 className={cn('text-xl font-semibold text-gray-700', className)} {...props}>
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
