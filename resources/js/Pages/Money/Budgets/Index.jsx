import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, Edit, Trash2, Target } from 'lucide-react';

export default function BudgetsIndex({ budgets }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    return (
        <SectionLayout sectionName="Money">
            <Head title="Budgets" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Budgets</h1>
                        <p className="text-gray-500 mt-1">Track your spending against budgets</p>
                    </div>
                    <Button onClick={() => router.visit('/money/budgets/create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        Create Budget
                    </Button>
                </div>

                {budgets.length === 0 ? (
                    <div className="bg-white border border-gray-200 rounded-lg p-12 text-center">
                        <Target className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No budgets yet</h3>
                        <p className="text-gray-500 mb-4">Create a budget to track your spending</p>
                        <Button onClick={() => router.visit('/money/budgets/create')}>
                            <Plus className="h-4 w-4 mr-2" />
                            Create Budget
                        </Button>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {budgets.map((budget) => {
                            const percentage = Math.min(budget.percentage_spent, 100);
                            const isOverBudget = budget.spent > budget.amount;
                            
                            return (
                                <div key={budget.id} className="bg-white border border-gray-200 rounded-lg p-6">
                                    <div className="flex items-start justify-between mb-4">
                                        <div className="flex-1">
                                            <h3 className="font-semibold text-gray-900 mb-1">{budget.name}</h3>
                                            {budget.category && (
                                                <p className="text-sm text-gray-500">{budget.category}</p>
                                            )}
                                        </div>
                                        <Link
                                            href={`/money/budgets/${budget.id}/edit`}
                                            className="text-gray-400 hover:text-gray-600"
                                        >
                                            <Edit className="h-4 w-4" />
                                        </Link>
                                    </div>
                                    
                                    <div className="mb-4">
                                        <div className="flex justify-between mb-2">
                                            <span className="text-sm text-gray-600">Budget</span>
                                            <span className="text-sm font-medium text-gray-900">
                                                {formatCurrency(budget.amount)}
                                            </span>
                                        </div>
                                        <div className="flex justify-between mb-2">
                                            <span className="text-sm text-gray-600">Spent</span>
                                            <span className={`text-sm font-medium ${
                                                isOverBudget ? 'text-red-600' : 'text-gray-900'
                                            }`}>
                                                {formatCurrency(budget.spent)}
                                            </span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-sm text-gray-600">Remaining</span>
                                            <span className={`text-sm font-medium ${
                                                budget.remaining < 0 ? 'text-red-600' : 'text-green-600'
                                            }`}>
                                                {formatCurrency(budget.remaining)}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div className="mb-4">
                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                            <div 
                                                className={`h-2 rounded-full transition-all duration-500 ${
                                                    isOverBudget ? 'bg-red-500' : 
                                                    percentage > 80 ? 'bg-yellow-500' : 'bg-green-500'
                                                }`}
                                                style={{ width: `${Math.min(percentage, 100)}%` }}
                                            />
                                        </div>
                                        <div className="flex justify-between mt-2 text-xs text-gray-500">
                                            <span>{percentage.toFixed(1)}% used</span>
                                            <span className="capitalize">{budget.period}</span>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}

