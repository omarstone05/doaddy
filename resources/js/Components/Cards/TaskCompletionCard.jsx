import React from 'react';
import { CheckSquare, TrendingUp } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Cell } from 'recharts';

const TaskCompletionCard = ({ data }) => {
  const tasks = data?.tasks || [];
  const total = data?.total || 0;
  const completed = data?.completed || 0;
  const completionRate = data?.completion_rate || 0;

  const statusColors = {
    todo: '#94A3B8',
    in_progress: '#3B82F6',
    review: '#F59E0B',
    done: '#7DCD85',
    blocked: '#EF4444',
  };

  const chartData = tasks.map(item => ({
    name: item.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()),
    count: item.count,
    color: statusColors[item.status] || '#94A3B8',
  }));

  return (
    <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 relative group hover:shadow-xl transition-all h-full">
      <div className="flex items-center gap-3 mb-4">
        <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center shadow-lg">
          <CheckSquare className="text-white" size={24} />
        </div>
        <div>
          <div className="text-sm text-gray-600">Task Completion</div>
          <div className="text-lg font-semibold text-gray-900">{completionRate.toFixed(1)}%</div>
        </div>
      </div>

      {chartData.length > 0 ? (
        <>
          <div className="h-40 mb-4">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={chartData}>
                <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" />
                <XAxis 
                  dataKey="name" 
                  tick={{ fontSize: 12 }}
                  angle={-45}
                  textAnchor="end"
                  height={60}
                />
                <YAxis tick={{ fontSize: 12 }} />
                <Tooltip />
                <Bar dataKey="count" radius={[8, 8, 0, 0]}>
                  {chartData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>

          <div className="grid grid-cols-2 gap-3 pt-4 border-t border-gray-200">
            <div>
              <div className="text-xs text-gray-500 mb-1">Total Tasks</div>
              <div className="text-lg font-semibold text-gray-900">{total}</div>
            </div>
            <div>
              <div className="text-xs text-gray-500 mb-1">Completed</div>
              <div className="text-lg font-semibold text-green-600">{completed}</div>
            </div>
          </div>
        </>
      ) : (
        <div className="text-center py-8 text-gray-500">
          <CheckSquare size={48} className="mx-auto mb-2 opacity-50" />
          <p className="text-sm">No task data available</p>
        </div>
      )}
    </div>
  );
};

export default TaskCompletionCard;

