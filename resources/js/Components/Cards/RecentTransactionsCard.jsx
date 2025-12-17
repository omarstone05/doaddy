import React from 'react';
import { Receipt, ArrowUpRight, ArrowDownRight, ArrowRight } from 'lucide-react';
import { Link } from '@inertiajs/react';

const RecentTransactionsCard = ({ data }) => {
  const { transactions = [] } = data || {};

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-ZM', {
      month: 'short',
      day: 'numeric',
    });
  };

  const formatCurrency = (value) => {
    if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
    if (value >= 1000) return `${(value / 1000).toFixed(1)}K`;
    return value.toFixed(0);
  };

  return (
    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 hover:shadow-lg hover:border-teal-200 transition-all duration-300">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h3 className="text-lg font-bold text-gray-900">Recent Transactions</h3>
          <p className="text-sm text-gray-500 mt-0.5">Latest financial activity</p>
        </div>
        <Link
          href="/money/movements"
          className="flex items-center gap-1 text-sm font-semibold text-teal-600 hover:text-teal-700 transition-colors"
        >
          View all
          <ArrowRight size={14} />
        </Link>
      </div>

      {/* Transactions List */}
      <div className="space-y-3">
        {transactions.length === 0 ? (
          <div className="text-center py-12">
            <div className="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center mx-auto mb-3">
              <Receipt className="w-6 h-6 text-teal-500" />
            </div>
            <p className="text-sm font-medium text-gray-600">No transactions yet</p>
            <p className="text-xs text-gray-400 mt-1">Activity will appear here</p>
          </div>
        ) : (
          transactions.slice(0, 5).map((transaction) => (
            <div
              key={transaction.id}
              className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-teal-50/50 transition-colors"
            >
              {/* Icon */}
              <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${
                transaction.flow_type === 'income' 
                  ? 'bg-teal-100' 
                  : 'bg-red-100'
              }`}>
                {transaction.flow_type === 'income' ? (
                  <ArrowDownRight size={18} className="text-teal-600" />
                ) : (
                  <ArrowUpRight size={18} className="text-red-500" />
                )}
              </div>

              {/* Details */}
              <div className="flex-1 min-w-0">
                <p className="text-sm font-semibold text-gray-900 truncate">
                  {transaction.description || 'Transaction'}
                </p>
                <div className="flex items-center gap-2 mt-0.5">
                  <span className="text-xs text-gray-500">{formatDate(transaction.date)}</span>
                  {transaction.category && (
                    <>
                      <span className="text-gray-300">•</span>
                      <span className="text-xs px-2 py-0.5 bg-gray-200/50 rounded-full text-gray-600">
                        {transaction.category}
                      </span>
                    </>
                  )}
                </div>
              </div>

              {/* Amount */}
              <p className={`text-sm font-bold whitespace-nowrap ${
                transaction.flow_type === 'income' ? 'text-teal-600' : 'text-red-500'
              }`}>
                {transaction.flow_type === 'income' ? '+' : '-'}K {formatCurrency(transaction.amount)}
              </p>
            </div>
          ))
        )}
      </div>

      {/* Summary */}
      {transactions.length > 0 && (
        <div className="mt-4 pt-4 border-t border-gray-200/50 grid grid-cols-2 gap-4">
          <div className="p-3 bg-teal-50 rounded-xl">
            <p className="text-xs text-gray-600 mb-1">Total In</p>
            <p className="text-lg font-bold text-teal-600">
              +K {formatCurrency(transactions
                .filter(t => t.flow_type === 'income')
                .reduce((sum, t) => sum + t.amount, 0))}
            </p>
          </div>
          <div className="p-3 bg-red-50 rounded-xl">
            <p className="text-xs text-gray-600 mb-1">Total Out</p>
            <p className="text-lg font-bold text-red-500">
              -K {formatCurrency(transactions
                .filter(t => t.flow_type === 'expense')
                .reduce((sum, t) => sum + t.amount, 0))}
            </p>
          </div>
        </div>
      )}
    </div>
  );
};

export default RecentTransactionsCard;
