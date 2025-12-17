import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { ArrowLeft, Edit, ArrowDown, Download, File } from 'lucide-react';

export default function ExpensesShow({ expense }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: expense.currency || 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount || 0);
    };

    return (
        <SectionLayout sectionName="Money">
            <Head title={`Expense - ${expense.description}`} />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => router.visit('/expenses')}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">{expense.description}</h1>
                            <p className="text-gray-500 mt-1">
                                {new Date(expense.transaction_date).toLocaleDateString('en-ZM', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                })}
                            </p>
                        </div>
                        <Link href={`/expenses/${expense.id}/edit`}>
                            <Button>
                                <Edit className="h-4 w-4 mr-2" />
                                Edit
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <Card className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <ArrowDown className="h-5 w-5 text-red-500" />
                            Expense Details
                        </h2>
                        <div className="space-y-3">
                            <div>
                                <span className="text-sm font-medium text-gray-500">Amount:</span>
                                <p className="text-2xl font-bold text-red-600 mt-1">{formatCurrency(expense.amount)}</p>
                            </div>
                            {expense.category && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">Category:</span>
                                    <p className="text-sm text-gray-900 mt-1">{expense.category}</p>
                                </div>
                            )}
                            {expense.from_account && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">Account:</span>
                                    <p className="text-sm text-gray-900 mt-1">{expense.from_account.name}</p>
                                </div>
                            )}
                            {expense.created_by && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">Recorded By:</span>
                                    <p className="text-sm text-gray-900 mt-1">{expense.created_by.name}</p>
                                </div>
                            )}
                        </div>
                    </Card>

                    {expense.attachments && expense.attachments.length > 0 && (
                        <Card className="p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Attachments</h2>
                            <div className="space-y-2">
                                {expense.attachments.map((attachment) => (
                                    <div key={attachment.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div className="flex items-center gap-2">
                                            <File className="h-4 w-4 text-gray-400" />
                                            <span className="text-sm text-gray-900">{attachment.name}</span>
                                        </div>
                                        {attachment.url && (
                                            <a
                                                href={attachment.url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-teal-600 hover:text-teal-800"
                                            >
                                                <Download className="h-4 w-4" />
                                            </a>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </Card>
                    )}
                </div>
            </div>
        </SectionLayout>
    );
}

