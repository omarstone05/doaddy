import React from 'react';
import { Activity, AlertTriangle, CheckCircle, Clock } from 'lucide-react';
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip } from 'recharts';

const ProjectHealthCard = ({ data }) => {
  const healthData = data?.health || [];
  
  const COLORS = {
    on_track: '#7DCD85',
    at_risk: '#F59E0B',
    delayed: '#EF4444',
  };

  const chartData = healthData.map(item => ({
    name: item.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()),
    value: item.count,
    color: COLORS[item.status] || '#94A3B8',
  }));

  const getIcon = (status) => {
    switch (status) {
      case 'on_track':
        return <CheckCircle size={16} className="text-green-600" />;
      case 'at_risk':
        return <Clock size={16} className="text-yellow-600" />;
      case 'delayed':
        return <AlertTriangle size={16} className="text-red-600" />;
      default:
        return <Activity size={16} className="text-gray-600" />;
    }
  };

  return (
    <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 relative group hover:shadow-xl transition-all h-full">
      <div className="flex items-center gap-3 mb-4">
        <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center shadow-lg">
          <Activity className="text-white" size={24} />
        </div>
        <div>
          <div className="text-sm text-gray-600">Project Health</div>
          <div className="text-lg font-semibold text-gray-900">Status Overview</div>
        </div>
      </div>

      {chartData.length > 0 ? (
        <>
          <div className="h-48 mb-4">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={chartData}
                  cx="50%"
                  cy="50%"
                  labelLine={false}
                  label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                  outerRadius={80}
                  fill="#8884d8"
                  dataKey="value"
                >
                  {chartData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
          </div>

          <div className="space-y-2">
            {healthData.map((item, index) => (
              <div key={index} className="flex items-center justify-between p-2 bg-white/50 rounded-lg">
                <div className="flex items-center gap-2">
                  {getIcon(item.status)}
                  <span className="text-sm text-gray-700 capitalize">
                    {item.status.replace('_', ' ')}
                  </span>
                </div>
                <span className="text-sm font-semibold text-gray-900">{item.count}</span>
              </div>
            ))}
          </div>
        </>
      ) : (
        <div className="text-center py-8 text-gray-500">
          <Activity size={48} className="mx-auto mb-2 opacity-50" />
          <p className="text-sm">No project data available</p>
        </div>
      )}
    </div>
  );
};

export default ProjectHealthCard;

