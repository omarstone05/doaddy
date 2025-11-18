// Active Projects Card - PM Module with glassmorphism
import React from 'react';
import { Briefcase, ArrowRight } from 'lucide-react';

const ActiveProjectsCard = ({ data }) => {
  const { count = 0 } = data || {};

  return (
    <div className="h-full">
      {/* Icon */}
      <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center mb-4">
        <Briefcase className="text-white" size={24} />
      </div>

      {/* Label */}
      <div className="text-sm text-gray-600 mb-1">Active Projects</div>

      {/* Count */}
      <div className="text-3xl font-bold text-gray-900 mb-2">
        {count}
      </div>

      {/* Status */}
      <div className="text-sm text-gray-500">
        Currently in progress
      </div>

      {/* Quick Action */}
      <div className="mt-4 pt-4 border-t border-gray-200">
        <button className="w-full flex items-center justify-between p-2 hover:bg-white/50 rounded-lg transition-colors group">
          <span className="text-sm text-gray-700 font-medium">View all projects</span>
          <ArrowRight size={16} className="text-gray-400 group-hover:text-gray-600 group-hover:translate-x-1 transition-all" />
        </button>
      </div>
    </div>
  );
};

export default ActiveProjectsCard;

