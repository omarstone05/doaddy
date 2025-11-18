import React from 'react';
import { DollarSign, ArrowUp, ArrowDown } from 'lucide-react';

const ProfitCard = ({ data }) => {
  const { amount = 0, change = 0, revenue = 0, expenses = 0 } = data || {};
  const isPositive = change >= 0;

  return (
    <div className="h-full">
      {/* Icon */}
      <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center mb-4">
        <DollarSign className="text-white" size={24} />
      </div>

      {/* Label */}
      <div className="text-sm text-gray-600 mb-1">Net Profit</div>

      {/* Amount */}
      <div className="text-3xl font-bold text-gray-900 mb-2">
        ZMW {amount.toLocaleString()}
      </div>

      {/* Change */}
      <div className="flex items-center gap-1 text-sm mb-3">
        {isPositive ? (
          <ArrowUp size={16} className="text-green-500" />
        ) : (
          <ArrowDown size={16} className="text-red-500" />
        )}
        <span className={`font-medium ${isPositive ? 'text-green-500' : 'text-red-500'}`}>
          {Math.abs(change).toFixed(1)}%
        </span>
        <span className="text-gray-500">change</span>
      </div>

      {/* Breakdown */}
      <div className="mt-4 pt-4 border-t border-gray-200 space-y-2">
        <div className="flex justify-between text-xs">
          <span className="text-gray-600">Revenue</span>
          <span className="font-medium text-green-600">ZMW {revenue.toLocaleString()}</span>
        </div>
        <div className="flex justify-between text-xs">
          <span className="text-gray-600">Expenses</span>
          <span className="font-medium text-red-600">ZMW {expenses.toLocaleString()}</span>
        </div>
      </div>
    </div>
  );
};

export default ProfitCard;

