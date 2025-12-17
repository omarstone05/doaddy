import React from 'react';
import { ArrowLeftRight, ArrowDownRight, ArrowUpRight } from 'lucide-react';

const CashFlowCard = ({ data }) => {
  const { income = 0, outgoing = 0, net = 0 } = data || {};
  const isPositive = net >= 0;

  const formatCurrency = (value) => {
    if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
    if (value >= 1000) return `${(value / 1000).toFixed(1)}K`;
    return value.toFixed(0);
  };

  return (
    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 relative overflow-hidden hover:shadow-lg hover:border-teal-200 transition-all duration-300">
      {/* Background icon decoration */}
      <div className="absolute bottom-0 right-0 opacity-[0.08] pointer-events-none" style={{ marginBottom: '-20%', marginRight: '-10%' }}>
        <ArrowLeftRight className="text-teal-500" strokeWidth={1.5} style={{ width: '140px', height: '140px' }} />
      </div>

      <div className="relative z-10">
        {/* Title */}
        <p className="text-sm font-semibold text-gray-600 mb-4">Cash Flow</p>

        {/* Net Amount */}
        <p className={`text-4xl font-black tracking-tight mb-2 ${isPositive ? 'text-teal-600' : 'text-red-500'}`}>
          {isPositive ? '+' : '-'}K {formatCurrency(Math.abs(net))}
        </p>

        {/* Flow Breakdown */}
        <div className="mt-4 space-y-2">
          <div className="flex items-center justify-between p-3 bg-teal-50 rounded-xl">
            <div className="flex items-center gap-2">
              <ArrowDownRight size={16} className="text-teal-600" />
              <span className="text-sm text-gray-600">Money In</span>
            </div>
            <span className="text-sm font-bold text-teal-600">
              K {formatCurrency(income)}
            </span>
          </div>
          
          <div className="flex items-center justify-between p-3 bg-red-50 rounded-xl">
            <div className="flex items-center gap-2">
              <ArrowUpRight size={16} className="text-red-500" />
              <span className="text-sm text-gray-600">Money Out</span>
            </div>
            <span className="text-sm font-bold text-red-500">
              K {formatCurrency(outgoing)}
            </span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CashFlowCard;
