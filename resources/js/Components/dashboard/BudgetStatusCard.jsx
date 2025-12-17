import React from 'react';
import { Card } from '../ui/Card';
import { AlertTriangle, CheckCircle } from 'lucide-react';

export function BudgetStatusCard({ budgets }) {
  const totalBudget = budgets?.reduce((sum, b) => sum + (b.budget || 0), 0) || 0;
  const totalSpent = budgets?.reduce((sum, b) => sum + (b.spent || 0), 0) || 0;
  const percentage = totalBudget > 0 ? (totalSpent / totalBudget) * 100 : 0;
  const isOverBudget = percentage > 100;
  const isWarning = percentage > 80;
  
  return (
    <Card className="h-full flex flex-col">
      <div className="mb-4">
        <h3 className="text-lg font-semibold text-gray-900 mb-1">Budget Status</h3>
        <p className="text-sm text-gray-500">Overall budget health</p>
      </div>
      
      <div className="flex-1 flex flex-col justify-center">
        <div className="mb-6">
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm text-gray-600">Used</span>
            <span className={`text-sm font-semibold ${isOverBudget ? 'text-red-600' : isWarning ? 'text-yellow-600' : 'text-green-600'}`}>
              {percentage.toFixed(1)}%
            </span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <div 
              className={`h-full transition-all ${
                isOverBudget ? 'bg-red-500' : isWarning ? 'bg-yellow-500' : 'bg-green-500'
              }`}
              style={{ width: `${Math.min(percentage, 100)}%` }}
            ></div>
          </div>
          {isOverBudget && (
            <div className="mt-2 flex items-center gap-1 text-red-600 text-xs">
              <AlertTriangle className="h-3 w-3" />
              <span>Over budget by {((percentage - 100) * totalBudget / 100).toFixed(0)}</span>
            </div>
          )}
        </div>
        
        <div className="space-y-2">
          <div className="flex justify-between items-center">
            <span className="text-sm text-gray-600">Total Budget</span>
            <span className="text-sm font-semibold text-gray-900">{totalBudget}</span>
          </div>
          <div className="flex justify-between items-center">
            <span className="text-sm text-gray-600">Total Spent</span>
            <span className="text-sm font-semibold text-gray-900">{totalSpent}</span>
          </div>
          <div className="pt-2 border-t border-gray-200 flex justify-between items-center">
            <span className="text-sm font-medium text-gray-900">Remaining</span>
            <span className={`text-sm font-bold ${totalBudget - totalSpent >= 0 ? 'text-green-600' : 'text-red-600'}`}>
              {totalBudget - totalSpent}
            </span>
          </div>
        </div>
        
        {budgets && budgets.length > 0 && (
          <div className="mt-4 pt-4 border-t border-gray-200">
            <p className="text-xs text-gray-500 mb-2">Top Budgets</p>
            <div className="space-y-2">
              {budgets.slice(0, 3).map((budget, index) => {
                const budgetPct = budget.budget > 0 ? (budget.spent / budget.budget) * 100 : 0;
                return (
                  <div key={index} className="flex items-center justify-between">
                    <span className="text-xs text-gray-600 truncate flex-1">{budget.name}</span>
                    <span className={`text-xs font-medium ml-2 ${budgetPct > 100 ? 'text-red-600' : budgetPct > 80 ? 'text-yellow-600' : 'text-green-600'}`}>
                      {budgetPct.toFixed(0)}%
                    </span>
                  </div>
                );
              })}
            </div>
          </div>
        )}
      </div>
    </Card>
  );
}

