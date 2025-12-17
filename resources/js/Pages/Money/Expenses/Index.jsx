import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import UploadModal from '@/Components/UploadModal';
import { Plus, Eye, Edit, Trash2, Receipt, Upload, Search, Calendar, Filter, Paperclip } from 'lucide-react';
import { useState } from 'react';

export default function ExpensesIndex({ expenses, stats, filters }) {
    const formatCurrency = (amount) => {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num);
    };

    const formatFullAmount = (amount) => {
        const num = parseFloat(amount) || 0;
        return `K ${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    };

    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [showUploadModal, setShowUploadModal] = useState(false);

    const handleSearch = (e) => {
        e.preventDefault();
        router.visit(`/money/expenses?search=${searchTerm}`);
    };

    const handleDelete = (expenseId) => {
        if (confirm('Are you sure you want to delete this expense record?')) {
            router.delete(`/money/expenses/${expenseId}`);
        }
    };

    return (
        <SectionLayout sectionName="Money">
            <Head title="Expenses" />
            <div>
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-3xl font-black text-gray-900 tracking-tight">Expenses</h1>
                        <p className="text-gray-500 mt-1">Track all your expense transactions</p>
                    </div>
                    <div className="flex gap-3">
                        <Button 
                            variant="secondary"
                            onClick={() => setShowUploadModal(true)}
                            className="gap-2"
                        >
                            <Upload className="h-4 w-4" />
                            Upload
                        </Button>
                        <Button onClick={() => router.visit('/money/expenses/create')} className="gap-2">
                            <Plus className="h-4 w-4" />
                            New Expense
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div className="bg-gradient-to-br from-red-50 to-orange-50 rounded-2xl p-6 border border-red-100/50 relative overflow-hidden">
                        <div className="absolute bottom-0 right-0 opacity-[0.08] pointer-events-none" style={{ marginBottom: '-20%', marginRight: '-10%' }}>
                            <Receipt className="text-red-500" strokeWidth={1.5} style={{ width: '120px', height: '120px' }} />
                        </div>
                        <div className="relative z-10">
                            <p className="text-sm font-semibold text-gray-600 mb-2">Total Expenses</p>
                            <p className="text-3xl font-black text-red-600">{formatCurrency(stats?.total_expenses)}</p>
                        </div>
                    </div>
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 relative overflow-hidden hover:shadow-lg hover:border-teal-200 transition-all">
                        <div className="absolute bottom-0 right-0 opacity-[0.08] pointer-events-none" style={{ marginBottom: '-20%', marginRight: '-10%' }}>
                            <Calendar className="text-teal-500" strokeWidth={1.5} style={{ width: '120px', height: '120px' }} />
                        </div>
                        <div className="relative z-10">
                            <p className="text-sm font-semibold text-gray-600 mb-2">This Month</p>
                            <p className="text-3xl font-black text-gray-900">{formatCurrency(stats?.this_month_expenses)}</p>
                        </div>
                    </div>
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 relative overflow-hidden hover:shadow-lg hover:border-teal-200 transition-all">
                        <div className="relative z-10">
                            <p className="text-sm font-semibold text-gray-600 mb-2">Total Records</p>
                            <p className="text-3xl font-black text-gray-900">{stats?.total_count || 0}</p>
                        </div>
                    </div>
                </div>

                {/* Filters Card */}
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 mb-6">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
                            <Filter className="w-5 h-5 text-teal-600" />
                        </div>
                        <h3 className="text-sm font-bold text-gray-900">Filters</h3>
                    </div>
                    <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div className="md:col-span-2">
                            <label className="block text-xs font-semibold text-gray-600 mb-2">Search</label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <input
                                    type="text"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder="Search by description or category..."
                                    className="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">From Date</label>
                            <input
                                type="date"
                                value={filters?.from_date || ''}
                                onChange={(e) => router.visit(`/money/expenses?from_date=${e.target.value}`)}
                                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 mb-2">To Date</label>
                            <input
                                type="date"
                                value={filters?.to_date || ''}
                                onChange={(e) => router.visit(`/money/expenses?to_date=${e.target.value}`)}
                                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            />
                        </div>
                    </form>
                </div>

                {/* Expenses Table */}
                {expenses.data && expenses.data.length > 0 ? (
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/50 overflow-hidden">
                        <table className="w-full">
                            <thead className="bg-gray-50/80 border-b border-gray-200/50">
                                <tr>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Date</th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Description</th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Category</th>
                                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Account</th>
                                    <th className="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Amount</th>
                                    <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Files</th>
                                    <th className="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {expenses.data.map((expense) => (
                                    <tr key={expense.id} className="hover:bg-teal-50/30 transition-colors">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {new Date(expense.transaction_date).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4">
                                            <p className="text-sm font-semibold text-gray-900">{expense.description}</p>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            {expense.category ? (
                                                <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                                    {expense.category}
                                                </span>
                                            ) : (
                                                <span className="text-gray-400">-</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {expense.from_account?.name || '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-red-600">
                                            {formatFullAmount(expense.amount)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                            {expense.attachments && expense.attachments.length > 0 ? (
                                                <span className="inline-flex items-center gap-1 px-2.5 py-1 bg-teal-100 text-teal-700 rounded-full text-xs font-semibold">
                                                    <Paperclip className="h-3 w-3" />
                                                    {expense.attachments.length}
                                                </span>
                                            ) : (
                                                <span className="text-gray-400">-</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                            <div className="flex items-center justify-center gap-1">
                                                <Link
                                                    href={`/money/expenses/${expense.id}`}
                                                    className="p-2 rounded-lg text-teal-600 hover:bg-teal-100 transition-colors"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                                <Link
                                                    href={`/money/expenses/${expense.id}/edit`}
                                                    className="p-2 rounded-lg text-blue-600 hover:bg-blue-100 transition-colors"
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(expense.id)}
                                                    className="p-2 rounded-lg text-red-500 hover:bg-red-100 transition-colors"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        {expenses.links && expenses.links.length > 3 && (
                            <div className="px-6 py-4 border-t border-gray-200/50 flex items-center justify-between bg-gray-50/50">
                                <p className="text-sm text-gray-600">
                                    Showing <span className="font-semibold">{expenses.from}</span> to <span className="font-semibold">{expenses.to}</span> of <span className="font-semibold">{expenses.total}</span>
                                </p>
                                <div className="flex gap-1">
                                    {expenses.links.map((link, index) => (
                                        <button
                                            key={index}
                                            onClick={() => link.url && router.visit(link.url)}
                                            disabled={!link.url}
                                            className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all ${
                                                link.active
                                                    ? 'bg-teal-500 text-white shadow-sm'
                                                    : link.url
                                                    ? 'bg-white border border-gray-200 text-gray-700 hover:bg-teal-50 hover:border-teal-200'
                                                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                ) : (
                    <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-12 border border-gray-200/50 text-center">
                        <div className="w-16 h-16 rounded-2xl bg-red-100 flex items-center justify-center mx-auto mb-4">
                            <Receipt className="h-8 w-8 text-red-500" />
                        </div>
                        <h3 className="text-lg font-bold text-gray-900 mb-2">No expenses found</h3>
                        <p className="text-gray-500 mb-6">Get started by recording your first expense</p>
                        <Button onClick={() => router.visit('/money/expenses/create')} className="gap-2">
                            <Plus className="h-4 w-4" />
                            Add Expense
                        </Button>
                    </div>
                )}

                {/* Upload Modal */}
                <UploadModal
                    isOpen={showUploadModal}
                    onClose={() => setShowUploadModal(false)}
                    onSuccess={(results) => {
                        console.log('Upload successful:', results);
                        router.reload();
                    }}
                    context="expenses"
                />
            </div>
        </SectionLayout>
    );
}
