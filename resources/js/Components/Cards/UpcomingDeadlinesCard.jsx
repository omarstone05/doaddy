import React from 'react';
import { Calendar, FolderKanban, CheckSquare } from 'lucide-react';
import { format } from 'date-fns';

const UpcomingDeadlinesCard = ({ data }) => {
  const tasks = data?.tasks || [];
  const projects = data?.projects || [];
  const allDeadlines = [
    ...tasks.map(t => ({ ...t, type: 'task' })),
    ...projects.map(p => ({ ...p, type: 'project' })),
  ].sort((a, b) => new Date(a.due_date || a.end_date) - new Date(b.due_date || b.end_date))
   .slice(0, 5);

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
    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 hover:shadow-lg hover:border-teal-200 transition-all duration-300">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h3 className="text-lg font-bold text-gray-900">Upcoming Deadlines</h3>
          <p className="text-sm text-gray-500 mt-0.5">Next 7 days</p>
        </div>
        <div className="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
          <Calendar className="w-5 h-5 text-teal-600" />
        </div>
      </div>

      {allDeadlines.length > 0 ? (
        <div className="space-y-3">
          {allDeadlines.map((item, index) => (
            <div
              key={index}
              className="flex items-start gap-3 p-3 bg-gray-50 rounded-xl hover:bg-teal-50/50 transition-colors"
            >
              <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${
                item.type === 'task' ? 'bg-teal-100' : 'bg-purple-100'
              }`}>
                {item.type === 'task' ? (
                  <CheckSquare className="w-5 h-5 text-teal-600" />
                ) : (
                  <FolderKanban className="w-5 h-5 text-purple-600" />
                )}
              </div>
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                  <p className="text-sm font-semibold text-gray-900 truncate">
                    {item.title || item.name}
                  </p>
                  {item.priority && (
                    <div className={`w-2 h-2 rounded-full ${getPriorityDot(item.priority)}`} />
                  )}
                </div>
                <div className="flex items-center gap-2 text-xs text-gray-500">
                  <Calendar size={10} />
                  <span>
                    {format(new Date(item.due_date || item.end_date), 'MMM d, yyyy')}
                  </span>
                  {item.project_name && (
                    <>
                      <span className="text-gray-300">•</span>
                      <span className="truncate">{item.project_name}</span>
                    </>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="text-center py-12">
          <div className="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center mx-auto mb-3">
            <Calendar className="w-6 h-6 text-teal-500" />
          </div>
          <p className="text-sm font-medium text-gray-600">No upcoming deadlines</p>
          <p className="text-xs text-gray-400 mt-1">You're all clear!</p>
        </div>
      )}
    </div>
  );
};

export default UpcomingDeadlinesCard;
