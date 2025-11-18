import React, { useState, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import { InsightsCard } from '@/Components/dashboard/InsightsCard';
import { getOrganizationTheme } from '@/utils/themeColors';
import { 
  TrendingUp, 
  Zap, 
  Receipt, 
  Package, 
  Users, 
  CheckSquare, 
  Activity, 
  Bell, 
  BarChart3, 
  Sparkles,
  X,
  Plus,
  Settings,
  DollarSign,
  ShoppingCart,
  FileText
} from 'lucide-react';

// Format currency helper
const formatCurrency = (amount, currency = 'ZMW') => {
  return new Intl.NumberFormat('en-ZM', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
};

// Card Components with real data
const RevenueCard = ({ onRemove, stats, theme }) => {
  const revenue = stats?.total_revenue || 0;
  const previousRevenue = stats?.previous_revenue || 0;
  const revenueTrend = stats?.revenue_trend || 0;
  const percentageChange = previousRevenue > 0 
    ? ((revenue - previousRevenue) / previousRevenue * 100).toFixed(1)
    : 0;

  return (
    <div className={`bg-gradient-to-br ${theme.primaryGradient} text-white p-6 rounded-2xl relative group hover:shadow-lg transition-all h-full`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-white/20 hover:bg-white/30 rounded-lg p-1 z-10">
        <X size={16} />
      </button>
      <TrendingUp size={32} className="mb-4 opacity-90" />
      <h3 className="text-sm font-medium opacity-90 mb-1">Revenue This Month</h3>
      <p className="text-3xl font-bold">{formatCurrency(revenue)}</p>
      {percentageChange > 0 && (
        <p className="text-sm opacity-75 mt-2">↑ {percentageChange}% from last month</p>
      )}
      {percentageChange < 0 && (
        <p className="text-sm opacity-75 mt-2">↓ {Math.abs(percentageChange)}% from last month</p>
      )}
    </div>
  );
};

const QuickActionsCard = ({ onRemove, theme }) => (
  <div className={`${theme.cardBg} border ${theme.cardBorder} ${theme.cardHover} p-6 rounded-2xl relative group hover:shadow-lg transition-all h-full`}>
    <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-100 hover:bg-gray-200 rounded-lg p-1 z-10">
      <X size={16} />
    </button>
    <Zap size={28} className={`mb-4 ${theme.iconColor}`} />
    <h3 className="text-sm font-semibold text-gray-700 mb-3">Quick Actions</h3>
    <div className="space-y-2">
      <button 
        onClick={() => router.visit('/pos')}
        className="w-full text-left px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-sm transition-colors"
      >
        + New Sale
      </button>
      <button 
        onClick={() => router.visit('/money/movements/create?type=expense')}
        className="w-full text-left px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-sm transition-colors"
      >
        + Add Expense
      </button>
      <button 
        onClick={() => router.visit('/invoices/create')}
        className="w-full text-left px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-sm transition-colors"
      >
        + Create Invoice
      </button>
    </div>
  </div>
);

const RecentTransactionsCard = ({ onRemove, stats, theme }) => {
  const recentSales = stats?.recent_sales || [];
  
  return (
    <div className={`${theme.cardBg} border ${theme.cardBorder} ${theme.cardHover} p-6 rounded-2xl relative group hover:shadow-lg transition-all h-full`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-100 hover:bg-gray-200 rounded-lg p-1 z-10">
        <X size={16} />
      </button>
      <Receipt size={28} className={`mb-4 ${theme.iconColor}`} />
      <h3 className="text-sm font-semibold text-gray-700 mb-3">Recent Sales</h3>
      <div className="space-y-3">
        {recentSales.length > 0 ? (
          recentSales.slice(0, 3).map((sale) => (
            <div key={sale.id} className="flex justify-between items-center text-sm">
              <span className="text-gray-600 truncate">{sale.customer_name || 'Walk-in'}</span>
              <span className="font-semibold text-green-600">{formatCurrency(sale.total)}</span>
            </div>
          ))
        ) : (
          <p className="text-sm text-gray-400">No recent sales</p>
        )}
      </div>
    </div>
  );
};

const InventoryCard = ({ onRemove, stats, theme }) => {
  const lowStock = stats?.low_stock_products || [];
  const totalProducts = stats?.total_products || 0;
  
  return (
    <div className={`bg-gradient-to-br ${theme.secondaryGradient} text-white p-6 rounded-2xl relative group hover:shadow-lg transition-all h-full`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-white/20 hover:bg-white/30 rounded-lg p-1 z-10">
        <X size={16} />
      </button>
      <Package size={28} className="mb-4 opacity-90" />
      <h3 className="text-sm font-medium opacity-90 mb-1">Inventory Status</h3>
      <p className="text-3xl font-bold">{totalProducts}</p>
      <p className="text-sm opacity-75 mt-2">Items in stock</p>
      {lowStock.length > 0 && (
        <div className="mt-3 pt-3 border-t border-white/20">
          <p className="text-xs opacity-75">⚠️ {lowStock.length} items low stock</p>
        </div>
      )}
    </div>
  );
};

const CustomersCard = ({ onRemove, stats, theme }) => {
  const totalCustomers = stats?.total_customers || 0;
  const customerGrowth = stats?.customer_growth_rate || 0;
  
  return (
    <div className={`${theme.cardBg} border ${theme.cardBorder} ${theme.cardHover} p-6 rounded-2xl relative group hover:shadow-lg transition-all h-full`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-100 hover:bg-gray-200 rounded-lg p-1 z-10">
        <X size={16} />
      </button>
      <Users size={28} className={`mb-4 ${theme.iconColor}`} />
      <h3 className="text-sm font-semibold text-gray-700 mb-1">Customers</h3>
      <p className="text-3xl font-bold text-gray-900">{totalCustomers}</p>
      {customerGrowth > 0 && (
        <p className="text-sm text-gray-500 mt-1">+{customerGrowth}% growth</p>
      )}
    </div>
  );
};

const ExpensesCard = ({ onRemove, stats, theme }) => {
  const expenses = stats?.total_expenses || 0;
  const previousExpenses = stats?.previous_expenses || 0;
  const percentageChange = previousExpenses > 0 
    ? ((expenses - previousExpenses) / previousExpenses * 100).toFixed(1)
    : 0;

  return (
    <div className={`${theme.cardBg} border ${theme.cardBorder} ${theme.cardHover} p-6 rounded-2xl relative group hover:shadow-lg transition-all h-full`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-100 hover:bg-gray-200 rounded-lg p-1 z-10">
        <X size={16} />
      </button>
      <DollarSign size={28} className={`mb-4 ${theme.iconColor}`} />
      <h3 className="text-sm font-semibold text-gray-700 mb-1">Expenses</h3>
      <p className="text-3xl font-bold text-gray-900">{formatCurrency(expenses)}</p>
      {percentageChange !== 0 && (
        <p className={`text-sm mt-1 ${percentageChange > 0 ? 'text-red-600' : 'text-green-600'}`}>
          {percentageChange > 0 ? '↑' : '↓'} {Math.abs(percentageChange)}% from last month
        </p>
      )}
    </div>
  );
};

const PendingInvoicesCard = ({ onRemove, stats, theme }) => {
  const pendingInvoices = stats?.pending_invoices || [];
  
  return (
    <div className={`${theme.cardBg} border ${theme.cardBorder} ${theme.cardHover} p-6 rounded-2xl relative group hover:shadow-lg transition-all h-full`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-100 hover:bg-gray-200 rounded-lg p-1 z-10">
        <X size={16} />
      </button>
      <FileText size={28} className={`mb-4 ${theme.iconColor}`} />
      <h3 className="text-sm font-semibold text-gray-700 mb-1">Pending Invoices</h3>
      <p className="text-3xl font-bold text-gray-900">{pendingInvoices.length}</p>
      {pendingInvoices.length > 0 && (
        <p className="text-sm text-gray-500 mt-1">Requires attention</p>
      )}
    </div>
  );
};

const PerformanceCard = ({ onRemove, stats, theme }) => {
  const netBalance = stats?.net_balance || 0;
  const revenue = stats?.total_revenue || 0;
  const expenses = stats?.total_expenses || 0;
  
  const profitMargin = revenue > 0 ? ((netBalance / revenue) * 100).toFixed(1) : 0;
  const expenseRatio = revenue > 0 ? ((expenses / revenue) * 100).toFixed(1) : 0;

  return (
    <div className={`${theme.cardBg} border ${theme.cardBorder} ${theme.cardHover} p-6 rounded-2xl relative group hover:shadow-lg transition-all h-full`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-100 hover:bg-gray-200 rounded-lg p-1 z-10">
        <X size={16} />
      </button>
      <BarChart3 size={28} className={`mb-4 ${theme.iconColor}`} />
      <h3 className="text-sm font-semibold text-gray-700 mb-3">Performance</h3>
      <div className="space-y-2">
        <div>
          <div className="flex justify-between text-xs text-gray-600 mb-1">
            <span>Profit Margin</span>
            <span>{profitMargin}%</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div 
              className={`h-2 rounded-full ${profitMargin > 0 ? 'bg-green-500' : 'bg-red-500'}`} 
              style={{ width: `${Math.min(100, Math.abs(profitMargin))}%` }}
            ></div>
          </div>
        </div>
        <div>
          <div className="flex justify-between text-xs text-gray-600 mb-1">
            <span>Expense Ratio</span>
            <span>{expenseRatio}%</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div className="bg-orange-500 h-2 rounded-full" style={{ width: `${Math.min(100, expenseRatio)}%` }}></div>
          </div>
        </div>
      </div>
    </div>
  );
};

const AddyInsightsCard = ({ onRemove, userName, stats, organizationName }) => {
  // Addy Insights card is non-removable, so onRemove is ignored
  return (
    <div className="relative h-full">
      <InsightsCard 
        userName={userName || 'User'}
        organizationName={organizationName}
        message={stats?.net_balance >= 0 
          ? `You're looking good, there's a few things that we need to do though...`
          : `You have ${formatCurrency(Math.abs(stats?.net_balance || 0))} in expenses. Consider reviewing your budget.`
        }
      />
    </div>
  );
};

// Main Dashboard Component
const BentoDashboard = ({ stats, user }) => {
  const { props } = usePage();
  // Get organization name from auth.user.organization if available, otherwise from user prop
  const organizationName = props?.auth?.user?.organization?.name || user?.organization?.name;
  const themeIndex = props?.auth?.user?.organization?.theme_index ?? 0;
  const theme = getOrganizationTheme(themeIndex);
  
  // Import ProjectsOverviewCard
  const ProjectsOverviewCard = React.lazy(() => import('./dashboard/ProjectsOverviewCard').then(module => ({ default: module.ProjectsOverviewCard })));

  // Define card components map - components can't be serialized to localStorage
  const cardComponents = {
    'addy-insights': AddyInsightsCard,
    'revenue': RevenueCard,
    'quick-actions': QuickActionsCard,
    'transactions': RecentTransactionsCard,
    'expenses': ExpensesCard,
    'inventory': InventoryCard,
    'customers': CustomersCard,
    'pending-invoices': PendingInvoicesCard,
    'performance': PerformanceCard,
    'projects-overview': ProjectsOverviewCard,
  };

  const [availableCards, setAvailableCards] = useState([
    { id: 'addy-insights', active: true, size: 'large' },
    { id: 'revenue', active: true, size: 'large' },
    { id: 'quick-actions', active: true, size: 'medium' },
    { id: 'transactions', active: true, size: 'medium' },
    { id: 'expenses', active: true, size: 'medium' },
    { id: 'inventory', active: true, size: 'medium' },
    { id: 'customers', active: true, size: 'small' },
    { id: 'pending-invoices', active: true, size: 'small' },
    { id: 'performance', active: true, size: 'medium' },
    { id: 'projects-overview', active: false, size: 'medium' },
  ]);

  const [showCardManager, setShowCardManager] = useState(false);

  // Load saved card preferences from localStorage
  useEffect(() => {
    const saved = localStorage.getItem('addy-bento-dashboard-cards');
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        // Only restore active state and size, not component references
        setAvailableCards(prev => {
          const savedMap = new Map(parsed.map(c => [c.id, { active: c.active, size: c.size }]));
          return prev.map(card => {
            const saved = savedMap.get(card.id);
            if (saved) {
              return { ...card, active: saved.active, size: saved.size };
            }
            return card;
          });
        });
      } catch (e) {
        console.error('Failed to parse saved cards:', e);
        // Clear corrupted data
        localStorage.removeItem('addy-bento-dashboard-cards');
      }
    }
  }, []);

  // Save card preferences to localStorage (only active state and size, not components)
  useEffect(() => {
    const cardsToSave = availableCards.map(card => ({
      id: card.id,
      active: card.active,
      size: card.size,
    }));
    localStorage.setItem('addy-bento-dashboard-cards', JSON.stringify(cardsToSave));
  }, [availableCards]);

  const removeCard = (cardId) => {
    // Don't allow removing Addy Insights card
    if (cardId === 'addy-insights') return;
    
    setAvailableCards(prev =>
      prev.map(card =>
        card.id === cardId ? { ...card, active: false } : card
      )
    );
  };

  const addCard = (cardId) => {
    setAvailableCards(prev =>
      prev.map(card =>
        card.id === cardId ? { ...card, active: true } : card
      )
    );
  };

  const activeCards = availableCards.filter(card => card.active);
  const inactiveCards = availableCards.filter(card => !card.active);

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="flex justify-between items-center mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p className="text-gray-500 mt-1">Welcome back, {user?.name || 'User'}</p>
          </div>
          <button
            onClick={() => setShowCardManager(!showCardManager)}
            className="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <Settings size={18} />
            <span>Manage Cards</span>
          </button>
        </div>

        {/* Card Manager */}
        {showCardManager && (
          <div className="mb-6 p-4 bg-white border border-gray-200 rounded-xl">
            <h3 className="font-semibold text-gray-900 mb-3">Add Cards</h3>
            <div className="flex flex-wrap gap-2">
              {inactiveCards.map(card => (
                <button
                  key={card.id}
                  onClick={() => addCard(card.id)}
                  className="flex items-center gap-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition-colors"
                >
                  <Plus size={16} />
                  <span className="capitalize">{card.id.replace('-', ' ')}</span>
                </button>
              ))}
              {inactiveCards.length === 0 && (
                <p className="text-sm text-gray-500">All cards are currently active</p>
              )}
            </div>
          </div>
        )}

        {/* Bento Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 auto-rows-auto">
          {activeCards.map(card => {
            const CardComponent = cardComponents[card.id];
            
            // Skip if component not found (shouldn't happen, but safety check)
            if (!CardComponent) {
              console.warn(`Card component not found for id: ${card.id}`);
              return null;
            }
            
            // Dynamic sizing based on card size property
            const sizeClasses = {
              'small': 'lg:col-span-1 md:row-span-1',
              'medium': 'lg:col-span-2 md:row-span-1',
              'large': 'lg:col-span-2 md:row-span-2',
            };

            return (
              <div
                key={card.id}
                className={`${sizeClasses[card.size]} min-h-[200px] group`}
              >
                {card.id === 'addy-insights' ? (
                  <CardComponent 
                    onRemove={() => removeCard(card.id)} 
                    userName={user?.name} 
                    organizationName={organizationName}
                    stats={stats} 
                  />
                ) : card.id === 'projects-overview' ? (
                  <React.Suspense fallback={<div>Loading...</div>}>
                    <CardComponent 
                      onRemove={() => removeCard(card.id)} 
                      stats={stats?.project_stats} 
                      theme={theme} 
                    />
                  </React.Suspense>
                ) : (
                  <CardComponent onRemove={() => removeCard(card.id)} stats={stats} theme={theme} />
                )}
              </div>
            );
          })}
        </div>

        {/* Empty State */}
        {activeCards.length === 0 && (
          <div className="text-center py-16">
            <div className="bg-white border border-gray-200 rounded-2xl p-12 inline-block">
              <Package size={48} className="mx-auto text-gray-400 mb-4" />
              <h3 className="text-lg font-semibold text-gray-900 mb-2">No cards active</h3>
              <p className="text-gray-500 mb-4">Add some cards to get started</p>
              <button
                onClick={() => setShowCardManager(true)}
                className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
              >
                Manage Cards
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default BentoDashboard;

