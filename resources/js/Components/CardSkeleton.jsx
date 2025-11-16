import React from 'react';

// Card Skeleton for loading states
export const CardSkeleton = ({ size = 'medium' }) => {
  const sizeClasses = {
    'small': 'lg:col-span-1',
    'medium': 'lg:col-span-2',
    'large': 'lg:col-span-2 md:row-span-2',
  };

  return (
    <div className={`${sizeClasses[size]} min-h-[200px]`}>
      <div className="bg-white border border-gray-200 p-6 rounded-2xl animate-pulse">
        <div className="flex items-start justify-between mb-4">
          <div className="w-8 h-8 bg-gray-200 rounded-lg"></div>
          <div className="w-4 h-4 bg-gray-200 rounded"></div>
        </div>
        <div className="space-y-3">
          <div className="h-4 bg-gray-200 rounded w-1/3"></div>
          <div className="h-8 bg-gray-200 rounded w-1/2"></div>
          <div className="h-3 bg-gray-200 rounded w-2/3"></div>
        </div>
      </div>
    </div>
  );
};

// Full Dashboard Skeleton
export const DashboardSkeleton = () => {
  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header Skeleton */}
        <div className="flex justify-between items-center mb-8">
          <div>
            <div className="h-8 bg-gray-200 rounded w-48 mb-2 animate-pulse"></div>
            <div className="h-4 bg-gray-200 rounded w-32 animate-pulse"></div>
          </div>
          <div className="h-10 bg-gray-200 rounded w-32 animate-pulse"></div>
        </div>

        {/* Grid Skeleton */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 auto-rows-auto">
          <CardSkeleton size="large" />
          <CardSkeleton size="medium" />
          <CardSkeleton size="medium" />
          <CardSkeleton size="medium" />
          <CardSkeleton size="small" />
          <CardSkeleton size="medium" />
          <CardSkeleton size="medium" />
          <CardSkeleton size="small" />
          <CardSkeleton size="medium" />
          <CardSkeleton size="large" />
        </div>
      </div>
    </div>
  );
};

export default CardSkeleton;

