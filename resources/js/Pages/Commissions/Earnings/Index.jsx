import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { DollarSign, User, Calendar, CheckCircle } from 'lucide-react';

export default function CommissionEarningsIndex({ earnings, totalPending, totalPaid, teamMembers, filters }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const getStatusBadge = (status) => {
        const badges = {
            pending: 'bg-yellow-100 text-yellow-700',
            paid: 'bg-green-100 text-green-700',
        };
        return badges[status] || 'bg-gray-100 text-gray-700';
    };

    return (
        <SectionLayout sectionName="People">
            <Head title="Commission Earnings" />
            <div>
                <div className="mb-6">
                    <h1 className="text-3xl font-bold text-gray-900">Commission Earnings</h1>
                    <p className="text-gray-500 mt-1">Track commission earnings for your team</p>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-2 gap-6 mb-6">
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="text-sm text-gray-500 mb-1">Total Pending</div>
                        <div className="text-3xl font-bold text-yellow-600">{formatCurrency(totalPending)}</div>
                    </div>
                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                        <div className="text-sm text-gray-500 mb-1">Total Paid</div>
                        <div className="text-3xl font-bold text-green-600">{formatCurrency(totalPaid)}</div>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Team Member</label>
                            <select
                                value={filters?.team_member_id || ''}
                                onChange={(e) => router.visit(`/commissions/earnings?team_member_id=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Members</option>
                                {teamMembers.map((member) => (
                                    <option key={member.id} value={member.id}>
                                        {member.first_name} {member.last_name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                value={filters?.status || ''}
                                onChange={(e) => router.visit(`/commissions/earnings?status=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            >
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                            <input
                                type="date"
                                value={filters?.date_from || ''}
                                onChange={(e) => router.visit(`/commissions/earnings?date_from=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                            <input
                                type="date"
                                value={filters?.date_to || ''}
                                onChange={(e) => router.visit(`/commissions/earnings?date_to=${e.target.value}`)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                    </div>
                </div>

                {/* Earnings Table */}
                <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Team Member</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Sale</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Commission Rule</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Sale Amount</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Commission</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {earnings.data.length === 0 ? (
                                <tr>
                                    <td colSpan="7" className="px-6 py-12 text-center text-gray-500">
                                        No commission earnings found.
                                    </td>
                                </tr>
                            ) : (
                                earnings.data.map((earning) => (
                                    <tr key={earning.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-gray-900">
                                                {earning.team_member?.first_name} {earning.team_member?.last_name}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900">
                                            {earning.sale?.sale_number || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {earning.commission_rule?.name || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-right text-gray-900">
                                            {formatCurrency(earning.sale_amount)}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-right font-medium text-gray-900">
                                            {formatCurrency(earning.amount)}
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusBadge(earning.status)}`}>
                                                {earning.status.charAt(0).toUpperCase() + earning.status.slice(1)}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {new Date(earning.created_at).toLocaleDateString()}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {earnings.links && earnings.links.length > 3 && (
                    <div className="mt-4 flex items-center justify-between">
                        <div className="text-sm text-gray-500">
                            Showing {earnings.from} to {earnings.to} of {earnings.total} results
                        </div>
                        <div className="flex gap-2">
                            {earnings.links.map((link, index) => (
                                <button
                                    key={index}
                                    onClick={() => link.url && router.visit(link.url)}
                                    disabled={!link.url}
                                    className={`px-3 py-2 text-sm border rounded-lg ${
                                        link.active
                                            ? 'bg-teal-500 text-white border-teal-500'
                                            : link.url
                                            ? 'border-gray-300 hover:bg-gray-50'
                                            : 'border-gray-200 text-gray-400 cursor-not-allowed'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}

