import React, { useState, useEffect } from 'react';
import { MoreVertical, Pin, X, GripVertical } from 'lucide-react';

// Import card components
import RevenueCard from '../../Components/Cards/RevenueCard';
import ExpensesCard from '../../Components/Cards/ExpensesCard';
import ProfitCard from '../../Components/Cards/ProfitCard';
import CashFlowCard from '../../Components/Cards/CashFlowCard';
import RevenueChartCard from '../../Components/Cards/RevenueChartCard';
import MonthlyGoalCard from '../../Components/Cards/MonthlyGoalCard';
import RecentTransactionsCard from '../../Components/Cards/RecentTransactionsCard';
import ActiveProjectsCard from '../../Components/Cards/ActiveProjectsCard';

const CARD_COMPONENTS = {
  'finance.revenue': RevenueCard,
  'finance.expenses': ExpensesCard,
  'finance.profit': ProfitCard,
  'finance.cash_flow': CashFlowCard,
  'finance.revenue_chart': RevenueChartCard,
  'finance.monthly_goal': MonthlyGoalCard,
  'finance.recent_transactions': RecentTransactionsCard,
  'pm.active_projects': ActiveProjectsCard,
};

const DashboardCard = ({ cardId, size, pinned, onRemove, onPin }) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [showMenu, setShowMenu] = useState(false);

  // Fetch card data
  useEffect(() => {
    const fetchData = async () => {
      try {
        const response = await fetch(`/api/dashboard/card-data/${cardId}`);
        if (response.ok) {
          const cardData = await response.json();
          setData(cardData);
        }
      } catch (error) {
        console.error('Error fetching card data:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [cardId]);

  // Get appropriate component
  const CardComponent = CARD_COMPONENTS[cardId];

  // Grid column span based on size
  const sizeClasses = {
    small: 'lg:col-span-3',
    medium: 'lg:col-span-4',
    large: 'lg:col-span-8',
    wide: 'lg:col-span-12',
  };

  if (!CardComponent) {
    return (
      <div className={`${sizeClasses[size] || 'lg:col-span-4'}`}>
        <div className="glass-card p-6">
          <p className="text-gray-500">Card component not found: {cardId}</p>
        </div>
      </div>
    );
  }

  return (
    <div className={`${sizeClasses[size] || 'lg:col-span-4'}`}>
      <div className="glass-card group relative h-full">
        {/* Drag Handle & Menu */}
        <div className="absolute top-4 right-4 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity z-10">
          {!pinned && (
            <button
              className="p-1 hover:bg-gray-100 rounded cursor-move"
              title="Drag to reorder"
            >
              <GripVertical size={18} className="text-gray-400" />
            </button>
          )}
          
          <div className="relative">
            <button
              onClick={() => setShowMenu(!showMenu)}
              className="p-1 hover:bg-gray-100 rounded"
            >
              <MoreVertical size={18} className="text-gray-600" />
            </button>
            
            {showMenu && (
              <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-20">
                <button
                  onClick={() => {
                    onPin();
                    setShowMenu(false);
                  }}
                  className="w-full px-4 py-2 text-left hover:bg-gray-50 flex items-center gap-2 text-sm"
                >
                  <Pin size={16} />
                  {pinned ? 'Unpin' : 'Pin to dashboard'}
                </button>
                <button
                  onClick={() => {
                    onRemove();
                    setShowMenu(false);
                  }}
                  className="w-full px-4 py-2 text-left hover:bg-gray-50 flex items-center gap-2 text-sm text-red-600"
                >
                  <X size={16} />
                  Remove from dashboard
                </button>
              </div>
            )}
          </div>
          
          {pinned && (
            <Pin size={16} className="text-teal-500" />
          )}
        </div>

        {/* Card Content */}
        {loading ? (
          <div className="flex items-center justify-center h-32">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-500" />
          </div>
        ) : (
          <CardComponent data={data} />
        )}
      </div>
    </div>
  );
};

export default DashboardCard;

