// Recent Transactions Card - List style with glassmorphism
import React from 'react';
import { List, ArrowUpRight, ArrowDownRight, ExternalLink } from 'lucide-react';
import { Link } from '@inertiajs/react';

const RecentTransactionsCard = ({ data }) => {
  const { transactions = [] } = data || {};

  const getTransactionIcon = (flowType) => {
    return flowType === 'income' ? (
      <div className="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
        <ArrowDownRight size={16} className="text-green-600" />
      </div>
    ) : (
      <div className="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
        <ArrowUpRight size={16} className="text-red-600" />
      </div>
    );
  };

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-ZM', {
      month: 'short',
      day: 'numeric',
    });
  };

  return (
    <div className="h-full flex flex-col">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h3 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <List size={20} className="text-teal-500" />
            Recent Transactions
          </h3>
          <p className="text-sm text-gray-600 mt-1">Latest financial activity</p>
        </div>

        <Link
          href="/money/movements"
          className="text-sm text-teal-500 hover:text-teal-600 font-medium flex items-center gap-1"
        >
          View all
          <ExternalLink size={14} />
        </Link>
      </div>

      {/* Transactions List */}
      <div className="flex-1 space-y-2 overflow-y-auto">
        {transactions.length === 0 ? (
          <div className="flex flex-col items-center justify-center h-full text-gray-400">
            <List size={48} className="mb-3 opacity-20" />
            <p className="text-sm">No transactions yet</p>
          </div>
        ) : (
          transactions.map((transaction) => (
            <div
              key={transaction.id}
              className="flex items-center gap-3 p-3 bg-white/50 hover:bg-white/80 rounded-lg transition-all group cursor-pointer"
            >
              {/* Icon */}
              {getTransactionIcon(transaction.flow_type)}

              {/* Details */}
              <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between gap-2 mb-1">
                  <p className="text-sm font-medium text-gray-900 truncate">
                    {transaction.description || 'Transaction'}
                  </p>
                  <p className={`text-sm font-semibold whitespace-nowrap ${
                    transaction.flow_type === 'income' ? 'text-green-600' : 'text-red-600'
                  }`}>
                    {transaction.flow_type === 'income' ? '+' : '-'}
                    ZMW {transaction.amount.toLocaleString()}
                  </p>
                </div>
                
                <div className="flex items-center gap-2 text-xs text-gray-500">
                  <span>{formatDate(transaction.date)}</span>
                  {transaction.category && (
                    <>
                      <span>•</span>
                      <span className="px-2 py-0.5 bg-gray-100 rounded text-gray-600">
                        {transaction.category}
                      </span>
                    </>
                  )}
                </div>
              </div>

              {/* Hover indicator */}
              <ExternalLink 
                size={14} 
                className="text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" 
              />
            </div>
          ))
        )}
      </div>

      {/* Summary */}
      {transactions.length > 0 && (
        <div className="mt-4 pt-4 border-t border-gray-200 grid grid-cols-2 gap-4">
          <div>
            <div className="text-xs text-gray-500 mb-1">Total In</div>
            <div className="text-lg font-semibold text-green-600">
              +ZMW {transactions
                .filter(t => t.flow_type === 'income')
                .reduce((sum, t) => sum + t.amount, 0)
                .toLocaleString()}
            </div>
          </div>
          <div>
            <div className="text-xs text-gray-500 mb-1">Total Out</div>
            <div className="text-lg font-semibold text-red-600">
              -ZMW {transactions
                .filter(t => t.flow_type === 'expense')
                .reduce((sum, t) => sum + t.amount, 0)
                .toLocaleString()}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default RecentTransactionsCard;

