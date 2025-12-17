import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Plus, ArrowUp, ArrowDown, ArrowRightLeft, Eye } from 'lucide-react';

export default function MoneyMovementsIndex({ movements, filters }) {
    const formatCurrency = (amount, currency = 'ZMW') => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const getFlowIcon = (type) => {
        switch (type) {
            case 'income':
                return <ArrowUp className="h-4 w-4 text-green-500" />;
            case 'expense':
                return <ArrowDown className="h-4 w-4 text-red-500" />;
            case 'transfer':
                return <ArrowRightLeft className="h-4 w-4 text-blue-500" />;
            default:
                return null;
        }
    };

    const getFlowColor = (type) => {
        switch (type) {
            case 'income':
                return 'text-green-600 bg-green-50';
            case 'expense':
                return 'text-red-600 bg-red-50';
            case 'transfer':
                return 'text-blue-600 bg-blue-50';
            default:
                return 'text-gray-600 bg-gray-50';
        }
    };

    return (
        <SectionLayout sectionName="Money">
            <Head title="Money Movements" />
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Money Movements</h1>
                        <p className="text-gray-500 mt-1">Track all your income, expenses, and transfers</p>
                    </div>
                    <div className="flex gap-2">
                        <Button 
                            variant="secondary"
                            onClick={() => router.visit('/money/movements/create?type=income')}
                        >
                            Record Income
                        </Button>
                        <Button 
                            variant="secondary"
                            onClick={() => router.visit('/money/movements/create?type=expense')}
                        >
                            Record Expense
                        </Button>
                        <Button onClick={() => router.visit('/money/movements/create')}>
                            <Plus className="h-4 w-4 mr-2" />
                            New Movement
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="flex gap-4">
                        <select
                            value={filters?.type || ''}
                            onChange={(e) => router.visit(`/money/movements?type=${e.target.value}`)}
                            className="px-4 py-2 border border-gray-300 rounded-lg"
                        >
                            <option value="">All Types</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                            <option value="transfer">Transfer</option>
                        </select>
                        <input
                            type="date"
                            value={filters?.from_date || ''}
                            onChange={(e) => router.visit(`/money/movements?from_date=${e.target.value}`)}
                            className="px-4 py-2 border border-gray-300 rounded-lg"
                            placeholder="From Date"
                        />
                        <input
                            type="date"
                            value={filters?.to_date || ''}
                            onChange={(e) => router.visit(`/money/movements?to_date=${e.target.value}`)}
                            className="px-4 py-2 border border-gray-300 rounded-lg"
                            placeholder="To Date"
                        />
                    </div>
                </div>

                {/* Movements Table */}
                {movements.data.length === 0 ? (
                    <div className="bg-white border border-gray-200 rounded-lg p-12 text-center">
                        <ArrowRightLeft className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No movements yet</h3>
                        <p className="text-gray-500 mb-4">Start tracking your money by recording your first transaction</p>
                        <Button onClick={() => router.visit('/money/movements/create')}>
                            <Plus className="h-4 w-4 mr-2" />
                            Record Movement
                        </Button>
                    </div>
                ) : (
                    <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Account
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Amount
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {movements.data.map((movement) => (
                                    <tr key={movement.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {new Date(movement.transaction_date).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium ${getFlowColor(movement.flow_type)}`}>
                                                {getFlowIcon(movement.flow_type)}
                                                {movement.flow_type.charAt(0).toUpperCase() + movement.flow_type.slice(1)}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900">
                                            <div>
                                                <div className="font-medium">{movement.description}</div>
                                                {movement.category && (
                                                    <div className="text-xs text-gray-500">{movement.category}</div>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {movement.flow_type === 'income' && movement.to_account && (
                                                <span>{movement.to_account.name}</span>
                                            )}
                                            {movement.flow_type === 'expense' && movement.from_account && (
                                                <span>{movement.from_account.name}</span>
                                            )}
                                            {movement.flow_type === 'transfer' && (
                                                <span>
                                                    {movement.from_account?.name} → {movement.to_account?.name}
                                                </span>
                                            )}
                                        </td>
                                        <td className={`px-6 py-4 whitespace-nowrap text-sm text-right font-medium ${
                                            movement.flow_type === 'income' ? 'text-green-600' : 
                                            movement.flow_type === 'expense' ? 'text-red-600' : 
                                            'text-gray-900'
                                        }`}>
                                            {movement.flow_type === 'expense' ? '-' : '+'}
                                            {formatCurrency(movement.amount, movement.currency)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                            <Link
                                                href={`/money/movements/${movement.id}`}
                                                className="text-teal-500 hover:text-teal-600"
                                            >
                                                <Eye className="h-4 w-4" />
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        
                        {/* Pagination */}
                        {movements.links && movements.links.length > 3 && (
                            <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                                <div className="text-sm text-gray-500">
                                    Showing {movements.from} to {movements.to} of {movements.total} results
                                </div>
                                <div className="flex gap-2">
                                    {movements.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-1 rounded-lg text-sm ${
                                                link.active
                                                    ? 'bg-teal-500 text-white'
                                                    : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
                                            } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}

