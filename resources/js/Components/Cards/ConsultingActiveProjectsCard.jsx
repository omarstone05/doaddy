import React from 'react';
import { Briefcase } from 'lucide-react';

const ConsultingActiveProjectsCard = ({ data }) => {
  const count = data?.count || 0;

  return (
    <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 relative group hover:shadow-xl transition-all h-full">
      <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center mb-4 shadow-lg">
        <Briefcase className="text-white" size={24} />
      </div>

      <div className="text-sm text-gray-600 mb-1">Active Projects</div>

      <div className="text-3xl font-bold text-gray-900 mb-2">
        {count}
      </div>

      <div className="text-xs text-gray-500">
        Currently running consulting projects
      </div>
    </div>
  );
};

export default ConsultingActiveProjectsCard;

