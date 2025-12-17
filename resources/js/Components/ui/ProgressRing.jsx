import React from 'react';

export function ProgressRing({ 
    progress = 0, 
    size = 120, 
    strokeWidth = 8,
    color = 'teal',
    backgroundColor = '#e5e7eb',
    children,
    className = '',
}) {
    const radius = (size - strokeWidth) / 2;
    const circumference = radius * 2 * Math.PI;
    const strokeDashoffset = circumference - (progress / 100) * circumference;

    const colorMap = {
        teal: '#14b8a6',
        emerald: '#10b981',
        green: '#22c55e',
        orange: '#f97316',
        red: '#ef4444',
        yellow: '#eab308',
    };

    const strokeColor = colorMap[color] || color;

    return (
        <div className={`relative inline-flex items-center justify-center ${className}`}>
            <svg
                width={size}
                height={size}
                className="transform -rotate-90"
            >
                {/* Background circle */}
                <circle
                    cx={size / 2}
                    cy={size / 2}
                    r={radius}
                    fill="none"
                    stroke={backgroundColor}
                    strokeWidth={strokeWidth}
                />
                {/* Progress circle */}
                <circle
                    cx={size / 2}
                    cy={size / 2}
                    r={radius}
                    fill="none"
                    stroke={strokeColor}
                    strokeWidth={strokeWidth}
                    strokeLinecap="round"
                    strokeDasharray={circumference}
                    strokeDashoffset={strokeDashoffset}
                    className="transition-all duration-700 ease-out"
                />
            </svg>
            {/* Center content */}
            <div className="absolute inset-0 flex items-center justify-center">
                {children || (
                    <span className="text-2xl font-bold text-gray-900">
                        {Math.round(progress)}%
                    </span>
                )}
            </div>
        </div>
    );
}

export default ProgressRing;

