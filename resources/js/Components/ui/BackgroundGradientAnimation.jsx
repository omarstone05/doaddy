import React from 'react';

export function BackgroundGradientAnimation({ children, className = '' }) {
  return (
    <div className={`relative h-full w-full overflow-hidden rounded-3xl ${className}`}>
      {/* Animated background elements */}
      <div className="absolute inset-0 overflow-hidden">
        {/* Animated blob 1 */}
        <div className="absolute top-0 left-0 w-72 h-72 bg-teal-400/40 rounded-full blur-3xl animate-move-vertical" />
        
        {/* Animated blob 2 */}
        <div className="absolute top-1/4 right-0 w-96 h-96 bg-mint-400/40 rounded-full blur-3xl animate-move-circle-reverse" />
        
        {/* Animated blob 3 */}
        <div className="absolute bottom-0 left-1/3 w-80 h-80 bg-teal-300/40 rounded-full blur-3xl animate-move-circle-slow" />
        
        {/* Animated blob 4 */}
        <div className="absolute top-1/2 right-1/4 w-64 h-64 bg-mint-300/40 rounded-full blur-3xl animate-move-horizontal" />
        
        {/* Animated blob 5 */}
        <div className="absolute bottom-1/4 left-1/2 w-56 h-56 bg-teal-200/40 rounded-full blur-3xl animate-move-circle" />
      </div>

      {/* Content */}
      <div className="relative z-10">
        {children}
      </div>
    </div>
  );
}

