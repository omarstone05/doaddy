import { Head, Link } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { ArrowLeft, DollarSign, User } from 'lucide-react';

export default function PayrollItemsShow({ payrollItem }) {
    return (
        <SectionLayout sectionName="People">
            <Head title="Payroll Item Details" />
            <div className="max-w-4xl mx-auto ">
                <Link href={`/payroll/runs/${payrollItem.payroll_run_id}`}>
                    <button className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                        <ArrowLeft className="h-4 w-4" />
                        Back to Payroll Run
                    </button>
                </Link>

                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    <h1 className="text-2xl font-bold text-gray-900 mb-6">Payroll Item Details</h1>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <User className="h-4 w-4" />
                                <span className="text-sm font-medium">Team Member</span>
                            </div>
                            <p className="text-gray-900 font-medium">
                                {payrollItem.team_member?.first_name} {payrollItem.team_member?.last_name}
                            </p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2 text-gray-600 mb-2">
                                <span className="text-sm font-medium">Pay Period</span>
                            </div>
                            <p className="text-gray-900 font-medium">{payrollItem.payroll_run?.pay_period}</p>
                        </div>
                    </div>

                    {/* Earnings */}
                    <div className="border-t border-gray-200 pt-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Earnings</h2>
                        <div className="space-y-3">
                            <div className="flex justify-between items-center">
                                <span className="text-gray-700">Basic Salary</span>
                                <span className="font-medium text-gray-900">
                                    {new Intl.NumberFormat('en-ZM', {
                                        style: 'currency',
                                        currency: 'ZMW',
                                    }).format(payrollItem.basic_salary)}
                                </span>
                            </div>
                            {payrollItem.allowances && payrollItem.allowances.length > 0 && (
                                <>
                                    {payrollItem.allowances.map((allowance, index) => (
                                        <div key={index} className="flex justify-between items-center">
                                            <span className="text-gray-700">{allowance.name}</span>
                                            <span className="font-medium text-gray-900">
                                                {new Intl.NumberFormat('en-ZM', {
                                                    style: 'currency',
                                                    currency: 'ZMW',
                                                }).format(allowance.amount)}
                                            </span>
                                        </div>
                                    ))}
                                </>
                            )}
                            <div className="flex justify-between items-center pt-3 border-t border-gray-200">
                                <span className="font-semibold text-gray-900">Gross Pay</span>
                                <span className="font-bold text-gray-900">
                                    {new Intl.NumberFormat('en-ZM', {
                                        style: 'currency',
                                        currency: 'ZMW',
                                    }).format(payrollItem.gross_pay)}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Deductions */}
                    {payrollItem.deductions && payrollItem.deductions.length > 0 && (
                        <div className="border-t border-gray-200 pt-6 mb-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Deductions</h2>
                            <div className="space-y-3">
                                {payrollItem.deductions.map((deduction, index) => (
                                    <div key={index} className="flex justify-between items-center">
                                        <span className="text-gray-700">{deduction.name}</span>
                                        <span className="font-medium text-gray-900">
                                            {new Intl.NumberFormat('en-ZM', {
                                                style: 'currency',
                                                currency: 'ZMW',
                                            }).format(deduction.amount)}
                                        </span>
                                    </div>
                                ))}
                                <div className="flex justify-between items-center pt-3 border-t border-gray-200">
                                    <span className="font-semibold text-gray-900">Total Deductions</span>
                                    <span className="font-bold text-gray-900">
                                        {new Intl.NumberFormat('en-ZM', {
                                            style: 'currency',
                                            currency: 'ZMW',
                                        }).format(payrollItem.total_deductions)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Net Pay */}
                    <div className="border-t border-gray-200 pt-6">
                        <div className="flex justify-between items-center bg-teal-50 p-4 rounded-lg">
                            <span className="text-lg font-bold text-gray-900">Net Pay</span>
                            <span className="text-2xl font-bold text-teal-600">
                                {new Intl.NumberFormat('en-ZM', {
                                    style: 'currency',
                                    currency: 'ZMW',
                                }).format(payrollItem.net_pay)}
                            </span>
                        </div>
                    </div>

                    {payrollItem.notes && (
                        <div className="mt-6">
                            <div className="text-sm font-medium text-gray-700 mb-2">Notes</div>
                            <p className="text-gray-900">{payrollItem.notes}</p>
                        </div>
                    )}
                </div>
            </div>
        </SectionLayout>
    );
}

