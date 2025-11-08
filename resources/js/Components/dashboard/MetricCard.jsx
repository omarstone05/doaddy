import React from 'react';
import { Card } from '../ui/Card';

export function MetricCard({ 
  label, 
  value, 
  link,
  linkText = 'See all',
  dismissible = true,
  icon: Icon
}) {
  return (
    <Card dismissible={dismissible} className="min-h-[180px]">
      <div className="flex flex-col h-full">
        <div className="flex items-center gap-2 mb-4">
          {Icon && (
            <div className="p-2 bg-teal-500/10 rounded-lg">
              <Icon className="h-5 w-5 text-teal-500" />
            </div>
          )}
          <p className="text-sm font-medium text-teal-600">
            Insights
          </p>
        </div>
        
        <div className="flex-1 flex flex-col justify-center">
          <p className="text-6xl font-bold text-teal-500 mb-2">
            {value}
          </p>
          <p className="text-sm font-medium text-gray-700">
            {label}
          </p>
        </div>
        
        {link && (
          <a 
            href={link}
            className="text-sm text-teal-500 hover:text-teal-600 font-medium inline-flex items-center gap-1 mt-4"
          >
            {linkText}
            <span>→</span>
          </a>
        )}
      </div>
    </Card>
  );
}

