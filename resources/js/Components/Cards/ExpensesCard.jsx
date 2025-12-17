import React from 'react';
import { Receipt, ArrowUpRight, ArrowDownRight } from 'lucide-react';

const ExpensesCard = ({ data }) => {
  const { amount = 0, change = 0 } = data || {};
  const isIncrease = change >= 0;
  // For expenses, increase is bad (red), decrease is good (green)
  const isPositive = !isIncrease;

  const formatCurrency = (value) => {
    if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
    if (value >= 1000) return `${(value / 1000).toFixed(1)}K`;
    return value.toFixed(0);
  };

  return (
    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 relative overflow-hidden hover:shadow-lg hover:border-teal-200 transition-all duration-300">
      {/* Background icon decoration */}
      <div className="absolute bottom-0 right-0 opacity-[0.08] pointer-events-none" style={{ marginBottom: '-20%', marginRight: '-10%' }}>
        <Receipt className="text-teal-500" strokeWidth={1.5} style={{ width: '140px', height: '140px' }} />
      </div>

      <div className="relative z-10">
        {/* Title */}
        <p className="text-sm font-semibold text-gray-600 mb-4">Monthly Expenses</p>

        {/* Value */}
        <div className="flex items-baseline gap-3 mb-2">
          <p className="text-4xl font-black tracking-tight text-gray-900">
            K {formatCurrency(amount)}
          </p>
          <div className={`flex items-center text-sm font-semibold px-2 py-0.5 rounded-md ${isPositive ? 'bg-teal-50 text-teal-600' : 'bg-red-50 text-red-600'}`}>
            {isIncrease ? <ArrowUpRight className="h-3.5 w-3.5 mr-0.5" /> : <ArrowDownRight className="h-3.5 w-3.5 mr-0.5" />}
            {Math.abs(change).toFixed(1)}%
          </div>
        </div>

        {/* Subtitle */}
        <p className="text-sm text-gray-500">vs last month</p>
      </div>
    </div>
  );
};

export default ExpensesCard;
