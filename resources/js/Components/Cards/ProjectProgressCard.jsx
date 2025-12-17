import React from 'react';
import { BarChart2, FolderKanban } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Cell } from 'recharts';

const ProjectProgressCard = ({ data }) => {
  const projects = data?.projects || [];
  const averageProgress = data?.average_progress || 0;
  const totalProjects = data?.total_projects || 0;

  const chartData = projects.map(project => ({
    name: project.name.length > 15 ? project.name.substring(0, 15) + '...' : project.name,
    progress: project.progress,
    color: project.progress >= 75 ? '#14b8a6' : project.progress >= 50 ? '#f59e0b' : '#ef4444',
  }));

  return (
    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 hover:shadow-lg hover:border-teal-200 transition-all duration-300">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h3 className="text-lg font-bold text-gray-900">Project Progress</h3>
          <p className="text-sm text-gray-500 mt-0.5">
            <span className="text-2xl font-black text-teal-600">{averageProgress.toFixed(0)}%</span>
            <span className="ml-2">average completion</span>
          </p>
        </div>
        <div className="text-right">
          <p className="text-xs text-gray-500">Total Projects</p>
          <p className="text-2xl font-black text-gray-900">{totalProjects}</p>
        </div>
      </div>

      {chartData.length > 0 ? (
        <>
          <div className="h-56 mb-4">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={chartData} layout="vertical" barGap={4}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" vertical={false} />
                <XAxis 
                  type="number" 
                  domain={[0, 100]} 
                  tick={{ fill: '#78716c', fontSize: 11 }}
                  axisLine={false}
                  tickLine={false}
                />
                <YAxis 
                  dataKey="name" 
                  type="category"
                  tick={{ fill: '#78716c', fontSize: 11 }}
                  width={100}
                  axisLine={false}
                  tickLine={false}
                />
                <Tooltip 
                  formatter={(value) => `${value}%`}
                  contentStyle={{
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    border: '1px solid #e7e5e4',
                    borderRadius: '12px',
                    boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                  }}
                />
                <Bar dataKey="progress" radius={[0, 6, 6, 0]}>
                  {chartData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>

          <div className="grid grid-cols-3 gap-3 pt-4 border-t border-gray-200/50">
            <div className="p-3 bg-teal-50 rounded-xl text-center">
              <p className="text-xs text-gray-600 mb-1">On Track</p>
              <p className="text-xl font-black text-teal-600">
                {projects.filter(p => p.progress >= 75).length}
              </p>
            </div>
            <div className="p-3 bg-amber-50 rounded-xl text-center">
              <p className="text-xs text-gray-600 mb-1">At Risk</p>
              <p className="text-xl font-black text-amber-600">
                {projects.filter(p => p.progress >= 50 && p.progress < 75).length}
              </p>
            </div>
            <div className="p-3 bg-red-50 rounded-xl text-center">
              <p className="text-xs text-gray-600 mb-1">Delayed</p>
              <p className="text-xl font-black text-red-500">
                {projects.filter(p => p.progress < 50).length}
              </p>
            </div>
          </div>
        </>
      ) : (
        <div className="text-center py-12">
          <div className="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center mx-auto mb-3">
            <FolderKanban className="w-6 h-6 text-teal-500" />
          </div>
          <p className="text-sm font-medium text-gray-600">No active projects</p>
          <p className="text-xs text-gray-400 mt-1">Projects will appear here</p>
        </div>
      )}
    </div>
  );
};

export default ProjectProgressCard;
