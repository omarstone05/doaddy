import React from 'react';
import { TrendingUp, ArrowUp, ArrowDown } from 'lucide-react';

const RevenueCard = ({ data }) => {
  const { amount = 0, change = 0, comparison = 0 } = data || {};
  const isPositive = change >= 0;

  return (
    <div className="h-full">
      {/* Icon */}
      <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center mb-4">
        <TrendingUp className="text-white" size={24} />
      </div>

      {/* Label */}
      <div className="text-sm text-gray-600 mb-1">Monthly Revenue</div>

      {/* Amount */}
      <div className="text-3xl font-bold text-gray-900 mb-2">
        ZMW {amount.toLocaleString()}
      </div>

      {/* Change */}
      <div className="flex items-center gap-1 text-sm">
        {isPositive ? (
          <ArrowUp size={16} className="text-green-500" />
        ) : (
          <ArrowDown size={16} className="text-red-500" />
        )}
        <span className={`font-medium ${isPositive ? 'text-green-500' : 'text-red-500'}`}>
          {Math.abs(change).toFixed(1)}%
        </span>
        <span className="text-gray-500">vs last month</span>
      </div>

      {/* Mini Sparkline */}
      <div className="mt-4 h-12">
        <svg className="w-full h-full" viewBox="0 0 100 30">
          <path
            d="M 0 25 L 20 20 L 40 22 L 60 15 L 80 10 L 100 5"
            fill="none"
            stroke="#7DCD85"
            strokeWidth="2"
            strokeLinecap="round"
          />
        </svg>
      </div>
    </div>
  );
};

export default RevenueCard;

