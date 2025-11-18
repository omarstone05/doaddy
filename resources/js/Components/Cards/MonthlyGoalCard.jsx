// Monthly Goal Card - Circular progress with glassmorphism
import React from 'react';
import { Target, TrendingUp } from 'lucide-react';

const MonthlyGoalCard = ({ data }) => {
  const { goal = 100000, current = 0, percentage = 0, remaining = 0 } = data || {};

  // Calculate circle parameters
  const radius = 70;
  const circumference = 2 * Math.PI * radius;
  const strokeDashoffset = circumference - (percentage / 100) * circumference;

  return (
    <div className="h-full flex flex-col">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h3 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <Target size={20} className="text-teal-500" />
            Monthly Goal
          </h3>
          <p className="text-sm text-gray-600 mt-1">Revenue target</p>
        </div>
      </div>

      {/* Circular Progress */}
      <div className="flex-1 flex items-center justify-center">
        <div className="relative">
          <svg className="w-48 h-48 transform -rotate-90">
            {/* Background circle */}
            <circle
              cx="96"
              cy="96"
              r={radius}
              stroke="#F5F5F5"
              strokeWidth="16"
              fill="none"
            />
            
            {/* Progress circle with gradient */}
            <defs>
              <linearGradient id="goalGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#7DCD85" />
                <stop offset="100%" stopColor="#00635D" />
              </linearGradient>
            </defs>
            <circle
              cx="96"
              cy="96"
              r={radius}
              stroke="url(#goalGradient)"
              strokeWidth="16"
              fill="none"
              strokeDasharray={circumference}
              strokeDashoffset={strokeDashoffset}
              strokeLinecap="round"
              className="transition-all duration-1000 ease-out"
            />
          </svg>

          {/* Center text */}
          <div className="absolute inset-0 flex flex-col items-center justify-center">
            <div className="text-4xl font-bold text-gray-900">
              {percentage.toFixed(0)}%
            </div>
            <div className="text-sm text-gray-500 mt-1">Complete</div>
          </div>
        </div>
      </div>

      {/* Goal Details */}
      <div className="mt-6 space-y-3">
        <div className="flex items-center justify-between p-3 bg-white/50 rounded-lg">
          <span className="text-sm text-gray-600">Current</span>
          <span className="text-sm font-semibold text-gray-900">
            ZMW {current.toLocaleString()}
          </span>
        </div>
        
        <div className="flex items-center justify-between p-3 bg-white/50 rounded-lg">
          <span className="text-sm text-gray-600">Target</span>
          <span className="text-sm font-semibold text-gray-900">
            ZMW {goal.toLocaleString()}
          </span>
        </div>

        {remaining > 0 && (
          <div className="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
            <span className="text-sm text-green-700 font-medium">To Go</span>
            <span className="text-sm font-bold text-green-700">
              ZMW {remaining.toLocaleString()}
            </span>
          </div>
        )}

        {percentage >= 100 && (
          <div className="flex items-center gap-2 p-3 bg-green-500 text-white rounded-lg">
            <TrendingUp size={16} />
            <span className="text-sm font-medium">Goal achieved! 🎉</span>
          </div>
        )}
      </div>
    </div>
  );
};

export default MonthlyGoalCard;

