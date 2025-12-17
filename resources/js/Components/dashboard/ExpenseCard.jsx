import React from 'react';
import { Card } from '../ui/Card';
import { TrendingDown } from 'lucide-react';

export function ExpenseCard({ 
  amount, 
  subtitle,
  percentageChange,
  changeLabel,
  onAddExpense,
  dismissible = true
}) {
  const isPositive = percentageChange > 0;
  
  return (
    <Card dismissible={dismissible} className="h-full flex flex-col">
      <div className="mb-6">
        <h3 className="text-sm font-medium text-gray-700 mb-2">
          Expenses
        </h3>
        <div className="flex items-start justify-between">
          <div>
            <p className="text-4xl font-bold text-teal-500">
              {amount}
            </p>
            <p className="text-sm text-gray-500 mt-1">
              {subtitle}
            </p>
          </div>
          <div className="text-mint-300 opacity-40">
            <TrendingDown className="h-16 w-16" />
          </div>
        </div>
      </div>
      
      <div className="flex items-center justify-between">
        <button
          onClick={onAddExpense}
          className="bg-teal-500 hover:bg-teal-600 text-white font-medium px-6 py-3 rounded-lg transition-colors"
        >
          Add Expense
        </button>
        
        <div className="text-right">
          <p className={`text-3xl font-bold ${isPositive ? 'text-green-500' : 'text-teal-500'}`}>
            {percentageChange}%
          </p>
          <p className="text-xs text-gray-400 mt-1 leading-tight">
            {changeLabel}
          </p>
        </div>
      </div>
    </Card>
  );
}

