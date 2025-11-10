import React, { useState, useMemo } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { InsightsCard } from '@/Components/dashboard/InsightsCard';
import { MetricCard } from '@/Components/dashboard/MetricCard';
import { GreenMetricCard } from '@/Components/dashboard/GreenMetricCard';
import { SalesTodayCard } from '@/Components/dashboard/SalesTodayCard';
import { ChartCard } from '@/Components/dashboard/ChartCard';
import { ExpenseCard } from '@/Components/dashboard/ExpenseCard';
import { QuickActionCard } from '@/Components/dashboard/QuickActionCard';
import { QuickActionsCard } from '@/Components/dashboard/QuickActionsCard';
import { QuickActionsBlock } from '@/Components/dashboard/QuickActionsBlock';
import { QuickActionModal } from '@/Components/dashboard/QuickActionModal';
import { CashFlowCard } from '@/Components/dashboard/CashFlowCard';
import { RevenueByCategoryCard } from '@/Components/dashboard/RevenueByCategoryCard';
import { ExpenseBreakdownCard } from '@/Components/dashboard/ExpenseBreakdownCard';
import { ProfitMarginCard } from '@/Components/dashboard/ProfitMarginCard';
import { RecentActivityCard } from '@/Components/dashboard/RecentActivityCard';
import { BudgetStatusCard } from '@/Components/dashboard/BudgetStatusCard';
import { CustomerGrowthCard } from '@/Components/dashboard/CustomerGrowthCard';
import { TeamPerformanceCard } from '@/Components/dashboard/TeamPerformanceCard';
import { ProjectStatusCard } from '@/Components/dashboard/ProjectStatusCard';
import { TimeframeSelector } from '@/Components/dashboard/TimeframeSelector';
import { AddCardModal } from '@/Components/dashboard/AddCardModal';
import { BentoGrid } from '@/Components/dashboard/BentoGrid';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/Card';
import { Layers, FolderOpen, PieChart, Package, FileText, AlertTriangle, Plus, DollarSign, ShoppingCart, Users, Receipt, CreditCard } from 'lucide-react';
import { GridEngine, GRID_CONFIG } from '@/lib/gridEngine';
import axios from 'axios';

