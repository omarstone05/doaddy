import React from 'react';
import { FolderKanban, ArrowRight } from 'lucide-react';
import { Link } from '@inertiajs/react';

const ActiveProjectsCard = ({ data }) => {
  const { count = 0 } = data || {};

  return (
    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 relative overflow-hidden hover:shadow-lg hover:border-teal-200 transition-all duration-300">
      {/* Background icon decoration */}
      <div className="absolute bottom-0 right-0 opacity-[0.08] pointer-events-none" style={{ marginBottom: '-20%', marginRight: '-10%' }}>
        <FolderKanban className="text-teal-500" strokeWidth={1.5} style={{ width: '140px', height: '140px' }} />
      </div>

      <div className="relative z-10">
        {/* Title */}
        <p className="text-sm font-semibold text-gray-600 mb-4">Active Projects</p>

        {/* Count */}
        <p className="text-4xl font-black tracking-tight text-gray-900 mb-2">
          {count}
        </p>

        {/* Subtitle */}
        <p className="text-sm text-gray-500 mb-4">Currently in progress</p>

        {/* Quick Action */}
        <Link 
          href="/projects"
          className="flex items-center justify-between p-3 bg-teal-50 rounded-xl hover:bg-teal-100 transition-colors group"
        >
          <span className="text-sm font-semibold text-teal-700">View all projects</span>
          <ArrowRight size={16} className="text-teal-500 group-hover:translate-x-1 transition-transform" />
        </Link>
      </div>
    </div>
  );
};

export default ActiveProjectsCard;
