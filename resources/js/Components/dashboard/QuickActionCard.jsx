import React from 'react';
import { router } from '@inertiajs/react';

export function QuickActionCard({ title, icon: Icon, onClick, url }) {
  const handleClick = () => {
    if (onClick) {
      onClick();
    } else if (url) {
      router.visit(url);
    }
  };

  return (
    <button
      onClick={handleClick}
      className="w-full h-full bg-teal-500 hover:bg-teal-600 text-white rounded-lg p-3 flex flex-col items-center justify-center transition-colors shadow-sm hover:shadow-md"
    >
      {Icon && (
        <div className="mb-1.5">
          <Icon className="h-5 w-5" />
        </div>
      )}
      <h3 className="text-xs font-semibold text-center leading-tight">{title}</h3>
    </button>
  );
}

