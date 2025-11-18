import React from 'react';
import { ArrowLeftRight, ArrowDownRight, ArrowUpRight } from 'lucide-react';

const CashFlowCard = ({ data }) => {
  const { income = 0, outgoing = 0, net = 0 } = data || {};
  const isPositive = net >= 0;

  return (
    <div className="h-full">
      {/* Icon */}
      <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center mb-4">
        <ArrowLeftRight className="text-white" size={24} />
      </div>

      {/* Label */}
      <div className="text-sm text-gray-600 mb-1">Cash Flow</div>

      {/* Net Amount */}
      <div className={`text-3xl font-bold mb-2 ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
        {isPositive ? '+' : ''}ZMW {Math.abs(net).toLocaleString()}
      </div>

      {/* Flow Breakdown */}
      <div className="mt-4 space-y-2">
        <div className="flex items-center justify-between p-2 bg-green-50 rounded-lg">
          <div className="flex items-center gap-2">
            <ArrowDownRight size={16} className="text-green-600" />
            <span className="text-xs text-gray-600">Money In</span>
          </div>
          <span className="text-sm font-semibold text-green-600">
            ZMW {income.toLocaleString()}
          </span>
        </div>
        
        <div className="flex items-center justify-between p-2 bg-red-50 rounded-lg">
          <div className="flex items-center gap-2">
            <ArrowUpRight size={16} className="text-red-600" />
            <span className="text-xs text-gray-600">Money Out</span>
          </div>
          <span className="text-sm font-semibold text-red-600">
            ZMW {outgoing.toLocaleString()}
          </span>
        </div>
      </div>
    </div>
  );
};

export default CashFlowCard;

