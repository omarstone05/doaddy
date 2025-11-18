// Expenses Card - Small metric card with glassmorphism
import React from 'react';
import { TrendingDown, ArrowUp, ArrowDown } from 'lucide-react';

const ExpensesCard = ({ data }) => {
  const { amount = 0, change = 0, comparison = 0 } = data || {};
  const isIncrease = change >= 0;

  return (
    <div className="h-full">
      {/* Icon */}
      <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center mb-4">
        <TrendingDown className="text-white" size={24} />
      </div>

      {/* Label */}
      <div className="text-sm text-gray-600 mb-1">Monthly Expenses</div>

      {/* Amount */}
      <div className="text-3xl font-bold text-gray-900 mb-2">
        ZMW {amount.toLocaleString()}
      </div>

      {/* Change Indicator */}
      <div className="flex items-center gap-1 text-sm">
        {isIncrease ? (
          <ArrowUp size={16} className="text-red-500" />
        ) : (
          <ArrowDown size={16} className="text-green-500" />
        )}
        <span className={`font-medium ${isIncrease ? 'text-red-500' : 'text-green-500'}`}>
          {Math.abs(change).toFixed(1)}%
        </span>
        <span className="text-gray-500">vs last month</span>
      </div>

      {/* Mini Bar Chart - Simplified visualization */}
      <div className="mt-4 flex items-end gap-1 h-12">
        {[65, 72, 58, 80, 70, 85, 75].map((height, i) => (
          <div
            key={i}
            className="flex-1 bg-gradient-to-t from-red-500 to-red-400 rounded-t transition-all hover:opacity-80"
            style={{ height: `${height}%` }}
          />
        ))}
      </div>
    </div>
  );
};

export default ExpensesCard;

