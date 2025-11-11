import { Head } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import FileUpload from '@/Components/FileUpload';
import { ArrowLeft, ArrowUp, ArrowDown, TrendingUp, TrendingDown } from 'lucide-react';

export default function MoneyMovementsShow({ movement }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: movement.currency || 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const getFlowTypeIcon = () => {
        if (movement.flow_type === 'income') {
            return <TrendingUp className="h-5 w-5 text-green-600" />;
        } else if (movement.flow_type === 'expense') {
            return <TrendingDown className="h-5 w-5 text-red-600" />;
        }
        return <ArrowUp className="h-5 w-5 text-blue-600" />;
    };

    const getFlowTypeColor = () => {
        if (movement.flow_type === 'income') {
            return 'text-green-600 bg-green-50';
        } else if (movement.flow_type === 'expense') {
            return 'text-red-600 bg-red-50';
        }
        return 'text-blue-600 bg-blue-50';
    };

    return (
        <SectionLayout sectionName="Money">
            <Head title={`${movement.flow_type === 'income' ? 'Income' : movement.flow_type === 'expense' ? 'Expense' : 'Transfer'} - ${movement.id}`} />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => window.history.back()}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">
                                {movement.flow_type === 'income' ? 'Income' : movement.flow_type === 'expense' ? 'Expense' : 'Transfer'}
                            </h1>
                            <p className="text-gray-500 mt-1">{formatCurrency(movement.amount)}</p>
                        </div>
                        <span className={`inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium ${getFlowTypeColor()}`}>
                            {getFlowTypeIcon()}
                            {movement.flow_type.charAt(0).toUpperCase() + movement.flow_type.slice(1)}
                        </span>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div className="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Amount</h3>
                            <p className="text-2xl font-bold text-gray-900">{formatCurrency(movement.amount)}</p>
                            <p className="text-sm text-gray-500 mt-1">{movement.currency}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Date</h3>
                            <p className="text-gray-900">{new Date(movement.transaction_date).toLocaleDateString()}</p>
                            {movement.created_at && (
                                <p className="text-sm text-gray-500 mt-1">
                                    Recorded: {new Date(movement.created_at).toLocaleDateString()}
                                </p>
                            )}
                        </div>
                    </div>

                    {movement.description && (
                        <div className="mb-6 pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Description</h3>
                            <p className="text-gray-900">{movement.description}</p>
                        </div>
                    )}

                    {movement.category && (
                        <div className="mb-6 pt-4 border-t border-gray-200">
                            <h3 className="text-sm font-medium text-gray-500 mb-2">Category</h3>
                            <p className="text-gray-900">{movement.category}</p>
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-6 pt-4 border-t border-gray-200">
                        {movement.flow_type === 'expense' && movement.from_account && (
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 mb-2">From Account</h3>
                                <p className="text-gray-900">{movement.from_account.name}</p>
                            </div>
                        )}
                        {movement.flow_type === 'income' && movement.to_account && (
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 mb-2">To Account</h3>
                                <p className="text-gray-900">{movement.to_account.name}</p>
                            </div>
                        )}
                        {movement.flow_type === 'transfer' && (
                            <>
                                {movement.from_account && (
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500 mb-2">From Account</h3>
                                        <p className="text-gray-900">{movement.from_account.name}</p>
                                    </div>
                                )}
                                {movement.to_account && (
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500 mb-2">To Account</h3>
                                        <p className="text-gray-900">{movement.to_account.name}</p>
                                    </div>
                                )}
                            </>
                        )}
                        {movement.created_by && (
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 mb-2">Recorded By</h3>
                                <p className="text-gray-900">{movement.created_by.name}</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Attachments */}
                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    <FileUpload
                        attachableType="App\Models\MoneyMovement"
                        attachableId={movement.id}
                        category={movement.flow_type === 'expense' ? 'receipt' : 'document'}
                        existingAttachments={movement.attachments || []}
                    />
                </div>
            </div>
        </SectionLayout>
    );
}

