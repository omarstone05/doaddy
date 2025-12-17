import React from 'react';
import { X } from 'lucide-react';

export function QuickActionModal({ isOpen, onClose, availableActions, onSelect }) {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onClick={onClose}>
      <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden" onClick={(e) => e.stopPropagation()}>
        <div className="p-6 border-b border-gray-200 flex items-center justify-between">
          <h2 className="text-xl font-semibold text-gray-900">Select Quick Action</h2>
          <button
            onClick={onClose}
            className="p-2 hover:bg-gray-100 rounded-full transition-colors"
          >
            <X className="h-5 w-5 text-gray-500" />
          </button>
        </div>
        
        <div className="p-6 overflow-y-auto max-h-[calc(80vh-120px)]">
          <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
            {availableActions.map((action) => {
              const Icon = action.icon;
              return (
                <button
                  key={action.id}
                  onClick={() => onSelect(action)}
                  className="p-4 border-2 border-gray-200 hover:border-teal-500 rounded-lg flex flex-col items-center justify-center gap-2 transition-colors hover:bg-teal-50"
                >
                  {Icon && <Icon className="h-8 w-8 text-teal-500" />}
                  <span className="text-sm font-medium text-gray-700 text-center">{action.title}</span>
                </button>
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}

