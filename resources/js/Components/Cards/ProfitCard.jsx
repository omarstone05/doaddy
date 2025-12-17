import React from 'react';
import { TrendingUp, ArrowUpRight, ArrowDownRight } from 'lucide-react';

const ProfitCard = ({ data }) => {
  const { amount = 0, change = 0, revenue = 0, expenses = 0 } = data || {};
  const isPositive = amount >= 0;
  const changePositive = change >= 0;

  const formatCurrency = (value) => {
    if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
    if (value >= 1000) return `${(value / 1000).toFixed(1)}K`;
    return value.toFixed(0);
  };

  return (
    <div className={`rounded-2xl p-6 border relative overflow-hidden hover:shadow-lg transition-all duration-300 ${isPositive ? 'bg-gradient-to-br from-teal-50 to-mint-50 border-teal-100/50' : 'bg-gradient-to-br from-red-50 to-orange-50 border-red-100/50'}`}>
      {/* Background icon decoration */}
      <div className="absolute bottom-0 right-0 opacity-[0.08] pointer-events-none" style={{ marginBottom: '-20%', marginRight: '-10%' }}>
        <TrendingUp className={isPositive ? 'text-teal-500' : 'text-red-500'} strokeWidth={1.5} style={{ width: '140px', height: '140px' }} />
      </div>

      <div className="relative z-10">
        {/* Title */}
        <p className="text-sm font-semibold text-gray-600 mb-4">Net Profit</p>

        {/* Value */}
        <div className="flex items-baseline gap-3 mb-2">
          <p className="text-4xl font-black tracking-tight text-gray-900">
            K {formatCurrency(Math.abs(amount))}
          </p>
          {typeof change === 'number' && (
            <div className={`flex items-center text-sm font-semibold px-2 py-0.5 rounded-md ${changePositive ? 'bg-teal-50 text-teal-600' : 'bg-red-50 text-red-600'}`}>
              {changePositive ? <ArrowUpRight className="h-3.5 w-3.5 mr-0.5" /> : <ArrowDownRight className="h-3.5 w-3.5 mr-0.5" />}
              {Math.abs(change).toFixed(1)}%
            </div>
          )}
        </div>

        {/* Breakdown */}
        <div className="mt-4 pt-4 border-t border-gray-200/50 space-y-2">
          <div className="flex justify-between text-sm">
            <span className="text-gray-500">Revenue</span>
            <span className="font-semibold text-teal-600">K {formatCurrency(revenue)}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span className="text-gray-500">Expenses</span>
            <span className="font-semibold text-red-500">K {formatCurrency(expenses)}</span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProfitCard;
