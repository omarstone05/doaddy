import React from 'react';
import { CheckSquare } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Cell } from 'recharts';

const TaskCompletionCard = ({ data }) => {
  const tasks = data?.tasks || [];
  const total = data?.total || 0;
  const completed = data?.completed || 0;
  const completionRate = data?.completion_rate || 0;

  const statusColors = {
    todo: '#94A3B8',
    in_progress: '#14b8a6',
    review: '#f59e0b',
    done: '#22c55e',
    blocked: '#ef4444',
  };

  const chartData = tasks.map(item => ({
    name: item.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()),
    count: item.count,
    color: statusColors[item.status] || '#94A3B8',
  }));

  return (
    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 hover:shadow-lg hover:border-teal-200 transition-all duration-300">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h3 className="text-lg font-bold text-gray-900">Task Completion</h3>
          <p className="text-sm text-gray-500 mt-0.5">
            <span className="text-2xl font-black text-teal-600">{completionRate.toFixed(0)}%</span>
            <span className="ml-2">complete</span>
          </p>
        </div>
        <div className="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
          <CheckSquare className="w-5 h-5 text-teal-600" />
        </div>
      </div>

      {chartData.length > 0 ? (
        <>
          <div className="h-40 mb-4">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={chartData} barGap={4}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" vertical={false} />
                <XAxis 
                  dataKey="name" 
                  tick={{ fill: '#78716c', fontSize: 11 }}
                  axisLine={false}
                  tickLine={false}
                />
                <YAxis 
                  tick={{ fill: '#78716c', fontSize: 11 }} 
                  axisLine={false}
                  tickLine={false}
                />
                <Tooltip
                  contentStyle={{
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    border: '1px solid #e7e5e4',
                    borderRadius: '12px',
                    boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                  }}
                />
                <Bar dataKey="count" radius={[6, 6, 0, 0]}>
                  {chartData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>

          <div className="grid grid-cols-2 gap-3 pt-4 border-t border-gray-200/50">
            <div className="p-3 bg-gray-50 rounded-xl text-center">
              <p className="text-xs text-gray-600 mb-1">Total Tasks</p>
              <p className="text-xl font-black text-gray-900">{total}</p>
            </div>
            <div className="p-3 bg-teal-50 rounded-xl text-center">
              <p className="text-xs text-gray-600 mb-1">Completed</p>
              <p className="text-xl font-black text-teal-600">{completed}</p>
            </div>
          </div>
        </>
      ) : (
        <div className="text-center py-12">
          <div className="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center mx-auto mb-3">
            <CheckSquare className="w-6 h-6 text-teal-500" />
          </div>
          <p className="text-sm font-medium text-gray-600">No task data available</p>
          <p className="text-xs text-gray-400 mt-1">Create tasks to track progress</p>
        </div>
      )}
    </div>
  );
};

export default TaskCompletionCard;
