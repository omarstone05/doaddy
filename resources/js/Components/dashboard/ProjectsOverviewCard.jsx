import React from 'react';
import { router } from '@inertiajs/react';
import { Card } from '../ui/Card';
import { FolderKanban, CheckCircle2, Clock, AlertTriangle, TrendingUp } from 'lucide-react';

export function ProjectsOverviewCard({ stats, onRemove, theme }) {
  const completionRate = stats?.total_projects > 0 
    ? Math.round((stats.completed_projects / stats.total_projects) * 100)
    : 0;

  return (
    <div className={`${theme.cardBg} border ${theme.cardBorder} ${theme.cardHover} p-6 rounded-2xl relative group hover:shadow-lg transition-all h-full`}>
      {onRemove && (
        <button 
          onClick={onRemove} 
          className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-100 hover:bg-gray-200 rounded-lg p-1 z-10"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      )}
      
      <div className="flex items-center gap-3 mb-4">
        <div className="p-2 bg-blue-500/10 rounded-lg">
          <FolderKanban className="h-5 w-5 text-blue-500" />
        </div>
        <div>
          <h3 className="text-sm font-semibold text-gray-700">Projects Overview</h3>
          <p className="text-xs text-gray-500">Active projects and progress</p>
        </div>
      </div>

      <div className="grid grid-cols-2 gap-3 mb-4">
        <div className="p-3 bg-green-50 rounded-lg">
          <div className="flex items-center gap-2 mb-1">
            <CheckCircle2 className="h-4 w-4 text-green-600" />
            <span className="text-xs text-gray-600">Active</span>
          </div>
          <div className="text-2xl font-bold text-green-600">{stats?.active_projects || 0}</div>
        </div>
        
        <div className="p-3 bg-blue-50 rounded-lg">
          <div className="flex items-center gap-2 mb-1">
            <Clock className="h-4 w-4 text-blue-600" />
            <span className="text-xs text-gray-600">Completed</span>
          </div>
          <div className="text-2xl font-bold text-blue-600">{stats?.completed_projects || 0}</div>
        </div>
      </div>

      {stats?.overdue_projects > 0 && (
        <div className="mb-4 p-3 bg-red-50 rounded-lg">
          <div className="flex items-center gap-2 mb-1">
            <AlertTriangle className="h-4 w-4 text-red-600" />
            <span className="text-xs font-medium text-red-700">Overdue Projects</span>
          </div>
          <div className="text-xl font-bold text-red-600">{stats.overdue_projects}</div>
        </div>
      )}

      <div className="mb-4">
        <div className="flex items-center justify-between mb-2">
          <span className="text-sm text-gray-600">Completion Rate</span>
          <span className="text-sm font-semibold text-gray-900">{completionRate}%</span>
        </div>
        <div className="w-full bg-gray-200 rounded-full h-2">
          <div 
            className="bg-blue-500 h-2 rounded-full transition-all"
            style={{ width: `${completionRate}%` }}
          />
        </div>
      </div>

      <button
        onClick={() => router.visit('/projects/section')}
        className="w-full mt-4 px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2"
      >
        <TrendingUp className="h-4 w-4" />
        View All Projects
      </button>
    </div>
  );
}

