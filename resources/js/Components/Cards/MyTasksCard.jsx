import React from 'react';
import { CheckSquare, Calendar, ArrowRight } from 'lucide-react';
import { Link } from '@inertiajs/react';

const MyTasksCard = ({ data }) => {
  const tasks = data?.tasks || [];
  const total = data?.total || 0;

  const getStatusColor = (status) => {
    const colors = {
      todo: 'bg-gray-100 text-gray-700',
      in_progress: 'bg-teal-100 text-teal-700',
      review: 'bg-amber-100 text-amber-700',
      blocked: 'bg-red-100 text-red-700',
      done: 'bg-green-100 text-green-700',
    };
    return colors[status] || colors.todo;
  };

  const getPriorityDot = (priority) => {
    const colors = {
      urgent: 'bg-red-500',
      high: 'bg-orange-500',
      medium: 'bg-amber-500',
      low: 'bg-gray-400',
    };
    return colors[priority] || colors.medium;
  };

  return (
    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 relative overflow-hidden hover:shadow-lg hover:border-teal-200 transition-all duration-300">
      {/* Background icon decoration */}
      <div className="absolute bottom-0 right-0 opacity-[0.08] pointer-events-none" style={{ marginBottom: '-15%', marginRight: '-8%' }}>
        <CheckSquare className="text-teal-500" strokeWidth={1.5} style={{ width: '120px', height: '120px' }} />
      </div>

      <div className="relative z-10">
        {/* Header */}
        <div className="flex items-center justify-between mb-4">
          <p className="text-sm font-semibold text-gray-600">My Tasks</p>
          <span className="text-2xl font-black text-gray-900">{total}</span>
        </div>

        {tasks.length > 0 ? (
          <>
            <div className="space-y-2 mb-4">
              {tasks.slice(0, 4).map((task) => (
                <div
                  key={task.id}
                  className="flex items-start gap-3 p-3 bg-gray-50 rounded-xl hover:bg-teal-50/50 transition-colors"
                >
                  <div className={`w-2 h-2 rounded-full mt-1.5 ${getPriorityDot(task.priority)}`} />
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-semibold text-gray-900 truncate">
                      {task.title}
                    </p>
                    <div className="flex items-center gap-2 mt-1.5">
                      <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${getStatusColor(task.status)}`}>
                        {task.status?.replace('_', ' ')}
                      </span>
                      {task.due_date && (
                        <span className="flex items-center gap-1 text-xs text-gray-500">
                          <Calendar size={10} />
                          {new Date(task.due_date).toLocaleDateString()}
                        </span>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {total > 4 && (
              <Link 
                href="/tasks"
                className="flex items-center justify-center gap-2 p-3 bg-teal-50 rounded-xl hover:bg-teal-100 transition-colors group"
              >
                <span className="text-sm font-semibold text-teal-700">View all {total} tasks</span>
                <ArrowRight size={14} className="text-teal-500 group-hover:translate-x-1 transition-transform" />
              </Link>
            )}
          </>
        ) : (
          <div className="text-center py-8">
            <div className="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center mx-auto mb-3">
              <CheckSquare className="w-6 h-6 text-teal-500" />
            </div>
            <p className="text-sm font-medium text-gray-600">All caught up!</p>
            <p className="text-xs text-gray-400 mt-1">No tasks assigned to you</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default MyTasksCard;
