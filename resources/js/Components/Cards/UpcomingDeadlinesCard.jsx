import React from 'react';
import { Calendar, AlertCircle, Briefcase, CheckSquare } from 'lucide-react';
import { format } from 'date-fns';

const UpcomingDeadlinesCard = ({ data }) => {
  const tasks = data?.tasks || [];
  const projects = data?.projects || [];
  const allDeadlines = [
    ...tasks.map(t => ({ ...t, type: 'task' })),
    ...projects.map(p => ({ ...p, type: 'project' })),
  ].sort((a, b) => new Date(a.due_date || a.end_date) - new Date(b.due_date || b.end_date))
   .slice(0, 5);

  const getPriorityColor = (priority) => {
    const colors = {
      urgent: 'text-red-600',
      high: 'text-orange-600',
      medium: 'text-yellow-600',
      low: 'text-gray-500',
    };
    return colors[priority] || colors.medium;
  };

  return (
    <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 relative group hover:shadow-xl transition-all h-full">
      <div className="flex items-center gap-3 mb-4">
        <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center shadow-lg">
          <Calendar className="text-white" size={24} />
        </div>
        <div>
          <div className="text-sm text-gray-600">Upcoming Deadlines</div>
          <div className="text-lg font-semibold text-gray-900">Next 7 Days</div>
        </div>
      </div>

      {allDeadlines.length > 0 ? (
        <div className="space-y-3">
          {allDeadlines.map((item, index) => (
            <div
              key={index}
              className="flex items-start gap-3 p-3 bg-white/50 rounded-lg hover:bg-white/80 transition-colors"
            >
              {item.type === 'task' ? (
                <CheckSquare size={18} className="text-teal-600 mt-0.5" />
              ) : (
                <Briefcase size={18} className="text-teal-600 mt-0.5" />
              )}
              <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between mb-1">
                  <p className="text-sm font-medium text-gray-900 truncate">
                    {item.title || item.name}
                  </p>
                  {item.priority && (
                    <span className={`text-xs font-medium ${getPriorityColor(item.priority)}`}>
                      {item.priority}
                    </span>
                  )}
                </div>
                <div className="flex items-center gap-2 text-xs text-gray-500">
                  <Calendar size={12} />
                  <span>
                    {format(new Date(item.due_date || item.end_date), 'MMM d, yyyy')}
                  </span>
                  {item.project_name && (
                    <>
                      <span>•</span>
                      <span>{item.project_name}</span>
                    </>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="text-center py-8 text-gray-500">
          <Calendar size={48} className="mx-auto mb-2 opacity-50" />
          <p className="text-sm">No upcoming deadlines</p>
        </div>
      )}
    </div>
  );
};

export default UpcomingDeadlinesCard;

