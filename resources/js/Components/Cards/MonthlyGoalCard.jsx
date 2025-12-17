import React from 'react';
import { Target, TrendingUp, Trophy } from 'lucide-react';

const MonthlyGoalCard = ({ data }) => {
  const { goal = 100000, current = 0, percentage = 0, remaining = 0 } = data || {};

  // Calculate circle parameters
  const radius = 65;
  const circumference = 2 * Math.PI * radius;
  const strokeDashoffset = circumference - (Math.min(percentage, 100) / 100) * circumference;

  const formatCurrency = (value) => {
    if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
    if (value >= 1000) return `${(value / 1000).toFixed(1)}K`;
    return value.toFixed(0);
  };

  return (
    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 hover:shadow-lg hover:border-teal-200 transition-all duration-300 relative overflow-hidden">
      {/* Background icon decoration */}
      <div className="absolute bottom-0 right-0 opacity-[0.08] pointer-events-none" style={{ marginBottom: '-15%', marginRight: '-10%' }}>
        <Target className="text-teal-500" strokeWidth={1.5} style={{ width: '140px', height: '140px' }} />
      </div>

      <div className="relative z-10">
        {/* Header */}
        <div className="flex items-center justify-between mb-6">
          <div>
            <h3 className="text-lg font-bold text-gray-900">Monthly Goal</h3>
            <p className="text-sm text-gray-500 mt-0.5">Revenue target</p>
          </div>
        </div>

        {/* Circular Progress */}
        <div className="flex items-center justify-center mb-6">
          <div className="relative">
            <svg className="w-40 h-40 transform -rotate-90">
              {/* Background circle */}
              <circle
                cx="80"
                cy="80"
                r={radius}
                stroke="#e5e7eb"
                strokeWidth="12"
                fill="none"
              />
              
              {/* Progress circle with gradient */}
              <defs>
                <linearGradient id="goalGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stopColor="#14b8a6" />
                  <stop offset="100%" stopColor="#0d9488" />
                </linearGradient>
              </defs>
              <circle
                cx="80"
                cy="80"
                r={radius}
                stroke="url(#goalGradient)"
                strokeWidth="12"
                fill="none"
                strokeDasharray={circumference}
                strokeDashoffset={strokeDashoffset}
                strokeLinecap="round"
                className="transition-all duration-1000 ease-out"
              />
            </svg>

            {/* Center text */}
            <div className="absolute inset-0 flex flex-col items-center justify-center">
              <p className="text-3xl font-black text-gray-900">
                {Math.min(percentage, 100).toFixed(0)}%
              </p>
              <p className="text-xs text-gray-500 mt-1">Complete</p>
            </div>
          </div>
        </div>

        {/* Goal Details */}
        <div className="space-y-2">
          <div className="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
            <span className="text-sm text-gray-600">Current</span>
            <span className="text-sm font-bold text-gray-900">
              K {formatCurrency(current)}
            </span>
          </div>
          
          <div className="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
            <span className="text-sm text-gray-600">Target</span>
            <span className="text-sm font-bold text-gray-900">
              K {formatCurrency(goal)}
            </span>
          </div>

          {remaining > 0 && percentage < 100 && (
            <div className="flex items-center justify-between p-3 bg-teal-50 rounded-xl">
              <span className="text-sm text-teal-700 font-medium">To Go</span>
              <span className="text-sm font-bold text-teal-700">
                K {formatCurrency(remaining)}
              </span>
            </div>
          )}

          {percentage >= 100 && (
            <div className="flex items-center gap-2 p-3 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl">
              <Trophy size={16} />
              <span className="text-sm font-semibold">Goal achieved! 🎉</span>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default MonthlyGoalCard;
