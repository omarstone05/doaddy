import React from 'react';
import { CheckSquare, Calendar, AlertCircle, ArrowRight } from 'lucide-react';
import { Link } from '@inertiajs/react';

const MyTasksCard = ({ data }) => {
  const tasks = data?.tasks || [];
  const total = data?.total || 0;

  const getPriorityColor = (priority) => {
    const colors = {
      urgent: 'text-red-600',
      high: 'text-orange-600',
      medium: 'text-yellow-600',
      low: 'text-gray-500',
    };
    return colors[priority] || colors.medium;
  };

  const getStatusColor = (status) => {
    const colors = {
      todo: 'bg-gray-100 text-gray-700',
      in_progress: 'bg-blue-100 text-blue-700',
      review: 'bg-yellow-100 text-yellow-700',
      blocked: 'bg-red-100 text-red-700',
    };
    return colors[status] || colors.todo;
  };

  return (
    <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 relative group hover:shadow-xl transition-all h-full">
      <div className="flex items-center gap-3 mb-4">
        <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center shadow-lg">
          <CheckSquare className="text-white" size={24} />
        </div>
        <div>
          <div className="text-sm text-gray-600">My Tasks</div>
          <div className="text-2xl font-bold text-gray-900">{total}</div>
        </div>
      </div>

      {tasks.length > 0 ? (
        <>
          <div className="space-y-2 mb-4">
            {tasks.slice(0, 5).map((task) => (
              <div
                key={task.id}
                className="flex items-start gap-2 p-2 bg-white/50 rounded-lg hover:bg-white/80 transition-colors"
              >
                <CheckSquare 
                  size={16} 
                  className={`mt-0.5 ${
                    task.status === 'done' ? 'text-green-600' : 'text-gray-400'
                  }`} 
                />
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-900 truncate">
                    {task.title}
                  </p>
                  <div className="flex items-center gap-2 mt-1">
                    <span className={`text-xs px-1.5 py-0.5 rounded ${getStatusColor(task.status)}`}>
                      {task.status.replace('_', ' ')}
                    </span>
                    <span className={`text-xs ${getPriorityColor(task.priority)}`}>
                      {task.priority}
                    </span>
                    {task.due_date && (
                      <>
                        <span className="text-gray-300">•</span>
                        <Calendar size={12} className="text-gray-400" />
                        <span className="text-xs text-gray-500">
                          {new Date(task.due_date).toLocaleDateString()}
                        </span>
                      </>
                    )}
                  </div>
                  <p className="text-xs text-gray-500 mt-1 truncate">
                    {task.project_name}
                  </p>
                </div>
              </div>
            ))}
          </div>

          {total > 5 && (
            <div className="pt-3 border-t border-gray-200">
              <p className="text-xs text-gray-500 text-center">
                +{total - 5} more tasks
              </p>
            </div>
          )}
        </>
      ) : (
        <div className="text-center py-8 text-gray-500">
          <CheckSquare size={48} className="mx-auto mb-2 opacity-50" />
          <p className="text-sm">No tasks assigned to you</p>
        </div>
      )}
    </div>
  );
};

export default MyTasksCard;

