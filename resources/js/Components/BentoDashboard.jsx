import React, { useState, useEffect, useMemo } from 'react';
import { router, usePage } from '@inertiajs/react';
import { InsightsCard } from '@/Components/dashboard/InsightsCard';
import { getOrganizationTheme } from '@/utils/themeColors';
import BentoCardWrapper from '@/Components/Cards/BentoCardWrapper';
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
  FileText,
  Search,
  Grid3x3,
  ArrowUp,
  ArrowDown,
  AlertTriangle,
  ExternalLink
} from 'lucide-react';
import { LineChart, Line, AreaChart, Area, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';

// Format currency helper
const formatCurrency = (amount, currency = 'ZMW') => {
  return new Intl.NumberFormat('en-ZM', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
};

// Modern Revenue Card with glassmorphism and dynamic sparkline
const RevenueCard = ({ onRemove, stats, theme }) => {
  const revenue = stats?.total_revenue || 0;
  const previousRevenue = stats?.previous_revenue || 0;
  const revenueTrend = stats?.revenue_trend || [];
  const percentageChange = previousRevenue > 0 
    ? ((revenue - previousRevenue) / previousRevenue * 100).toFixed(1)
    : 0;
  const isPositive = percentageChange >= 0;

  // Generate sparkline data from trend or create sample
  const sparklineData = revenueTrend.length > 0 
    ? revenueTrend.map((item, i) => ({ value: item, day: i }))
    : Array.from({ length: 7 }, (_, i) => ({ value: Math.random() * 100 + 50, day: i }));

  return (
    <div className={`bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 relative group hover:shadow-xl transition-all h-full`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-white/80 hover:bg-white rounded-lg p-1.5 z-10 shadow-sm">
        <X size={14} className="text-gray-600" />
      </button>
      
      {/* Icon */}
      <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center mb-4 shadow-lg">
        <TrendingUp className="text-white" size={24} />
      </div>

      {/* Label */}
      <div className="text-sm text-gray-600 mb-1">Revenue This Month</div>

      {/* Amount */}
      <div className="text-3xl font-bold text-gray-900 mb-2">
        {formatCurrency(revenue)}
      </div>

      {/* Change Indicator */}
      <div className="flex items-center gap-1 text-sm mb-4">
        {isPositive ? (
          <ArrowUp size={16} className="text-green-500" />
        ) : (
          <ArrowDown size={16} className="text-red-500" />
        )}
        <span className={`font-medium ${isPositive ? 'text-green-500' : 'text-red-500'}`}>
          {Math.abs(percentageChange)}%
        </span>
        <span className="text-gray-500">vs last month</span>
      </div>

      {/* Dynamic Sparkline Chart */}
      <div className="h-16 -mx-2">
        <ResponsiveContainer width="100%" height="100%">
          <AreaChart data={sparklineData}>
            <defs>
              <linearGradient id="revenueGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#7DCD85" stopOpacity={0.3}/>
                <stop offset="95%" stopColor="#7DCD85" stopOpacity={0}/>
              </linearGradient>
            </defs>
            <Area
              type="monotone"
              dataKey="value"
              stroke="#7DCD85"
              strokeWidth={2}
              fill="url(#revenueGradient)"
              dot={false}
            />
          </AreaChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
};

// Modern Quick Actions Card with glassmorphism
const QuickActionsCard = ({ onRemove, theme }) => {
  const actions = [
    { label: 'New Sale', icon: ShoppingCart, url: '/pos', color: 'from-blue-400 to-blue-600' },
    { label: 'Add Expense', icon: DollarSign, url: '/money/movements/create?type=expense', color: 'from-red-400 to-red-600' },
    { label: 'Create Invoice', icon: FileText, url: '/invoices/create', color: 'from-green-400 to-green-600' },
    { label: 'Add Product', icon: Package, url: '/products/create', color: 'from-purple-400 to-purple-600' },
  ];

  return (
    <div className={`bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 relative group hover:shadow-xl transition-all h-full flex flex-col`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-white/80 hover:bg-white rounded-lg p-1.5 z-10 shadow-sm">
        <X size={14} className="text-gray-600" />
      </button>
      
      {/* Header */}
      <div className="flex items-center gap-3 mb-4">
        <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center shadow-lg">
          <Zap className="text-white" size={20} />
        </div>
        <div>
          <h3 className="text-lg font-semibold text-gray-900">Quick Actions</h3>
          <p className="text-xs text-gray-500">Common tasks</p>
        </div>
      </div>

      {/* Actions Grid */}
      <div className="grid grid-cols-2 gap-3 flex-1">
        {actions.map((action, index) => {
          const Icon = action.icon;
          return (
            <button
              key={index}
              onClick={() => router.visit(action.url)}
              className="group/action bg-white/60 hover:bg-white/90 backdrop-blur-sm border border-white/30 rounded-xl p-3 transition-all hover:shadow-md hover:scale-105"
            >
              <div className={`w-8 h-8 rounded-lg bg-gradient-to-br ${action.color} flex items-center justify-center mb-2 shadow-sm`}>
                <Icon className="text-white" size={16} />
              </div>
              <span className="text-xs font-medium text-gray-700 group-hover/action:text-gray-900">
                {action.label}
              </span>
            </button>
          );
        })}
      </div>
    </div>
  );
};

// Modern Recent Transactions Card with glassmorphism
const RecentTransactionsCard = ({ onRemove, stats, theme }) => {
  const recentSales = stats?.recent_sales || [];
  
  return (
    <div className={`bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 relative group hover:shadow-xl transition-all h-full flex flex-col`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-white/80 hover:bg-white rounded-lg p-1.5 z-10 shadow-sm">
        <X size={14} className="text-gray-600" />
      </button>
      
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg">
            <Receipt className="text-white" size={20} />
          </div>
          <div>
            <h3 className="text-lg font-semibold text-gray-900">Recent Sales</h3>
            <p className="text-xs text-gray-500">Latest transactions</p>
          </div>
        </div>
        <button 
          onClick={() => router.visit('/sales')}
          className="text-xs text-teal-600 hover:text-teal-700 font-medium flex items-center gap-1"
        >
          View all
          <ExternalLink size={12} />
        </button>
      </div>

      {/* Transactions List */}
      <div className="flex-1 space-y-2 overflow-y-auto">
        {recentSales.length > 0 ? (
          recentSales.slice(0, 5).map((sale) => (
            <div
              key={sale.id}
              className="flex items-center justify-between p-3 bg-white/50 hover:bg-white/80 rounded-lg transition-all group/item"
            >
              <div className="flex items-center gap-3 flex-1 min-w-0">
                <div className="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                  <ShoppingCart size={14} className="text-green-600" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-900 truncate">
                    {sale.customer_name || 'Walk-in Customer'}
                  </p>
                  <p className="text-xs text-gray-500">
                    {sale.created_at ? new Date(sale.created_at).toLocaleDateString() : 'Today'}
                  </p>
                </div>
              </div>
              <span className="text-sm font-semibold text-green-600 ml-2">
                {formatCurrency(sale.total)}
              </span>
            </div>
          ))
        ) : (
          <div className="flex flex-col items-center justify-center h-full text-gray-400 py-8">
            <Receipt size={48} className="mb-3 opacity-20" />
            <p className="text-sm">No recent sales</p>
          </div>
        )}
      </div>
    </div>
  );
};

// Modern Inventory Card with glassmorphism and dynamic chart
const InventoryCard = ({ onRemove, stats, theme }) => {
  const lowStock = stats?.low_stock_products || [];
  const totalProducts = stats?.total_products || 0;
  const stockLevels = stats?.stock_levels || { inStock: 80, lowStock: 15, outOfStock: 5 };
  
  // Pie chart data
  const pieData = [
    { name: 'In Stock', value: stockLevels.inStock || 80, color: '#7DCD85' },
    { name: 'Low Stock', value: stockLevels.lowStock || 15, color: '#F59E0B' },
    { name: 'Out of Stock', value: stockLevels.outOfStock || 5, color: '#EF4444' },
  ];

  return (
    <div className={`bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 relative group hover:shadow-xl transition-all h-full flex flex-col`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-white/80 hover:bg-white rounded-lg p-1.5 z-10 shadow-sm">
        <X size={14} className="text-gray-600" />
      </button>
      
      {/* Header */}
      <div className="flex items-center gap-3 mb-4">
        <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-lg">
          <Package className="text-white" size={20} />
        </div>
        <div>
          <h3 className="text-lg font-semibold text-gray-900">Inventory</h3>
          <p className="text-xs text-gray-500">Stock status</p>
        </div>
      </div>

      {/* Main Metric */}
      <div className="mb-4">
        <div className="text-3xl font-bold text-gray-900 mb-1">{totalProducts}</div>
        <div className="text-sm text-gray-600">Total products</div>
      </div>

      {/* Pie Chart */}
      <div className="flex-1 flex items-center justify-center -mx-2">
        <ResponsiveContainer width="100%" height={120}>
          <PieChart>
            <Pie
              data={pieData}
              cx="50%"
              cy="50%"
              innerRadius={30}
              outerRadius={50}
              paddingAngle={2}
              dataKey="value"
            >
              {pieData.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={entry.color} />
              ))}
            </Pie>
            <Tooltip />
          </PieChart>
        </ResponsiveContainer>
      </div>

      {/* Status Indicators */}
      <div className="space-y-2 pt-4 border-t border-gray-200">
        {lowStock.length > 0 && (
          <div className="flex items-center gap-2 p-2 bg-orange-50 rounded-lg">
            <AlertTriangle size={16} className="text-orange-600" />
            <span className="text-xs font-medium text-orange-700">
              {lowStock.length} items low stock
            </span>
          </div>
        )}
        <div className="flex items-center justify-between text-xs">
          <div className="flex items-center gap-2">
            <div className="w-2 h-2 rounded-full bg-green-500"></div>
            <span className="text-gray-600">In Stock</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-2 h-2 rounded-full bg-orange-500"></div>
            <span className="text-gray-600">Low Stock</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-2 h-2 rounded-full bg-red-500"></div>
            <span className="text-gray-600">Out</span>
          </div>
        </div>
      </div>
    </div>
  );
};

// Modern Customers Card with glassmorphism and growth chart
const CustomersCard = ({ onRemove, stats, theme }) => {
  const totalCustomers = stats?.total_customers || 0;
  const customerGrowth = stats?.customer_growth_rate || 0;
  const customerTrend = stats?.customer_trend || [];
  
  // Generate trend data
  const trendData = customerTrend.length > 0
    ? customerTrend.map((val, i) => ({ month: i, customers: val }))
    : Array.from({ length: 6 }, (_, i) => ({ month: i, customers: Math.random() * 20 + 10 }));

  return (
    <div className={`bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 relative group hover:shadow-xl transition-all h-full flex flex-col`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-white/80 hover:bg-white rounded-lg p-1.5 z-10 shadow-sm">
        <X size={14} className="text-gray-600" />
      </button>
      
      {/* Icon */}
      <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center mb-4 shadow-lg">
        <Users className="text-white" size={24} />
      </div>

      {/* Label */}
      <div className="text-sm text-gray-600 mb-1">Total Customers</div>

      {/* Amount */}
      <div className="text-3xl font-bold text-gray-900 mb-2">
        {totalCustomers.toLocaleString()}
      </div>

      {/* Growth Indicator */}
      {customerGrowth > 0 && (
        <div className="flex items-center gap-1 text-sm mb-4">
          <ArrowUp size={16} className="text-green-500" />
          <span className="font-medium text-green-500">
            +{customerGrowth}%
          </span>
          <span className="text-gray-500">growth</span>
        </div>
      )}

      {/* Mini Growth Chart */}
      <div className="flex-1 h-16 -mx-2">
        <ResponsiveContainer width="100%" height="100%">
          <AreaChart data={trendData}>
            <defs>
              <linearGradient id="customerGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#6366F1" stopOpacity={0.3}/>
                <stop offset="95%" stopColor="#6366F1" stopOpacity={0}/>
              </linearGradient>
            </defs>
            <Area
              type="monotone"
              dataKey="customers"
              stroke="#6366F1"
              strokeWidth={2}
              fill="url(#customerGradient)"
              dot={false}
            />
          </AreaChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
};

// Modern Expenses Card with glassmorphism and dynamic bar chart
const ExpensesCard = ({ onRemove, stats, theme }) => {
  const expenses = stats?.total_expenses || 0;
  const previousExpenses = stats?.previous_expenses || 0;
  const expenseBreakdown = stats?.expense_breakdown || [];
  const percentageChange = previousExpenses > 0 
    ? ((expenses - previousExpenses) / previousExpenses * 100).toFixed(1)
    : 0;
  const isIncrease = percentageChange >= 0;

  // Generate bar chart data
  const barData = expenseBreakdown.length > 0
    ? expenseBreakdown.slice(0, 7)
    : Array.from({ length: 7 }, (_, i) => ({ 
        name: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][i],
        value: Math.random() * 100 + 20 
      }));

  return (
    <div className={`bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 relative group hover:shadow-xl transition-all h-full`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-white/80 hover:bg-white rounded-lg p-1.5 z-10 shadow-sm">
        <X size={14} className="text-gray-600" />
      </button>
      
      {/* Icon */}
      <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center mb-4 shadow-lg">
        <DollarSign className="text-white" size={24} />
      </div>

      {/* Label */}
      <div className="text-sm text-gray-600 mb-1">Monthly Expenses</div>

      {/* Amount */}
      <div className="text-3xl font-bold text-gray-900 mb-2">
        {formatCurrency(expenses)}
      </div>

      {/* Change Indicator */}
      <div className="flex items-center gap-1 text-sm mb-4">
        {isIncrease ? (
          <ArrowUp size={16} className="text-red-500" />
        ) : (
          <ArrowDown size={16} className="text-green-500" />
        )}
        <span className={`font-medium ${isIncrease ? 'text-red-500' : 'text-green-500'}`}>
          {Math.abs(percentageChange)}%
        </span>
        <span className="text-gray-500">vs last month</span>
      </div>

      {/* Dynamic Bar Chart */}
      <div className="h-16 -mx-2">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={barData}>
            <Bar dataKey="value" fill="#EF4444" radius={[4, 4, 0, 0]} />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
};

// Modern Pending Invoices Card with glassmorphism
const PendingInvoicesCard = ({ onRemove, stats, theme }) => {
  const pendingInvoices = stats?.pending_invoices || [];
  const totalAmount = pendingInvoices.reduce((sum, inv) => sum + (inv.amount || 0), 0);
  
  return (
    <div className={`bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 relative group hover:shadow-xl transition-all h-full flex flex-col`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-white/80 hover:bg-white rounded-lg p-1.5 z-10 shadow-sm">
        <X size={14} className="text-gray-600" />
      </button>
      
      {/* Icon */}
      <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center mb-4 shadow-lg">
        <FileText className="text-white" size={24} />
      </div>

      {/* Label */}
      <div className="text-sm text-gray-600 mb-1">Pending Invoices</div>

      {/* Count */}
      <div className="text-3xl font-bold text-gray-900 mb-2">
        {pendingInvoices.length}
      </div>

      {/* Total Amount */}
      {pendingInvoices.length > 0 && (
        <div className="mb-4">
          <div className="text-xs text-gray-500 mb-1">Total Amount</div>
          <div className="text-lg font-semibold text-orange-600">
            {formatCurrency(totalAmount)}
          </div>
        </div>
      )}

      {/* Status Badge */}
      <div className="mt-auto">
        {pendingInvoices.length > 0 ? (
          <div className="flex items-center gap-2 p-2 bg-orange-50 rounded-lg">
            <AlertTriangle size={16} className="text-orange-600" />
            <span className="text-xs font-medium text-orange-700">
              Requires attention
            </span>
          </div>
        ) : (
          <div className="flex items-center gap-2 p-2 bg-green-50 rounded-lg">
            <CheckSquare size={16} className="text-green-600" />
            <span className="text-xs font-medium text-green-700">
              All clear
            </span>
          </div>
        )}
      </div>
    </div>
  );
};

// Modern Performance Card with glassmorphism and dynamic charts
const PerformanceCard = ({ onRemove, stats, theme }) => {
  const netBalance = stats?.net_balance || 0;
  const revenue = stats?.total_revenue || 0;
  const expenses = stats?.total_expenses || 0;
  const performanceData = stats?.performance_trend || [];
  
  const profitMargin = revenue > 0 ? ((netBalance / revenue) * 100).toFixed(1) : 0;
  const expenseRatio = revenue > 0 ? ((expenses / revenue) * 100).toFixed(1) : 0;

  // Generate comparison chart data
  const comparisonData = performanceData.length > 0
    ? performanceData
    : [
        { name: 'Revenue', value: revenue, color: '#7DCD85' },
        { name: 'Expenses', value: expenses, color: '#EF4444' },
        { name: 'Profit', value: netBalance, color: '#00635D' },
      ];

  return (
    <div className={`bg-white/70 backdrop-blur-lg border border-white/20 rounded-2xl p-6 relative group hover:shadow-xl transition-all h-full flex flex-col`}>
      <button onClick={onRemove} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-white/80 hover:bg-white rounded-lg p-1.5 z-10 shadow-sm">
        <X size={14} className="text-gray-600" />
      </button>
      
      {/* Header */}
      <div className="flex items-center gap-3 mb-4">
        <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center shadow-lg">
          <BarChart3 className="text-white" size={20} />
        </div>
        <div>
          <h3 className="text-lg font-semibold text-gray-900">Performance</h3>
          <p className="text-xs text-gray-500">Key metrics</p>
        </div>
      </div>

      {/* Metrics */}
      <div className="space-y-4 flex-1">
        {/* Profit Margin */}
        <div>
          <div className="flex justify-between items-center mb-2">
            <span className="text-sm font-medium text-gray-700">Profit Margin</span>
            <span className={`text-sm font-bold ${profitMargin > 0 ? 'text-green-600' : 'text-red-600'}`}>
              {profitMargin}%
            </span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <div 
              className={`h-full rounded-full transition-all ${profitMargin > 0 ? 'bg-gradient-to-r from-green-400 to-green-600' : 'bg-gradient-to-r from-red-400 to-red-600'}`} 
              style={{ width: `${Math.min(100, Math.abs(profitMargin))}%` }}
            ></div>
          </div>
        </div>

        {/* Expense Ratio */}
        <div>
          <div className="flex justify-between items-center mb-2">
            <span className="text-sm font-medium text-gray-700">Expense Ratio</span>
            <span className="text-sm font-bold text-red-600">{expenseRatio}%</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <div 
              className="h-full rounded-full bg-gradient-to-r from-red-400 to-red-600 transition-all" 
              style={{ width: `${Math.min(100, expenseRatio)}%` }}
            ></div>
          </div>
        </div>

        {/* Comparison Chart */}
        <div className="mt-4 pt-4 border-t border-gray-200">
          <div className="text-xs text-gray-500 mb-2">Revenue vs Expenses</div>
          <div className="h-24">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={comparisonData}>
                <Bar dataKey="value" fill="#00635D" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
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
const BentoDashboard = ({ stats, user, modularCards = [], preloadedCardData = {} }) => {
  const { props } = usePage();
  // Get organization name from auth.user.organization if available, otherwise from user prop
  const organizationName = props?.auth?.user?.organization?.name || user?.organization?.name;
  const themeIndex = props?.auth?.user?.organization?.theme_index ?? 0;
  const theme = getOrganizationTheme(themeIndex);
  
  // Legacy card components
  const legacyCardComponents = {
    'addy-insights': AddyInsightsCard,
    'revenue': RevenueCard,
    'quick-actions': QuickActionsCard,
    'transactions': RecentTransactionsCard,
    'expenses': ExpensesCard,
    'inventory': InventoryCard,
    'customers': CustomersCard,
    'pending-invoices': PendingInvoicesCard,
    'performance': PerformanceCard,
  };

  // Convert modular cards to Bento format and merge with legacy cards
  const initialModularCards = useMemo(() => {
    if (!modularCards || modularCards.length === 0) {
      console.warn('No modular cards received from backend');
      return [];
    }
    console.log('Processing modular cards:', modularCards.length);
    return modularCards.map(card => ({
      id: card.id,
      active: false, // Start inactive, user can add them
      size: card.size === 'small' ? 'small' : card.size === 'wide' ? 'large' : card.size || 'medium',
      name: card.name,
      description: card.description,
      category: card.category,
      icon: card.icon,
      color: card.color,
      isModular: true, // Flag to identify modular cards
    }));
  }, [modularCards]);

  // Initialize availableCards with legacy cards only
  // Modular cards will be added in useEffect to avoid duplicates
  const [availableCards, setAvailableCards] = useState([
    { id: 'addy-insights', active: true, size: 'large', isModular: false },
    { id: 'revenue', active: true, size: 'large', isModular: false },
    { id: 'quick-actions', active: true, size: 'medium', isModular: false },
    { id: 'transactions', active: true, size: 'medium', isModular: false },
    { id: 'expenses', active: true, size: 'medium', isModular: false },
    { id: 'inventory', active: true, size: 'medium', isModular: false },
    { id: 'customers', active: true, size: 'small', isModular: false },
    { id: 'pending-invoices', active: true, size: 'small', isModular: false },
    { id: 'performance', active: true, size: 'medium', isModular: false },
  ]);

  const [showCardManager, setShowCardManager] = useState(false);
  const [cardSearchQuery, setCardSearchQuery] = useState('');

  // Load saved card preferences from localStorage
  useEffect(() => {
    const saved = localStorage.getItem('addy-bento-dashboard-cards');
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        // Only restore active state and size, not component references
        setAvailableCards(prev => {
          const savedMap = new Map(parsed.map(c => [c.id, { active: c.active, size: c.size }]));
          const existingIds = new Set(prev.map(c => c.id));
          
          // Merge saved cards with existing cards
          const merged = prev.map(card => {
            const saved = savedMap.get(card.id);
            if (saved) {
              return { ...card, active: saved.active, size: saved.size };
            }
            return card;
          });
          
          // Add any new modular cards that weren't in saved data AND aren't already in prev
          initialModularCards.forEach(modularCard => {
            if (!savedMap.has(modularCard.id) && !existingIds.has(modularCard.id)) {
              merged.push(modularCard);
            }
          });
          
          return merged;
        });
      } catch (e) {
        console.error('Failed to parse saved cards:', e);
        // Clear corrupted data
        localStorage.removeItem('addy-bento-dashboard-cards');
      }
    } else {
      // First time - merge modular cards (only if they don't already exist)
      setAvailableCards(prev => {
        const existingIds = new Set(prev.map(c => c.id));
        const newModular = initialModularCards.filter(c => !existingIds.has(c.id));
        console.log('Adding new modular cards:', newModular.length, newModular.map(c => c.id));
        if (newModular.length > 0) {
          return [...prev, ...newModular];
        }
        return prev;
      });
    }
  }, [initialModularCards]);

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
    setAvailableCards(prev => {
      // Check if card already exists in the list
      const cardExists = prev.find(c => c.id === cardId);
      if (cardExists) {
        // Just activate it if it exists
        return prev.map(card =>
          card.id === cardId ? { ...card, active: true } : card
        );
      } else {
        // Card doesn't exist, try to find it in modular cards
        const modularCard = initialModularCards.find(c => c.id === cardId);
        if (modularCard) {
          // Double-check it's not already in prev (shouldn't happen, but safety check)
          const alreadyExists = prev.some(c => c.id === cardId);
          if (!alreadyExists) {
            return [...prev, { ...modularCard, active: true }];
          }
        }
        return prev;
      }
    });
  };

  // Filter active cards and remove duplicates by ID (in case a card was added twice)
  const activeCards = availableCards
    .filter(card => card.active)
    .reduce((acc, card) => {
      // Only add if we haven't seen this ID before
      if (!acc.find(c => c.id === card.id)) {
        acc.push(card);
      }
      return acc;
    }, []);
  
  const inactiveCards = availableCards.filter(card => !card.active);
  
  // Filter cards by search query
  const filteredInactiveCards = inactiveCards.filter(card => {
    if (!cardSearchQuery) return true;
    const query = cardSearchQuery.toLowerCase();
    const name = (card.name || card.id).toLowerCase();
    const description = (card.description || '').toLowerCase();
    return name.includes(query) || description.includes(query);
  });

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

        {/* Enhanced Card Manager Modal */}
        {showCardManager && (
          <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div className="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[85vh] overflow-hidden flex flex-col">
              {/* Header */}
              <div className="p-6 border-b border-gray-200 flex-shrink-0">
                <div className="flex items-center justify-between mb-4">
                  <div>
                    <h2 className="text-2xl font-bold text-gray-900">Manage Dashboard Cards</h2>
                    <p className="text-sm text-gray-600 mt-1">
                      {inactiveCards.length} available cards • {activeCards.length} active
                    </p>
                  </div>
                  <button
                    onClick={() => setShowCardManager(false)}
                    className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                  >
                    <X size={24} className="text-gray-600" />
                  </button>
                </div>

                {/* Search */}
                <div className="relative">
                  <Search size={20} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                  <input
                    type="text"
                    placeholder="Search cards..."
                    value={cardSearchQuery}
                    onChange={(e) => setCardSearchQuery(e.target.value)}
                    className="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-sm"
                    autoFocus
                  />
                </div>
              </div>

              {/* Cards Grid */}
              <div className="flex-1 overflow-y-auto p-6">
                {filteredInactiveCards.length === 0 ? (
                  <div className="flex flex-col items-center justify-center h-full text-gray-500 py-12">
                    <Grid3x3 size={48} className="mb-3 opacity-20" />
                    <p className="text-lg font-medium">No cards available</p>
                    <p className="text-sm mt-1">
                      {cardSearchQuery ? 'Try a different search term' : 'All cards are active'}
                    </p>
                  </div>
                ) : (
                  <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                    {filteredInactiveCards.map((card) => {
                      const IconComponent = legacyCardComponents[card.id] ? null : 
                        (card.icon === 'TrendingUp' ? TrendingUp :
                         card.icon === 'DollarSign' ? DollarSign :
                         card.icon === 'LineChart' ? BarChart3 :
                         card.icon === 'Target' ? CheckSquare :
                         Package);
                      
                      return (
                        <button
                          key={card.id}
                          onClick={() => {
                            addCard(card.id);
                            setCardSearchQuery('');
                          }}
                          className="glass-card p-4 text-left hover:shadow-xl transition-all group border-2 border-transparent hover:border-teal-500"
                        >
                          {/* Icon */}
                          {IconComponent && (
                            <div
                              className="w-10 h-10 rounded-lg flex items-center justify-center mb-3 transition-transform group-hover:scale-110"
                              style={{
                                background: card.color 
                                  ? `linear-gradient(135deg, ${card.color}dd, ${card.color})`
                                  : 'linear-gradient(135deg, #00635Ddd, #00635D)'
                              }}
                            >
                              <IconComponent size={20} className="text-white" />
                            </div>
                          )}
                          
                          {/* Card Info */}
                          <h3 className="font-semibold text-gray-900 mb-1 group-hover:text-teal-600 transition-colors">
                            {card.name || card.id.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                          </h3>
                          <p className="text-xs text-gray-600 mb-2 line-clamp-2">
                            {card.description || 'Dashboard card'}
                          </p>

                          {/* Badges */}
                          <div className="flex flex-wrap gap-1.5">
                            <span className="px-2 py-0.5 bg-teal-100 text-teal-700 rounded text-xs font-medium">
                              {card.size || 'medium'}
                            </span>
                            {card.category && (
                              <span className="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs">
                                {card.category}
                              </span>
                            )}
                            {card.isModular && (
                              <span className="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs font-medium">
                                New
                              </span>
                            )}
                          </div>

                          {/* Add indicator */}
                          <div className="mt-3 pt-3 border-t border-gray-200 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span className="text-xs text-teal-600 font-medium flex items-center gap-1">
                              <Plus size={12} />
                              Click to add
                            </span>
                          </div>
                        </button>
                      );
                    })}
                  </div>
                )}
              </div>

              {/* Footer */}
              <div className="p-6 border-t border-gray-200 bg-gray-50 flex-shrink-0">
                <div className="flex items-center justify-between">
                  <div className="text-sm text-gray-600">
                    Showing {filteredInactiveCards.length} of {inactiveCards.length} available cards
                  </div>
                  <button
                    onClick={() => setShowCardManager(false)}
                    className="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors font-medium"
                  >
                    Close
                  </button>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Bento Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 auto-rows-auto">
          {activeCards.map(card => {
            // Check if it's a modular card
            const isModularCard = card.isModular || card.id.startsWith('finance.') || card.id.startsWith('pm.');
            const LegacyCardComponent = legacyCardComponents[card.id];
            
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
                {isModularCard ? (
                  // Render modular card using BentoCardWrapper
                  <BentoCardWrapper 
                    cardId={card.id}
                    onRemove={() => removeCard(card.id)} 
                    theme={theme}
                    preloadedData={preloadedCardData?.[card.id]}
                  />
                ) : LegacyCardComponent ? (
                  // Render legacy card
                  card.id === 'addy-insights' ? (
                    <LegacyCardComponent 
                      onRemove={() => removeCard(card.id)} 
                      userName={user?.name} 
                      organizationName={organizationName}
                      stats={stats} 
                    />
                  ) : (
                    <LegacyCardComponent onRemove={() => removeCard(card.id)} stats={stats} theme={theme} />
                  )
                ) : (
                  // Fallback for unknown cards
                  <div className={`${theme.cardBg} border ${theme.cardBorder} p-6 rounded-2xl relative group hover:shadow-lg transition-all h-full`}>
                    <button onClick={() => removeCard(card.id)} className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-100 hover:bg-gray-200 rounded-lg p-1 z-10">
                      <X size={16} />
                    </button>
                    <p className="text-gray-500">Card not found: {card.id}</p>
                  </div>
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

