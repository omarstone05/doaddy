import React from 'react';
import { Card } from '../ui/Card';
import { FolderKanban, CheckCircle2, Clock, AlertCircle } from 'lucide-react';

export function ProjectStatusCard({ projects }) {
  const total = projects?.length || 0;
  const completed = projects?.filter(p => p.status === 'completed').length || 0;
  const inProgress = projects?.filter(p => p.status === 'in_progress').length || 0;
  const onHold = projects?.filter(p => p.status === 'on_hold').length || 0;
  
  return (
    <Card className="h-full flex flex-col">
      <div className="mb-4">
        <h3 className="text-lg font-semibold text-gray-900 mb-1">Project Status</h3>
        <p className="text-sm text-gray-500">Active projects overview</p>
      </div>
      
      <div className="flex-1 flex flex-col justify-center">
        <div className="grid grid-cols-2 gap-4 mb-6">
          <div className="p-3 bg-green-50 rounded-lg">
            <div className="flex items-center gap-2 mb-1">
              <CheckCircle2 className="h-4 w-4 text-green-600" />
              <span className="text-xs text-gray-600">Completed</span>
            </div>
            <div className="text-2xl font-bold text-green-600">{completed}</div>
          </div>
          <div className="p-3 bg-blue-50 rounded-lg">
            <div className="flex items-center gap-2 mb-1">
              <Clock className="h-4 w-4 text-blue-600" />
              <span className="text-xs text-gray-600">In Progress</span>
            </div>
            <div className="text-2xl font-bold text-blue-600">{inProgress}</div>
          </div>
        </div>
        
        <div className="mb-4">
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm text-gray-600">Total Projects</span>
            <span className="text-sm font-semibold text-gray-900">{total}</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div 
              className="bg-green-500 h-2 rounded-full transition-all"
              style={{ width: `${total > 0 ? (completed / total) * 100 : 0}%` }}
            ></div>
          </div>
        </div>
        
        {projects && projects.length > 0 && (
          <div className="space-y-2">
            <p className="text-xs font-medium text-gray-700 mb-2">Recent Projects</p>
            {projects.slice(0, 3).map((project, index) => (
              <div key={index} className="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                <div className="flex items-center gap-2 flex-1 min-w-0">
                  <FolderKanban className="h-4 w-4 text-teal-500 flex-shrink-0" />
                  <span className="text-sm text-gray-900 truncate">{project.name}</span>
                </div>
                <span className={`text-xs font-medium px-2 py-1 rounded ${
                  project.status === 'completed' ? 'bg-green-100 text-green-700' :
                  project.status === 'in_progress' ? 'bg-blue-100 text-blue-700' :
                  'bg-yellow-100 text-yellow-700'
                }`}>
                  {project.status.replace('_', ' ')}
                </span>
              </div>
            ))}
          </div>
        )}
      </div>
    </Card>
  );
}

