// Wrapper component to make modular cards work in BentoDashboard
import React, { useState, useEffect } from 'react';
import { X } from 'lucide-react';

// Import modular card components
import RevenueCard from './RevenueCard';
import ExpensesCard from './ExpensesCard';
import ProfitCard from './ProfitCard';
import CashFlowCard from './CashFlowCard';
import RevenueChartCard from './RevenueChartCard';
import MonthlyGoalCard from './MonthlyGoalCard';
import RecentTransactionsCard from './RecentTransactionsCard';
import ActiveProjectsCard from './ActiveProjectsCard';

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

const BentoCardWrapper = ({ cardId, onRemove, theme }) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

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

  const CardComponent = CARD_COMPONENTS[cardId];

  if (!CardComponent) {
    return (
      <div className={`${theme.cardBg} border ${theme.cardBorder} p-6 rounded-2xl relative group hover:shadow-lg transition-all h-full`}>
        <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-100 hover:bg-gray-200 rounded-lg p-1 z-10">
          <X size={16} />
        </button>
        <p className="text-gray-500">Card not found: {cardId}</p>
      </div>
    );
  }

  return (
    <div className={`${theme.cardBg} border ${theme.cardBorder} ${theme.cardHover} p-6 rounded-2xl relative group hover:shadow-lg transition-all h-full`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-100 hover:bg-gray-200 rounded-lg p-1 z-10">
        <X size={16} />
      </button>
      {loading ? (
        <div className="flex items-center justify-center h-32">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-500" />
        </div>
      ) : (
        <CardComponent data={data} />
      )}
    </div>
  );
};

export default BentoCardWrapper;

