import React from 'react';
import { TrendingUp, Briefcase } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Cell } from 'recharts';

const ProjectProgressCard = ({ data }) => {
  const projects = data?.projects || [];
  const averageProgress = data?.average_progress || 0;
  const totalProjects = data?.total_projects || 0;

  const chartData = projects.map(project => ({
    name: project.name.length > 15 ? project.name.substring(0, 15) + '...' : project.name,
    progress: project.progress,
    color: project.progress >= 75 ? '#7DCD85' : project.progress >= 50 ? '#F59E0B' : '#EF4444',
  }));

  return (
    <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 relative group hover:shadow-xl transition-all h-full">
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-3">
          <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center shadow-lg">
            <TrendingUp className="text-white" size={24} />
          </div>
          <div>
            <div className="text-sm text-gray-600">Project Progress</div>
            <div className="text-lg font-semibold text-gray-900">
              {averageProgress.toFixed(1)}% Average
            </div>
          </div>
        </div>
        <div className="text-right">
          <div className="text-xs text-gray-500">Total Projects</div>
          <div className="text-xl font-bold text-teal-600">{totalProjects}</div>
        </div>
      </div>

      {chartData.length > 0 ? (
        <>
          <div className="h-64 mb-4">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={chartData} layout="vertical">
                <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" />
                <XAxis type="number" domain={[0, 100]} tick={{ fontSize: 12 }} />
                <YAxis 
                  dataKey="name" 
                  type="category"
                  tick={{ fontSize: 11 }}
                  width={120}
                />
                <Tooltip 
                  formatter={(value) => `${value}%`}
                  labelStyle={{ color: '#374151' }}
                />
                <Bar dataKey="progress" radius={[0, 8, 8, 0]}>
                  {chartData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>

          <div className="grid grid-cols-3 gap-3 pt-4 border-t border-gray-200">
            <div>
              <div className="text-xs text-gray-500 mb-1">On Track</div>
              <div className="text-lg font-semibold text-green-600">
                {projects.filter(p => p.progress >= 75).length}
              </div>
            </div>
            <div>
              <div className="text-xs text-gray-500 mb-1">At Risk</div>
              <div className="text-lg font-semibold text-yellow-600">
                {projects.filter(p => p.progress >= 50 && p.progress < 75).length}
              </div>
            </div>
            <div>
              <div className="text-xs text-gray-500 mb-1">Delayed</div>
              <div className="text-lg font-semibold text-red-600">
                {projects.filter(p => p.progress < 50).length}
              </div>
            </div>
          </div>
        </>
      ) : (
        <div className="text-center py-12 text-gray-500">
          <Briefcase size={48} className="mx-auto mb-2 opacity-50" />
          <p className="text-sm">No active projects</p>
        </div>
      )}
    </div>
  );
};

export default ProjectProgressCard;

