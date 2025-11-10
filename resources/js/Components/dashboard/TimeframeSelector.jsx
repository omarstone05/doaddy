import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';

const timeframes = [
  { value: 'today', label: 'Today' },
  { value: 'yesterday', label: 'Yesterday' },
  { value: 'this_week', label: 'This Week' },
  { value: 'last_week', label: 'Last Week' },
  { value: 'this_month', label: 'This Month' },
  { value: 'last_month', label: 'Last Month' },
  { value: 'this_quarter', label: 'This Quarter' },
  { value: 'last_quarter', label: 'Last Quarter' },
  { value: 'this_year', label: 'This Year' },
  { value: 'last_year', label: 'Last Year' },
];

export function TimeframeSelector({ currentTimeframe = 'today' }) {
  const [isOpen, setIsOpen] = useState(false);
  const selected = timeframes.find(t => t.value === currentTimeframe) || timeframes[0];

  const handleSelect = (timeframe) => {
    setIsOpen(false);
    router.get('/dashboard', { timeframe }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  return (
    <div className="relative">
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="bg-mint-100 text-teal-700 font-medium px-6 py-2.5 rounded-full flex items-center gap-2 hover:bg-mint-200 transition-colors"
      >
        {selected.label.toUpperCase()}
        <ChevronDown className={`h-4 w-4 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
      </button>

      {isOpen && (
        <>
          <div
            className="fixed inset-0 z-10"
            onClick={() => setIsOpen(false)}
          />
          <div className="absolute right-0 mt-2 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-20 min-w-[180px]">
            {timeframes.map((timeframe) => (
              <button
                key={timeframe.value}
                onClick={() => handleSelect(timeframe.value)}
                className={`w-full text-left px-4 py-2 text-sm transition-colors ${
                  currentTimeframe === timeframe.value
                    ? 'bg-teal-50 text-teal-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-50'
                }`}
              >
                {timeframe.label}
              </button>
            ))}
          </div>
        </>
      )}
    </div>
  );
}

