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
import ConsultingActiveProjectsCard from './ConsultingActiveProjectsCard';
import ProjectHealthCard from './ProjectHealthCard';
import TaskCompletionCard from './TaskCompletionCard';
import UpcomingDeadlinesCard from './UpcomingDeadlinesCard';
import ProjectProgressCard from './ProjectProgressCard';
import MyTasksCard from './MyTasksCard';

const CARD_COMPONENTS = {
  'finance.revenue': RevenueCard,
  'finance.expenses': ExpensesCard,
  'finance.profit': ProfitCard,
  'finance.cash_flow': CashFlowCard,
  'finance.revenue_chart': RevenueChartCard,
  'finance.monthly_goal': MonthlyGoalCard,
  'finance.recent_transactions': RecentTransactionsCard,
  'pm.active_projects': ActiveProjectsCard,
  'consulting.active_projects': ConsultingActiveProjectsCard,
  'consulting.project_health': ProjectHealthCard,
  'consulting.task_completion': TaskCompletionCard,
  'consulting.upcoming_deadlines': UpcomingDeadlinesCard,
  'consulting.project_progress': ProjectProgressCard,
  'consulting.my_tasks': MyTasksCard,
};

const BentoCardWrapper = ({ cardId, onRemove, theme, preloadedData }) => {
  const [data, setData] = useState(preloadedData || null);
  const [loading, setLoading] = useState(!preloadedData);

  // Fetch card data if not preloaded
  useEffect(() => {
    // If we have preloaded data, use it immediately
    if (preloadedData) {
      setData(preloadedData);
      setLoading(false);
      return;
    }

    // Otherwise fetch from API
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
  }, [cardId, preloadedData]);

  const CardComponent = CARD_COMPONENTS[cardId];

  if (!CardComponent) {
    return (
      <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 relative group hover:shadow-lg transition-all h-full">
        <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-100 hover:bg-gray-200 rounded-lg p-1.5 z-10">
          <X size={14} />
        </button>
        <p className="text-sm text-gray-500">Card not found: {cardId}</p>
      </div>
    );
  }

  return (
    <div className="relative group h-full">
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-white/80 hover:bg-white rounded-lg p-1.5 z-10 shadow-sm">
        <X size={14} className="text-gray-500" />
      </button>
      {loading ? (
        <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 h-full flex items-center justify-center">
          <div className="animate-spin rounded-full h-8 w-8 border-2 border-teal-500 border-t-transparent" />
        </div>
      ) : (
        <CardComponent data={data} />
      )}
    </div>
  );
};

export default BentoCardWrapper;
