import React from 'react';
import { Plus } from 'lucide-react';
import { QuickActionCard } from './QuickActionCard';

export function QuickActionsBlock({ assignedAction, onAssign, onRemove }) {
  if (assignedAction) {
    return (
      <div className="w-full h-full relative group">
        <QuickActionCard
          title={assignedAction.title}
          icon={assignedAction.icon}
          url={assignedAction.url}
        />
        {onRemove && (
          <button
            onClick={(e) => {
              e.stopPropagation();
              onRemove();
            }}
            className="absolute top-2 right-2 p-1.5 bg-white hover:bg-red-50 rounded-full border border-gray-300 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity z-10"
            title="Remove quick action"
          >
            <svg className="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        )}
      </div>
    );
  }

  return (
    <button
      onClick={onAssign}
      className="w-full h-full bg-gray-100 hover:bg-gray-200 border-2 border-dashed border-gray-300 hover:border-teal-400 rounded-lg flex flex-col items-center justify-center transition-colors group"
    >
      <Plus className="h-20 w-20 text-gray-400 group-hover:text-teal-500 transition-colors mb-4" />
      <span className="text-lg font-semibold text-gray-500 group-hover:text-teal-600">
        Add Quick Action
      </span>
    </button>
  );
}