export default function Dashboard({ user, availableCards, orgCards = [], stats, timeframe = 'today' }) {
  const [showAddCardModal, setShowAddCardModal] = useState(false);
  const [showQuickActionModal, setShowQuickActionModal] = useState(false);
  const [selectedQuickActionSlot, setSelectedQuickActionSlot] = useState(null);
  
  // Define available quick actions
  const availableQuickActions = [
    { id: 'qa-expense', title: 'Add Expense', icon: DollarSign, url: '/money/movements/create?type=expense' },
    { id: 'qa-invoice', title: 'Create Invoice', icon: FileText, url: '/invoices/create' },
    { id: 'qa-sale', title: 'New Sale', icon: ShoppingCart, url: '/pos' },
    { id: 'qa-product', title: 'Add Product', icon: Package, url: '/products/create' },
    { id: 'qa-quote', title: 'Create Quote', icon: Receipt, url: '/quotes/create' },
    { id: 'qa-customer', title: 'Add Customer', icon: Users, url: '/customers/create' },
    { id: 'qa-payment', title: 'Record Payment', icon: CreditCard, url: '/payments/create' },
  ];

  // Quick actions block ID
  const quickActionsBlockId = 'quick-actions-block';
  
  // Addy insight card ID (non-removable)
  const addyCardId = 'addy-insight-card';
  
  // State for assigned quick actions (stored in localStorage for persistence)
  const [assignedQuickActions, setAssignedQuickActions] = useState(() => {
    try {
      const stored = localStorage.getItem('dashboard_quick_actions');
      return stored ? JSON.parse(stored) : {};
    } catch {
      return {};
    }
  });

  const [cards, setCards] = useState(() => {
    // Create Addy insight card (6×2 in old system = 24×8 in new grid)
    const addyCard = {
      id: addyCardId,
      type: 'addy_insight',
      dashboard_card: { key: 'addy_insight', name: 'Addy Insights' },
      width: 24, // 6 * 4 = 24
      height: 8, // 8 units high
      row: undefined,
      col: undefined,
      display_order: 0,
    };

    // Filter out the old quick_actions card
    const regularCards = orgCards
      .filter(card => card.dashboard_card?.key !== 'quick_actions' && card.id !== addyCardId)
      .map((card) => {
        // Default to 8x8 for all regular cards
        // If card has existing dimensions, keep them; otherwise use 8x8
        const width = card.width || GRID_CONFIG.STANDARD_WIDTH;
        const height = card.height || GRID_CONFIG.STANDARD_HEIGHT;
        return {
          ...card,
          width,
          height,
          row: undefined, // Reset positions
          col: undefined,
        };
      });

    // Get existing quick action blocks from orgCards or create one default
    const existingQuickActionBlocks = orgCards.filter(card => 
      card.type === 'quick_actions_block' || 
      card.dashboard_card?.key === 'quick_actions_block' ||
      card.id?.startsWith('quick-actions-block')
    );

    // If no quick action blocks exist, create one default
    const quickActionBlocks = existingQuickActionBlocks.length > 0
      ? existingQuickActionBlocks.map(card => ({
          ...card,
          type: 'quick_actions_block',
          dashboard_card: { key: 'quick_actions_block', name: 'Quick Actions' },
          width: card.width || 8,
          height: card.height || 8,
          row: undefined,
          col: undefined,
          display_order: 999,
        }))
      : [{
          id: quickActionsBlockId,
          type: 'quick_actions_block',
          dashboard_card: { key: 'quick_actions_block', name: 'Quick Actions' },
          width: 8,
          height: 8,
          row: undefined,
          col: undefined,
          display_order: 999,
        }];

    // Combine all cards - Addy first, then quick action blocks, then regular cards
    const allCards = [addyCard, ...quickActionBlocks, ...regularCards];

    // Use GridEngine to auto-layout - this will place Addy at top, then quick actions, then regular cards
    const engine = new GridEngine();
    return engine.autoLayout(allCards);
  });

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-ZM', {
      style: 'currency',
      currency: 'ZMW',
      minimumFractionDigits: 2,
    }).format(amount || 0);
  };

  // Prepare chart data
  const revenueData = stats.revenue_trend?.map(item => ({
    name: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
    value: parseFloat(item.amount) || 0,
  })) || [];

  const comparisonData = [
    { name: 'Current', series1: stats.total_revenue || 0, series2: stats.total_expenses || 0 },
    { name: 'Previous', series1: stats.previous_revenue || 0, series2: stats.previous_expenses || 0 },
  ];

  const salesCount = stats.recent_sales?.length || 0;
  const expensePercentageChange = stats.previous_expenses 
    ? Math.round(((stats.total_expenses - stats.previous_expenses) / stats.previous_expenses) * 100)
    : 0;

  // Prepare dashboard items using GridEngine
  const dashboardItems = useMemo(() => {
    // Ensure all cards have proper dimensions
    const allCards = cards.map(card => {
      if (card.id === addyCardId) {
        return {
          ...card,
          width: card.width || 24,
          height: card.height || 8,
        };
      }
      if (card.type === 'quick_actions_block' || card.dashboard_card?.key === 'quick_actions_block' || card.id?.startsWith('quick-actions-block')) {
        return {
          ...card,
          width: card.width || 8,
          height: card.height || 8,
        };
      }
      // Regular cards default to 8x8
      return {
        ...card,
        width: card.width || GRID_CONFIG.STANDARD_WIDTH,
        height: card.height || GRID_CONFIG.STANDARD_HEIGHT,
      };
    });

    // Use GridEngine to ensure proper layout
    const engine = new GridEngine();
    return engine.autoLayout(allCards);
  }, [cards, addyCardId]);

  const handleReorder = (newItems) => {
    // Update local state with new positions
    const updatedCards = newItems.map((item) => {
      const existingCard = cards.find(c => c.id === item.id);
      if (existingCard) {
        return {
          ...existingCard,
          row: item.row,
          col: item.col,
          width: item.width,
          height: item.height,
        };
      }
      return item;
    });

    setCards(updatedCards);

    // Save to server (only for regular cards, not quick actions block or Addy card)
    const layouts = updatedCards
      .filter(card => {
        // Don't save Addy card layout
        if (card.id === addyCardId) return false;
        // Don't save quick actions blocks (frontend-only)
        if (card.type === 'quick_actions_block' || card.dashboard_card?.key === 'quick_actions_block' || card.id?.startsWith('quick-actions-block')) {
          return false;
        }
        return true;
      })
      .map((card, index) => ({
        id: card.id,
        row: card.row,
        col: card.col,
        width: card.width,
        height: card.height,
        display_order: index,
      }));

    // Only make API call if there are cards to save
    if (layouts.length > 0) {
      axios.post('/dashboard/cards/layout', { layouts })
        .catch(error => {
          console.error('Error saving layout:', error);
          // Optionally revert on error
        });
    }
  };

  const handleResize = React.useCallback((cardId, newWidth, newHeight) => {
    setCards(prevCards => {
      const engine = new GridEngine(prevCards);
      const card = prevCards.find(c => c.id === cardId);
      
      if (!card || card.id === addyCardId) {
        return prevCards; // Don't resize Addy card
      }

      // Check if resize is valid
      if (engine.checkCollision(card.row, card.col, newWidth, newHeight, cardId)) {
        console.warn('Resize would cause collision');
        return prevCards;
      }

      // Validate dimensions
      const validation = engine.validateCard({
        ...card,
        width: newWidth,
        height: newHeight,
      });

      if (!validation.valid) {
        console.warn('Invalid resize:', validation.errors);
        return prevCards;
      }

      const updatedCards = prevCards.map(c => 
        c.id === cardId 
          ? { ...c, width: newWidth, height: newHeight }
          : c
      );

      // Save to server (debounced - only save after resize ends)
      // Skip API call for quick actions blocks (frontend-only)
      const isQuickActionBlock = cardId?.startsWith('quick-actions-block') || 
                                 prevCards.find(c => c.id === cardId)?.type === 'quick_actions_block';
      if (!isQuickActionBlock && cardId !== addyCardId) {
        clearTimeout(window.resizeTimeout);
        window.resizeTimeout = setTimeout(() => {
          axios.post('/dashboard/cards/layout', {
            layouts: [{
              id: cardId,
              row: card.row,
              col: card.col,
              width: newWidth,
              height: newHeight,
              display_order: card.display_order,
            }]
          }).catch(error => {
            console.error('Error saving resize:', error);
          });
        }, 500); // Save 500ms after last resize
      }

      return updatedCards;
    });
  }, [addyCardId]);

  const handleRemoveCard = (cardId) => {
    if (cardId === addyCardId) {
      return; // Don't allow removing Addy card
    }

    // Check if it's a quick action block (frontend-only)
    const isQuickActionBlock = cardId?.startsWith('quick-actions-block') || 
                               cards.find(c => c.id === cardId)?.type === 'quick_actions_block';
    
    if (isQuickActionBlock) {
      // Quick action blocks are frontend-only, just remove from local state
      setCards(cards.filter(c => c.id !== cardId));
      // Also remove any assigned quick action for this block
      handleRemoveQuickAction(cardId);
      return;
    }

    // Regular cards are in database, remove via API
    router.delete(`/dashboard/cards/${cardId}`, {
      preserveScroll: true,
      onSuccess: () => {
        setCards(cards.filter(c => c.id !== cardId));
      },
    });
  };

  // Handle assigning a quick action to the block
  const handleAssignQuickAction = (blockId) => {
    setSelectedQuickActionSlot(blockId);
    setShowQuickActionModal(true);
  };

  // Handle selecting a quick action from the modal
  const handleSelectQuickAction = (action) => {
    setAssignedQuickActions(prev => {
      const updated = { ...prev, [selectedQuickActionSlot]: action };
      localStorage.setItem('dashboard_quick_actions', JSON.stringify(updated));
      return updated;
    });
    setShowQuickActionModal(false);
    setSelectedQuickActionSlot(null);
  };

  // Handle removing a quick action from the block
  const handleRemoveQuickAction = (blockId) => {
    setAssignedQuickActions(prev => {
      const updated = { ...prev };
      delete updated[blockId];
      localStorage.setItem('dashboard_quick_actions', JSON.stringify(updated));
      return updated;
    });
  };

  const handleAddCard = (cardId) => {
    router.post('/dashboard/cards/add', { dashboard_card_id: cardId }, {
      preserveScroll: true,
      onSuccess: () => {
        setShowAddCardModal(false);
        window.location.reload();
      },
      onError: (errors) => {
        console.error('Error adding card:', errors);
        // Still close modal even on error
        setShowAddCardModal(false);
      },
    });
  };

  // Render card content based on type
  const renderCardContent = (item) => {
    if (item.id === addyCardId) {
      return (
        <InsightsCard 
          userName={user?.name || 'User'}
          message={stats.net_balance >= 0 
            ? `You're looking good this ${timeframe}, there's a few things that we need to do though...`
            : `You have ${formatCurrency(Math.abs(stats.net_balance))} in expenses. Consider reviewing your budget.`
          }
        />
      );
    }

    const card = cards.find(c => c.id === item.id);
    if (!card) return null;

    const cardType = card.dashboard_card?.key || card.type || card.actionData?.id;

    switch (cardType) {
      case 'sales_today':
        return <SalesTodayCard count={salesCount} link="/pos" />;
      
      case 'expenses_today':
        return (
          <GreenMetricCard 
            icon={Layers}
            title="Expenses Today"
            value={formatCurrency(stats.total_expenses || 0)}
            subtitle="Total expenses recorded"
            percentageChange={Math.abs(expensePercentageChange)}
            trend={expensePercentageChange > 0 ? 'up' : 'down'}
            link="/money/movements?type=expense"
          />
        );
      
      case 'total_revenue':
        return (
          <GreenMetricCard 
            icon={PieChart}
            title="Total Revenue"
            value={formatCurrency(stats.total_revenue || 0)}
            subtitle="Revenue vs Last Period"
            percentageChange={stats.previous_revenue 
              ? Math.round(((stats.total_revenue - stats.previous_revenue) / stats.previous_revenue) * 100)
              : 0
            }
            trend={stats.total_revenue >= (stats.previous_revenue || 0) ? 'up' : 'down'}
            link="/sales"
          />
        );
      
      case 'total_orders':
        return (
          <GreenMetricCard 
            icon={FolderOpen}
            title="Total Orders"
            value={salesCount.toString()}
            subtitle="Orders vs Last Period"
            percentageChange={10.9}
            trend="up"
            link="/sales"
          />
        );
      
      case 'projects_running':
        return (
          <MetricCard 
            icon={FolderOpen}
            label="Projects Running"
            value={<><span className="text-gray-400 text-4xl mr-2">on</span>06</>}
            link="/projects"
            linkText="See all Projects"
          />
        );
      
      case 'budget_used':
        return (
          <MetricCard 
            icon={PieChart}
            label="Budget Used"
            value="46%"
            link="/money/budgets"
            linkText="See all Budgets"
          />
        );
      
      case 'revenue_chart':
        return (
          <ChartCard 
            title="Revenue"
            value={formatCurrency(stats.total_revenue || 0)}
            subtitle="Total income recorded"
            data={revenueData}
            dataKey="value"
            color="#7DCD85"
          />
        );
      
      case 'comparison_chart':
        return (
          <ChartCard 
            title="This Period Vs Previous"
            value=""
            data={comparisonData}
            dataKey="series1"
            color="#00635D"
          />
        );
      
      case 'expense_card':
        return (
          <ExpenseCard 
            amount={formatCurrency(stats.total_expenses || 0)}
            subtitle="Total expenses recorded"
            percentageChange={Math.abs(expensePercentageChange)}
            changeLabel={`Expenses ${expensePercentageChange > 0 ? 'up' : 'down'} ${Math.abs(expensePercentageChange)}% from previous period`}
            onAddExpense={() => router.visit('/money/movements/create?type=expense')}
          />
        );
      
      case 'top_products':
        return (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Package className="h-5 w-5" />
                Top Products
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {stats.top_products?.slice(0, 5).map((product, index) => (
                  <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <div className="font-medium text-gray-900">{product.name}</div>
                      <div className="text-sm text-gray-500">{product.quantity} sold</div>
                    </div>
                    <div className="text-lg font-semibold text-teal-500">
                      {formatCurrency(parseFloat(product.revenue))}
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        );
      
      case 'pending_invoices':
        return (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <FileText className="h-5 w-5" />
                Pending Invoices
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {stats.pending_invoices?.slice(0, 5).map((invoice) => (
                  <a 
                    key={invoice.id} 
                    href={`/invoices/${invoice.id}`} 
                    className="block p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition"
                  >
                    <div className="flex justify-between items-start">
                      <div>
                        <div className="font-medium text-gray-900">{invoice.invoice_number}</div>
                        <div className="text-sm text-gray-500">{invoice.customer?.name}</div>
                      </div>
                      <div className="text-right">
                        <div className="font-semibold text-gray-900">
                          {formatCurrency(parseFloat(invoice.total_amount) - parseFloat(invoice.paid_amount))}
                        </div>
                        <div className="text-xs text-yellow-600">Outstanding</div>
                      </div>
                    </div>
                  </a>
                ))}
              </div>
            </CardContent>
          </Card>
        );
      
      case 'low_stock':
        return (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <AlertTriangle className="h-5 w-5 text-red-500" />
                Low Stock Alerts
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {stats.low_stock_products?.slice(0, 5).map((product) => (
                  <a 
                    key={product.id} 
                    href={`/products/${product.id}`} 
                    className="block p-3 bg-red-50 rounded-lg hover:bg-red-100 transition"
                  >
                    <div className="flex justify-between items-start">
                      <div>
                        <div className="font-medium text-gray-900">{product.name}</div>
                        <div className="text-sm text-gray-500">
                          Min: {product.minimum_stock} {product.unit}
                        </div>
                      </div>
                      <div className="text-right">
                        <div className="font-semibold text-red-600">{product.current_stock} {product.unit}</div>
                        <div className="text-xs text-red-600">Low Stock</div>
                      </div>
                    </div>
                  </a>
                ))}
              </div>
            </CardContent>
          </Card>
        );
      
      case 'net_balance':
        return (
          <GreenMetricCard 
            icon={DollarSign}
            title="Net Balance"
            value={formatCurrency(stats.net_balance || 0)}
            subtitle="Revenue minus expenses"
            percentageChange={stats.previous_revenue && stats.previous_expenses
              ? Math.round((((stats.total_revenue - stats.total_expenses) - (stats.previous_revenue - stats.previous_expenses)) / Math.abs(stats.previous_revenue - stats.previous_expenses)) * 100)
              : 0
            }
            trend={stats.net_balance >= 0 ? 'up' : 'down'}
            link="/money"
          />
        );
      
      case 'cash_flow':
        const cashFlowData = stats.revenue_trend?.map((item, index) => ({
          name: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
          inflow: parseFloat(item.amount) || 0,
          outflow: stats.expense_trend?.[index]?.amount ? parseFloat(stats.expense_trend[index].amount) : 0,
        })) || [];
        return (
          <CashFlowCard 
            data={cashFlowData}
            totalInflow={formatCurrency(stats.total_revenue || 0)}
            totalOutflow={formatCurrency(stats.total_expenses || 0)}
            netFlow={formatCurrency(stats.net_balance || 0)}
          />
        );
      
      case 'revenue_by_category':
        const categoryData = stats.revenue_by_category || [
          { name: 'Products', value: stats.total_revenue * 0.6 || 0 },
          { name: 'Services', value: stats.total_revenue * 0.3 || 0 },
          { name: 'Other', value: stats.total_revenue * 0.1 || 0 },
        ];
        return (
          <RevenueByCategoryCard 
            data={categoryData.map(cat => ({
              name: cat.name,
              value: parseFloat(cat.value) || 0,
            }))}
          />
        );
      
      case 'expense_breakdown':
        const expenseData = stats.expense_breakdown || [
          { name: 'Operations', amount: stats.total_expenses * 0.4 || 0 },
          { name: 'Marketing', amount: stats.total_expenses * 0.3 || 0 },
          { name: 'Salaries', amount: stats.total_expenses * 0.2 || 0 },
          { name: 'Other', amount: stats.total_expenses * 0.1 || 0 },
        ];
        return (
          <ExpenseBreakdownCard 
            data={expenseData.map(exp => ({
              name: exp.name,
              amount: parseFloat(exp.amount) || 0,
            }))}
          />
        );
      
      case 'profit_margin':
        const profit = stats.total_revenue - stats.total_expenses;
        const margin = stats.total_revenue > 0 ? (profit / stats.total_revenue) * 100 : 0;
        const prevProfit = (stats.previous_revenue || 0) - (stats.previous_expenses || 0);
        const prevMargin = (stats.previous_revenue || 0) > 0 ? (prevProfit / (stats.previous_revenue || 1)) * 100 : 0;
        return (
          <ProfitMarginCard 
            revenue={formatCurrency(stats.total_revenue || 0)}
            expenses={formatCurrency(stats.total_expenses || 0)}
            profit={formatCurrency(profit)}
            margin={margin.toFixed(1)}
            previousMargin={prevMargin}
          />
        );
      
      case 'budget_status':
        return (
          <BudgetStatusCard 
            budgets={stats.budgets || []}
          />
        );
      
      case 'recent_activity':
        const activities = [
          ...(stats.recent_sales?.map(sale => ({
            type: 'sale',
            title: `Sale to ${sale.customer?.name || 'Customer'}`,
            amount: formatCurrency(parseFloat(sale.total_amount) || 0),
            time: new Date(sale.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }),
            url: `/sales/${sale.id}`,
          })) || []),
          ...(stats.pending_invoices?.map(inv => ({
            type: 'invoice',
            title: `Invoice ${inv.invoice_number}`,
            amount: formatCurrency(parseFloat(inv.total_amount) - parseFloat(inv.paid_amount)),
            time: new Date(inv.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }),
            url: `/invoices/${inv.id}`,
          })) || []),
        ].slice(0, 5);
        return (
          <RecentActivityCard 
            activities={activities}
          />
        );
      
      case 'customer_growth':
        const growthData = stats.customer_growth || Array.from({ length: 6 }, (_, i) => ({
          name: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'][i],
          value: Math.floor(Math.random() * 50) + 20,
        }));
        return (
          <CustomerGrowthCard 
            data={growthData}
            totalCustomers={stats.total_customers || 0}
            growthRate={stats.customer_growth_rate || 0}
          />
        );
      
      case 'team_performance':
        return (
          <TeamPerformanceCard 
            teamStats={stats.team_stats || {
              totalMembers: 0,
              goalsCompleted: 0,
              avgPerformance: 0,
              topPerformers: [],
            }}
          />
        );
      
      case 'project_status':
        return (
          <ProjectStatusCard 
            projects={stats.projects || []}
          />
        );
      
      case 'top_customers':
        return (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Users className="h-5 w-5" />
                Top Customers
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {stats.top_customers?.slice(0, 5).map((customer, index) => (
                  <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <div className="font-medium text-gray-900">{customer.name}</div>
                      <div className="text-sm text-gray-500">{customer.sales_count} orders</div>
                    </div>
                    <div className="text-lg font-semibold text-teal-500">
                      {formatCurrency(parseFloat(customer.revenue))}
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        );
      
      case 'quick_actions_block':
        // Single 8x8 quick actions block
        const assignedAction = assignedQuickActions[item.id];
        return (
          <QuickActionsBlock
            assignedAction={assignedAction ? availableQuickActions.find(a => a.id === assignedAction.id) : null}
            onAssign={() => handleAssignQuickAction(item.id)}
            onRemove={() => handleRemoveQuickAction(item.id)}
          />
        );
      
      case 'quick_actions':
        return <QuickActionsCard />;
      
      case 'quick_action':
        // Legacy individual quick action card (for backwards compatibility)
        const actionData = card.actionData;
        if (actionData) {
          return (
            <QuickActionCard
              title={actionData.title}
              icon={actionData.icon}
              url={actionData.url}
            />
          );
        }
        return <QuickActionsCard />;
      
      default:
        return (
          <Card>
            <CardHeader>
              <CardTitle>{card.dashboard_card?.name || 'Card'}</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-gray-500">
                {card.dashboard_card?.description || 'Dashboard card'}
              </p>
            </CardContent>
          </Card>
        );
    }
  };

  return (
    <AuthenticatedLayout>
      <Head title="Dashboard" />
      
      <div className="min-h-screen bg-gray-50">
        <main className="max-w-[1600px] mx-auto px-6 py-8">
          {/* Header with Timeframe Selector and Add Card Button */}
          <div className="flex justify-between items-center mb-6">
            <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
            <div className="flex items-center gap-3">
              <TimeframeSelector currentTimeframe={timeframe} />
              <button
                onClick={() => setShowAddCardModal(true)}
                className="p-2.5 bg-teal-500 hover:bg-teal-600 text-white rounded-full transition-colors shadow-lg"
              >
                <Plus className="h-5 w-5" />
              </button>
            </div>
          </div>

          {/* Bento Grid */}
          <BentoGrid
            items={dashboardItems}
            onReorder={handleReorder}
            onResize={handleResize}
            onRemove={handleRemoveCard}
            nonRemovableIds={[addyCardId]}
            showGrid={false}
          >
            {dashboardItems.map((item) => (
              <div key={item.id} className="h-full w-full">
                {renderCardContent(item)}
              </div>
            ))}
          </BentoGrid>

          {/* Add Card Modal */}
          <AddCardModal
            isOpen={showAddCardModal}
            onClose={() => setShowAddCardModal(false)}
            availableCards={availableCards}
            onAddCard={handleAddCard}
          />
          
          <QuickActionModal
            isOpen={showQuickActionModal}
            onClose={() => {
              setShowQuickActionModal(false);
              setSelectedQuickActionSlot(null);
            }}
            availableActions={availableQuickActions}
            onSelect={handleSelectQuickAction}
          />
        </main>
      </div>
    </AuthenticatedLayout>
  );
}
