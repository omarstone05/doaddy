import React from 'react';

export function Avatar({ children, className = '', src, alt }) {
    return (
        <div className={`inline-flex items-center justify-center rounded-full bg-gray-200 ${className}`}>
            {src ? (
                <img src={src} alt={alt} className="w-full h-full rounded-full object-cover" />
            ) : (
                children
            )}
        </div>
    );
}

export function AvatarFallback({ children, className = '' }) {
    return (
        <span className={`text-gray-600 font-medium ${className}`}>
            {children}
        </span>
    );
}

