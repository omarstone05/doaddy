import React from 'react';
import { TrendingUp, TrendingDown, ArrowRight } from 'lucide-react';

export function GreenMetricCard({ 
  title, 
  value, 
  subtitle,
  percentageChange,
  trend = 'up', // 'up' or 'down'
  icon: Icon,
  link,
  dismissible = true
}) {
  const isPositive = trend === 'up';
  const changeColor = isPositive ? 'bg-mint-300/30 text-white' : 'bg-red-500/30 text-white';
  
  return (
    <div className="bg-gradient-to-br from-teal-500 to-teal-600 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow h-full flex flex-col relative overflow-hidden">
      {/* Background Pattern/Decoration */}
      <div className="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-16 -mt-16"></div>
      <div className="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full -ml-12 -mb-12"></div>
      
      {/* Content */}
      <div className="relative z-10 flex flex-col h-full">
        {/* Header */}
        <div className="flex items-start justify-between mb-4">
          {Icon && (
            <div className="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
              <Icon className="h-5 w-5 text-white" />
            </div>
          )}
          {percentageChange !== undefined && (
            <div className={`px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1 ${changeColor}`}>
              {isPositive ? (
                <TrendingUp className="h-3 w-3" />
              ) : (
                <TrendingDown className="h-3 w-3" />
              )}
              {Math.abs(percentageChange)}%
            </div>
          )}
        </div>
        
        {/* Title */}
        <h3 className="text-white/90 text-sm font-medium mb-2">
          {title}
        </h3>
        
        {/* Value */}
        <div className="flex-1 flex items-center">
          <p className="text-white text-4xl font-bold">
            {value}
          </p>
        </div>
        
        {/* Subtitle */}
        {subtitle && (
          <p className="text-white/70 text-xs mt-2">
            {subtitle}
          </p>
        )}
        
        {/* Link/Arrow */}
        {link && (
          <div className="mt-4 flex items-center text-white/80 hover:text-white transition-colors">
            <ArrowRight className="h-4 w-4" />
          </div>
        )}
      </div>
    </div>
  );
}

