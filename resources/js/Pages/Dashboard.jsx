import { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { InsightsCard } from '@/Components/dashboard/InsightsCard';
import { MetricCard } from '@/Components/dashboard/MetricCard';
import { SalesTodayCard } from '@/Components/dashboard/SalesTodayCard';
import { ChartCard } from '@/Components/dashboard/ChartCard';
import { ExpenseCard } from '@/Components/dashboard/ExpenseCard';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/Card';
import { Layers, FolderOpen, PieChart, Package, FileText, Users, AlertTriangle, Plus, X } from 'lucide-react';
import { DndContext, closestCenter, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

function SortableCard({ card, isActive, onRemove }) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
    } = useSortable({ id: card.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div ref={setNodeRef} style={style} {...attributes} {...listeners} className="cursor-move">
            <Card className={isActive ? 'ring-2 ring-teal-500' : ''}>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle className="text-lg">{card.dashboard_card.name}</CardTitle>
                        <button
                            onClick={(e) => {
                                e.stopPropagation();
                                onRemove(card.id);
                            }}
                            className="text-gray-400 hover:text-red-500"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="text-sm text-gray-500">
                        {card.dashboard_card.description || 'Dashboard card'}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

export default function Dashboard({ user, availableCards, orgCards, stats }) {
    const [cards, setCards] = useState(orgCards || []);
    const [showAddCard, setShowAddCard] = useState(false);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const handleDragEnd = (event) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setCards((items) => {
                const oldIndex = items.findIndex(item => item.id === active.id);
                const newIndex = items.findIndex(item => item.id === over.id);
                const newItems = arrayMove(items, oldIndex, newIndex);
                
                // Update order on server
                const orders = newItems.map((item, index) => ({
                    id: item.id,
                    order: index,
                }));
                
                router.post('/dashboard/cards/reorder', { orders }, {
                    preserveScroll: true,
                });
                
                return newItems;
            });
        }
    };

    const handleRemoveCard = (cardId) => {
        router.delete(`/dashboard/cards/${cardId}`, {
            preserveScroll: true,
            onSuccess: () => {
                setCards(cards.filter(c => c.id !== cardId));
            },
        });
    };

    const handleAddCard = (cardId) => {
        router.post('/dashboard/cards/add', { dashboard_card_id: cardId }, {
            preserveScroll: true,
            onSuccess: () => {
                setShowAddCard(false);
                window.location.reload();
            },
        });
    };

    // Prepare chart data
    const revenueData = stats.revenue_trend?.map(item => ({
        name: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
        value: parseFloat(item.amount) || 0,
    })) || [];

    const comparisonData = [
        { name: 'This Month', series1: stats.this_month_revenue || 0, series2: stats.this_month_expenses || 0 },
        { name: 'Last Month', series1: stats.last_month_revenue || 0, series2: stats.last_month_expenses || 0 },
    ];

    // Calculate sales count (you may need to adjust this based on your data)
    const salesCount = stats.recent_sales?.length || 0;

    // Calculate percentage change for expenses
    const expensePercentageChange = stats.last_month_expenses 
        ? Math.round(((stats.this_month_expenses - stats.last_month_expenses) / stats.last_month_expenses) * 100)
        : 0;

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />
            
            <div className="min-h-screen bg-gray-50">
                <main className="max-w-[1600px] mx-auto px-6 py-8">
                    {/* Date Selector */}
                    <div className="flex justify-end mb-6">
                        <div className="flex items-center gap-3">
                            <button className="bg-mint-100 text-teal-700 font-medium px-6 py-2.5 rounded-full flex items-center gap-2 hover:bg-mint-200 transition-colors">
                                TODAY
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <button className="p-2.5 bg-white border border-gray-200 rounded-full hover:bg-gray-50 transition-colors">
                                <svg className="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {/* Hero Row - Insights + Sales Today */}
                    <div className="grid grid-cols-12 gap-6 mb-6">
                        <div className="col-span-12 lg:col-span-8">
                            <InsightsCard 
                                userName={user?.name || 'User'}
                                message={stats.net_balance >= 0 
                                    ? `You're looking good this month so far, there's a few things that we need to do though...`
                                    : `You have ${formatCurrency(Math.abs(stats.net_balance))} in expenses. Consider reviewing your budget.`
                                }
                            />
                        </div>
                        <div className="col-span-12 lg:col-span-4">
                            <SalesTodayCard 
                                count={salesCount}
                                link="/pos"
                            />
                        </div>
                    </div>

                    {/* Metrics Row - 3 Metric Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <MetricCard 
                            icon={Layers}
                            label="Expenses Today"
                            value={formatCurrency(stats.total_expenses || 0)}
                            link="/money/movements?type=expense"
                            linkText="See all Expenses"
                        />
                        <MetricCard 
                            icon={FolderOpen}
                            label="Projects Running"
                            value={<><span className="text-gray-400 text-4xl mr-2">on</span>06</>}
                            link="/projects"
                            linkText="See all Projects"
                        />
                        <MetricCard 
                            icon={PieChart}
                            label="Budget Used"
                            value="46%"
                            link="/money/budgets"
                            linkText="See all Budgets"
                        />
                    </div>

                    {/* Charts Row - Revenue + Comparison */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        {revenueData.length > 0 && (
                            <ChartCard 
                                title="Revenue"
                                value={formatCurrency(stats.total_revenue || 0)}
                                subtitle="Total income recorded"
                                data={revenueData}
                                dataKey="value"
                                color="#7DCD85"
                            />
                        )}
                        <ChartCard 
                            title="This Month Vs Last Month"
                            value=""
                            data={comparisonData}
                            dataKey="series1"
                            color="#00635D"
                        />
                    </div>

                    {/* Expense Cards Row - 2 Expense Cards */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <ExpenseCard 
                            amount={formatCurrency(stats.total_expenses || 0)}
                            subtitle="Total expenses recorded"
                            percentageChange={Math.abs(expensePercentageChange)}
                            changeLabel={`Expenses ${expensePercentageChange > 0 ? 'up' : 'down'} ${Math.abs(expensePercentageChange)}% from last month`}
                            onAddExpense={() => router.visit('/money/movements/create?type=expense')}
                        />
                        <ExpenseCard 
                            amount={formatCurrency(stats.total_expenses || 0)}
                            subtitle="Total expenses recorded"
                            percentageChange={Math.abs(expensePercentageChange)}
                            changeLabel={`Expenses ${expensePercentageChange > 0 ? 'up' : 'down'} ${Math.abs(expensePercentageChange)}% from last month`}
                            onAddExpense={() => router.visit('/money/movements/create?type=expense')}
                        />
                    </div>

                    {/* Additional Sections */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        {/* Top Products */}
                        {stats.top_products && stats.top_products.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Package className="h-5 w-5" />
                                        Top Products
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {stats.top_products.slice(0, 5).map((product, index) => (
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
                        )}

                        {/* Pending Invoices */}
                        {stats.pending_invoices && stats.pending_invoices.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <FileText className="h-5 w-5" />
                                        Pending Invoices
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {stats.pending_invoices.slice(0, 5).map((invoice) => (
                                            <Link key={invoice.id} href={`/invoices/${invoice.id}`} className="block p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
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
                                            </Link>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Low Stock Alerts */}
                        {stats.low_stock_products && stats.low_stock_products.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <AlertTriangle className="h-5 w-5 text-red-500" />
                                        Low Stock Alerts
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {stats.low_stock_products.slice(0, 5).map((product) => (
                                            <Link key={product.id} href={`/products/${product.id}`} className="block p-3 bg-red-50 rounded-lg hover:bg-red-100 transition">
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
                                            </Link>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Customizable Dashboard Cards */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-xl font-semibold text-gray-900">Dashboard Cards</h2>
                            <button
                                onClick={() => setShowAddCard(!showAddCard)}
                                className="bg-teal-500 hover:bg-teal-600 text-white font-medium px-4 py-2 rounded-lg transition-colors flex items-center gap-2"
                            >
                                <Plus className="h-4 w-4" />
                                Add Card
                            </button>
                        </div>

                        {showAddCard && (
                            <div className="bg-white border border-gray-200 rounded-lg p-4 mb-4">
                                <h3 className="text-sm font-medium text-gray-900 mb-3">Available Cards</h3>
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                                    {availableCards
                                        ?.filter(card => !cards.some(c => c.dashboard_card_id === card.id))
                                        .map(card => (
                                            <button
                                                key={card.id}
                                                onClick={() => handleAddCard(card.id)}
                                                className="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 text-left"
                                            >
                                                {card.name}
                                            </button>
                                        ))}
                                </div>
                            </div>
                        )}

                        {cards.length === 0 ? (
                            <div className="bg-white border border-gray-200 rounded-lg p-12 text-center">
                                <p className="text-gray-500">No dashboard cards configured. Add cards to customize your dashboard.</p>
                            </div>
                        ) : (
                            <DndContext
                                sensors={sensors}
                                collisionDetection={closestCenter}
                                onDragEnd={handleDragEnd}
                            >
                                <SortableContext
                                    items={cards.map(c => c.id)}
                                    strategy={verticalListSortingStrategy}
                                >
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        {cards.map((card) => (
                                            <SortableCard
                                                key={card.id}
                                                card={card}
                                                isActive={false}
                                                onRemove={handleRemoveCard}
                                            />
                                        ))}
                                    </div>
                                </SortableContext>
                            </DndContext>
                        )}
                    </div>
                </main>
            </div>
        </AuthenticatedLayout>
    );
}
