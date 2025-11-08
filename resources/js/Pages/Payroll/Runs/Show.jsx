import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, DollarSign, Users, Calendar, CheckCircle } from 'lucide-react';

export default function PayrollRunsShow({ payrollRun }) {
    const getStatusBadge = (status) => {
        const badges = {
            draft: 'bg-gray-100 text-gray-700',
            processing: 'bg-blue-100 text-blue-700',
            completed: 'bg-green-100 text-green-700',
            cancelled: 'bg-red-100 text-red-700',
        };
        return badges[status] || 'bg-gray-100 text-gray-700';
    };

    const handleProcess = () => {
        if (confirm('Are you sure you want to process this payroll run? This will calculate all payroll items.')) {
            router.post(`/payroll/runs/${payrollRun.id}/process`);
        }
    };

    return (
        <SectionLayout sectionName="People">
            <Head title="Payroll Run Details" />
            <div className="max-w-7xl mx-auto">
                <Link href="/payroll/runs">
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Payroll Runs
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="flex items-center justify-between mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Payroll Run Details</h1>
                        <span className={`px-3 py-1 text-sm font-medium rounded-full ${getStatusBadge(payrollRun.status)}`}>
                            {payrollRun.status.charAt(0).toUpperCase() + payrollRun.status.slice(1)}
                        </span>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <Calendar className="h-4 w-4" />
                                <span className="text-sm font-medium">Pay Period</span>
                            </div>
                            <p className="text-gray-900 font-medium">{payrollRun.pay_period}</p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <Calendar className="h-4 w-4" />
                                <span className="text-sm font-medium">Date Range</span>
                            </div>
                            <p className="text-gray-900 font-medium">
                                {new Date(payrollRun.start_date).toLocaleDateString()} - {new Date(payrollRun.end_date).toLocaleDateString()}
                            </p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <DollarSign className="h-4 w-4" />
                                <span className="text-sm font-medium">Total Amount</span>
                            </div>
                            <p className="text-gray-900 font-medium text-lg">
                                {new Intl.NumberFormat('en-ZM', {
                                    style: 'currency',
                                    currency: 'ZMW',
                                }).format(payrollRun.total_amount)}
                            </p>
                        </div>
                    </div>

                    {payrollRun.notes && (
                        <div className="mb-6">
                            <div className="text-sm font-medium text-gray-700 mb-2">Notes</div>
                            <p className="text-gray-900">{payrollRun.notes}</p>
                        </div>
                    )}

                    {payrollRun.status === 'draft' && (
                        <div className="mb-6">
                            <Button onClick={handleProcess} className="bg-teal-500 hover:bg-teal-600 text-white">
                                <CheckCircle className="h-4 w-4 mr-2" />
                                Process Payroll
                            </Button>
                        </div>
                    )}

                    {/* Payroll Items */}
                    <div className="mt-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Payroll Items</h2>
                        <div className="border border-gray-200 rounded-lg overflow-hidden">
                            <table className="w-full">
                                <thead className="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Team Member</th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Basic Salary</th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Gross Pay</th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Deductions</th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Net Pay</th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {payrollRun.items?.length === 0 ? (
                                        <tr>
                                            <td colSpan="6" className="px-6 py-8 text-center text-gray-500">
                                                No payroll items found.
                                            </td>
                                        </tr>
                                    ) : (
                                        payrollRun.items?.map((item) => (
                                            <tr key={item.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4">
                                                    <div className="font-medium text-gray-900">
                                                        {item.team_member?.first_name} {item.team_member?.last_name}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-right text-sm text-gray-900">
                                                    {new Intl.NumberFormat('en-ZM', {
                                                        style: 'currency',
                                                        currency: 'ZMW',
                                                    }).format(item.basic_salary)}
                                                </td>
                                                <td className="px-6 py-4 text-right text-sm text-gray-900">
                                                    {new Intl.NumberFormat('en-ZM', {
                                                        style: 'currency',
                                                        currency: 'ZMW',
                                                    }).format(item.gross_pay)}
                                                </td>
                                                <td className="px-6 py-4 text-right text-sm text-gray-900">
                                                    {new Intl.NumberFormat('en-ZM', {
                                                        style: 'currency',
                                                        currency: 'ZMW',
                                                    }).format(item.total_deductions)}
                                                </td>
                                                <td className="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                                    {new Intl.NumberFormat('en-ZM', {
                                                        style: 'currency',
                                                        currency: 'ZMW',
                                                    }).format(item.net_pay)}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <Link href={`/payroll/items/${item.id}`}>
                                                        <Button variant="ghost" size="sm">View</Button>
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </SectionLayout>
    );
}

