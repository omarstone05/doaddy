import { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Lock, Unlock, DollarSign } from 'lucide-react';

export default function RegisterIndex({ openSession, closedSessions, accounts }) {
    const { data, setData, post, processing } = useForm({
        money_account_id: '',
        opening_float: '',
    });

    const closeForm = useForm({
        closing_count: '',
        notes: '',
    });

    const handleOpen = (e) => {
        e.preventDefault();
        post('/register/open', {
            onSuccess: () => {
                router.reload();
            },
        });
    };

    const handleClose = (e) => {
        e.preventDefault();
        closeForm.post(`/register/${openSession.id}/close`, {
            onSuccess: () => {
                router.reload();
            },
        });
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    return (
        <SectionLayout sectionName="Sales">
            <Head title="Register Sessions" />
            <div className="max-w-6xl mx-auto ">
                <div className="mb-6">
                    <h1 className="text-3xl font-bold text-gray-900">Register Sessions</h1>
                    <p className="text-gray-500 mt-1">Open and close register sessions</p>
                </div>

                {openSession ? (
                    <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                        <div className="flex items-center justify-between mb-6">
                            <div>
                                <h2 className="text-2xl font-bold text-gray-900">Current Session</h2>
                                <p className="text-gray-500 mt-1">Session {openSession.session_number}</p>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                                    Open
                                </span>
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-6 mb-6">
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 mb-2">Opened By</h3>
                                <p className="text-gray-900">{openSession.opened_by?.first_name} {openSession.opened_by?.last_name}</p>
                                <p className="text-sm text-gray-600">
                                    {new Date(openSession.opening_date).toLocaleString()}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 mb-2">Opening Float</h3>
                                <p className="text-2xl font-bold text-gray-900">
                                    {formatCurrency(openSession.opening_float)}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 mb-2">Account</h3>
                                <p className="text-gray-900">{openSession.money_account?.name}</p>
                            </div>
                        </div>

                        {/* Close Session Form */}
                        <form onSubmit={handleClose} className="border-t border-gray-200 pt-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Close Session</h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Closing Count *
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={closeForm.data.closing_count}
                                        onChange={(e) => closeForm.setData('closing_count', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Notes
                                    </label>
                                    <input
                                        type="text"
                                        value={closeForm.data.notes}
                                        onChange={(e) => closeForm.setData('notes', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>
                            </div>
                            <Button
                                type="submit"
                                disabled={closeForm.processing}
                                className="mt-4"
                            >
                                <Lock className="h-4 w-4 mr-2" />
                                Close Session
                            </Button>
                        </form>
                    </div>
                ) : (
                    <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                        <h2 className="text-2xl font-bold text-gray-900 mb-4">Open New Session</h2>
                        <form onSubmit={handleOpen}>
                            <div className="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Cash Account *
                                    </label>
                                    <select
                                        value={data.money_account_id}
                                        onChange={(e) => setData('money_account_id', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                        required
                                    >
                                        <option value="">Select account</option>
                                        {accounts.map((account) => (
                                            <option key={account.id} value={account.id}>
                                                {account.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Opening Float *
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.opening_float}
                                        onChange={(e) => setData('opening_float', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                        placeholder="0.00"
                                        required
                                    />
                                </div>
                            </div>
                            <Button type="submit" disabled={processing}>
                                <Unlock className="h-4 w-4 mr-2" />
                                Open Session
                            </Button>
                        </form>
                    </div>
                )}

                {/* Closed Sessions */}
                {closedSessions && closedSessions.length > 0 && (
                    <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h2 className="text-xl font-semibold text-gray-900">Recent Sessions</h2>
                        </div>
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Session</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Opened</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Closed</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Total Sales</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Variance</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {closedSessions.map((session) => (
                                    <tr key={session.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="font-medium text-gray-900">{session.session_number}</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {new Date(session.opening_date).toLocaleString()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {session.closing_date ? new Date(session.closing_date).toLocaleString() : '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                            {formatCurrency(session.total_sales || 0)}
                                        </td>
                                        <td className={`px-6 py-4 whitespace-nowrap text-sm text-right font-medium ${
                                            (session.variance || 0) === 0 ? 'text-gray-600' : 
                                            (session.variance || 0) > 0 ? 'text-green-600' : 'text-red-600'
                                        }`}>
                                            {formatCurrency(session.variance || 0)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}

