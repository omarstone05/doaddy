import React from 'react';
import { Card } from '../ui/Card';
import { Clock, ArrowRight } from 'lucide-react';
import { router } from '@inertiajs/react';

export function RecentActivityCard({ activities }) {
  const getActivityIcon = (type) => {
    switch(type) {
      case 'sale': return '💰';
      case 'invoice': return '📄';
      case 'payment': return '💳';
      case 'expense': return '💸';
      default: return '📌';
    }
  };
  
  const getActivityColor = (type) => {
    switch(type) {
      case 'sale': return 'bg-green-100 text-green-700';
      case 'invoice': return 'bg-blue-100 text-blue-700';
      case 'payment': return 'bg-teal-100 text-teal-700';
      case 'expense': return 'bg-red-100 text-red-700';
      default: return 'bg-gray-100 text-gray-700';
    }
  };
  
  return (
    <Card className="h-full flex flex-col">
      <div className="mb-4">
        <h3 className="text-lg font-semibold text-gray-900 mb-1">Recent Activity</h3>
        <p className="text-sm text-gray-500">Latest transactions</p>
      </div>
      
      <div className="flex-1 overflow-y-auto">
        <div className="space-y-3">
          {activities && activities.length > 0 ? (
            activities.map((activity, index) => (
              <div 
                key={index}
                className="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                onClick={() => activity.url && router.visit(activity.url)}
              >
                <div className={`p-2 rounded-lg ${getActivityColor(activity.type)}`}>
                  <span className="text-lg">{getActivityIcon(activity.type)}</span>
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-900 truncate">
                    {activity.title}
                  </p>
                  <div className="flex items-center gap-2 mt-1">
                    <Clock className="h-3 w-3 text-gray-400" />
                    <span className="text-xs text-gray-500">{activity.time}</span>
                  </div>
                </div>
                <div className="text-right">
                  <p className="text-sm font-semibold text-gray-900">{activity.amount}</p>
                  {activity.url && (
                    <ArrowRight className="h-4 w-4 text-gray-400 mt-1" />
                  )}
                </div>
              </div>
            ))
          ) : (
            <div className="text-center py-8 text-gray-500 text-sm">
              No recent activity
            </div>
          )}
        </div>
      </div>
    </Card>
  );
}

