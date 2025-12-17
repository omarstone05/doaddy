import React from 'react';
import { Card } from '../ui/Card';
import { TrendingUp, TrendingDown } from 'lucide-react';

export function ProfitMarginCard({ revenue, expenses, profit, margin, previousMargin }) {
  const marginChange = previousMargin ? margin - previousMargin : 0;
  const isPositive = marginChange >= 0;
  
  return (
    <Card className="h-full flex flex-col">
      <div className="mb-4">
        <h3 className="text-lg font-semibold text-gray-900 mb-1">Profit Margin</h3>
        <p className="text-sm text-gray-500">Financial health indicator</p>
      </div>
      
      <div className="flex-1 flex flex-col justify-center">
        <div className="mb-6">
          <div className="text-5xl font-bold text-teal-500 mb-2">
            {margin}%
          </div>
          {marginChange !== 0 && (
            <div className={`flex items-center gap-1 ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
              {isPositive ? <TrendingUp className="h-4 w-4" /> : <TrendingDown className="h-4 w-4" />}
              <span className="text-sm font-medium">
                {isPositive ? '+' : ''}{marginChange.toFixed(1)}% from previous
              </span>
            </div>
          )}
        </div>
        
        <div className="space-y-3">
          <div className="flex justify-between items-center">
            <span className="text-sm text-gray-600">Revenue</span>
            <span className="text-sm font-semibold text-gray-900">{revenue}</span>
          </div>
          <div className="flex justify-between items-center">
            <span className="text-sm text-gray-600">Expenses</span>
            <span className="text-sm font-semibold text-gray-900">{expenses}</span>
          </div>
          <div className="pt-3 border-t border-gray-200 flex justify-between items-center">
            <span className="text-sm font-medium text-gray-900">Net Profit</span>
            <span className="text-sm font-bold text-teal-500">{profit}</span>
          </div>
        </div>
      </div>
    </Card>
  );
}

