import React, { useState } from 'react';
import axios from 'axios';

export default function ActionConfirmation({ action, onConfirm, onCancel, messageId, onUpdateMessage }) {
    const [confirming, setConfirming] = useState(false);
    const [result, setResult] = useState(null);
    const [executed, setExecuted] = useState(false);
    const preview = action.preview;

    const handleConfirm = async () => {
        setConfirming(true);

        try {
            const response = await axios.post(`/api/addy/actions/${action.action_id}/confirm`);
            setResult(response.data);
            setExecuted(true);
            
            // Update the message to mark action as executed
            if (onUpdateMessage && messageId) {
                onUpdateMessage(messageId, {
                    action_executed: true,
                    action_result: response.data,
                });
            }
            
            // Add success message to chat
            if (onConfirm) {
                onConfirm(response.data);
            }
        } catch (error) {
            setResult({
                success: false,
                message: error.response?.data?.message || 'Action failed',
            });
        } finally {
            setConfirming(false);
        }
    };

    const handleCancel = async () => {
        try {
            await axios.post(`/api/addy/actions/${action.action_id}/cancel`);
            
            if (onCancel) {
                onCancel();
            }
        } catch (error) {
            console.error('Failed to cancel action:', error);
        }
    };

    if (result || executed) {
        return (
            <div className={`p-4 rounded-lg mt-3 ${result?.success ? 'bg-green-50/80 backdrop-blur-sm border border-green-300/50' : 'bg-red-50/80 backdrop-blur-sm border border-red-300/50'}`}>
                <div className="flex items-center gap-2 mb-2">
                    {result?.success ? (
                        <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    ) : (
                        <svg className="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    )}
                    <span className={`font-semibold ${result?.success ? 'text-green-800' : 'text-red-800'}`}>
                        {result?.success ? 'Action Completed!' : 'Action Failed'}
                    </span>
                </div>
                <p className={result?.success ? 'text-green-700' : 'text-red-700'}>
                    {result?.message || 'Action completed successfully!'}
                </p>

                {result?.success && result.result?.sent && (
                    <div className="mt-2 text-sm text-green-700">
                        Successfully sent {result.result.sent} email(s)
                    </div>
                )}
                
                {result?.success && result.result?.invoice_id && (
                    <div className="mt-2 text-sm text-green-700">
                        Invoice created successfully! <a href={`/invoices/${result.result.invoice_id}`} className="underline font-medium">View Invoice</a>
                    </div>
                )}
                
                {result?.success && result.result?.transaction_id && (
                    <div className="mt-2 text-sm text-green-700">
                        Transaction recorded successfully!
                    </div>
                )}
                
                {result?.success && result.result?.imported_count !== undefined && (
                    <div className="mt-2 text-sm text-green-700">
                        <div className="font-medium">Import Summary:</div>
                        <div>✅ Imported: {result.result.imported_count} transaction(s)</div>
                        {result.result.skipped_count > 0 && (
                            <div>⏭️ Skipped: {result.result.skipped_count} duplicate(s)</div>
                        )}
                        {result.result.error_count > 0 && (
                            <div>❌ Errors: {result.result.error_count} transaction(s)</div>
                        )}
                    </div>
                )}

                {/* Report Display */}
                {result?.success && result.result?.report && (
                    <div className="mt-4 p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
                        <div className="mb-4">
                            <h3 className="text-lg font-bold text-gray-900 mb-1">
                                {result.result.report.title}
                            </h3>
                            {result.result.report.range?.label && (
                                <p className="text-sm text-gray-600">
                                    Period: {result.result.report.range.label}
                                </p>
                            )}
                        </div>

                        {/* Highlights */}
                        {result.result.report.highlights && result.result.report.highlights.length > 0 && (
                            <div className="mb-4">
                                <h4 className="text-sm font-semibold text-gray-700 mb-2">Key Metrics</h4>
                                <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    {result.result.report.highlights.map((highlight, index) => (
                                        <div key={index} className="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                            <div className="text-xs text-gray-600 mb-1">{highlight.label}</div>
                                            <div className="text-lg font-bold text-gray-900">
                                                {highlight.value}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Detailed Data */}
                        {result.result.report.data && (
                            <div className="mb-4 space-y-4">
                                {/* Cash Flow Data */}
                                {result.result.report.data.cash_flow && (
                                    <div>
                                        <h4 className="text-sm font-semibold text-gray-700 mb-2">Cash Flow Details</h4>
                                        <div className="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                            {result.result.report.data.cash_flow.income_total !== undefined && (
                                                <div className="flex justify-between mb-2">
                                                    <span className="text-sm text-gray-600">Total Income:</span>
                                                    <span className="text-sm font-semibold text-green-600">
                                                        {result.result.report.data.cash_flow.income_total?.toLocaleString('en-ZM', {
                                                            style: 'currency',
                                                            currency: 'ZMW',
                                                            minimumFractionDigits: 2
                                                        }) || 'ZMW 0.00'}
                                                    </span>
                                                </div>
                                            )}
                                            {result.result.report.data.cash_flow.expense_total !== undefined && (
                                                <div className="flex justify-between mb-2">
                                                    <span className="text-sm text-gray-600">Total Expenses:</span>
                                                    <span className="text-sm font-semibold text-red-600">
                                                        {result.result.report.data.cash_flow.expense_total?.toLocaleString('en-ZM', {
                                                            style: 'currency',
                                                            currency: 'ZMW',
                                                            minimumFractionDigits: 2
                                                        }) || 'ZMW 0.00'}
                                                    </span>
                                                </div>
                                            )}
                                            {result.result.report.data.cash_flow.net_cash_flow !== undefined && (
                                                <div className="flex justify-between pt-2 border-t border-gray-300">
                                                    <span className="text-sm font-semibold text-gray-700">Net Cash Flow:</span>
                                                    <span className={`text-sm font-bold ${
                                                        result.result.report.data.cash_flow.net_cash_flow >= 0 
                                                            ? 'text-green-600' 
                                                            : 'text-red-600'
                                                    }`}>
                                                        {result.result.report.data.cash_flow.net_cash_flow?.toLocaleString('en-ZM', {
                                                            style: 'currency',
                                                            currency: 'ZMW',
                                                            minimumFractionDigits: 2
                                                        }) || 'ZMW 0.00'}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Expense Data */}
                                {result.result.report.data.expenses && (
                                    <div>
                                        <h4 className="text-sm font-semibold text-gray-700 mb-2">Expense Breakdown</h4>
                                        {result.result.report.data.expenses.top_categories && 
                                         result.result.report.data.expenses.top_categories.length > 0 && (
                                            <div className="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                                <div className="space-y-2">
                                                    {result.result.report.data.expenses.top_categories.map((category, index) => (
                                                        <div key={index} className="flex justify-between items-center">
                                                            <span className="text-sm text-gray-700">{category.category}</span>
                                                            <div className="text-right">
                                                                <div className="text-sm font-semibold text-gray-900">
                                                                    {category.amount?.toLocaleString('en-ZM', {
                                                                        style: 'currency',
                                                                        currency: 'ZMW',
                                                                        minimumFractionDigits: 2
                                                                    }) || 'ZMW 0.00'}
                                                                </div>
                                                                <div className="text-xs text-gray-500">{category.percent}%</div>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                )}

                                {/* Sales Data */}
                                {result.result.report.data.invoice_count !== undefined && (
                                    <div>
                                        <h4 className="text-sm font-semibold text-gray-700 mb-2">Sales Summary</h4>
                                        <div className="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                            <div className="grid grid-cols-2 gap-3">
                                                <div>
                                                    <div className="text-xs text-gray-600 mb-1">Invoices</div>
                                                    <div className="text-sm font-semibold text-gray-900">
                                                        {result.result.report.data.invoice_count || 0}
                                                    </div>
                                                </div>
                                                {result.result.report.data.customers && (
                                                    <div>
                                                        <div className="text-xs text-gray-600 mb-1">Top Customers</div>
                                                        <div className="text-sm font-semibold text-gray-900">
                                                            {Object.keys(result.result.report.data.customers).length}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Warnings */}
                        {result.result.report.warnings && result.result.report.warnings.length > 0 && (
                            <div className="mt-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                <h4 className="text-sm font-semibold text-yellow-900 mb-2">⚠️ Warnings</h4>
                                <ul className="space-y-1">
                                    {result.result.report.warnings.map((warning, index) => (
                                        <li key={index} className="text-sm text-yellow-800">
                                            • {warning}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>
                )}
            </div>
        );
    }

    return (
        <div className="bg-teal-50 border border-teal-200 rounded-lg p-4 mt-3">
            <div className="mb-3">
                <h4 className="font-semibold text-teal-900 mb-1">
                    {preview.title}
                </h4>
                <p className="text-sm text-teal-700">
                    {preview.description}
                </p>
            </div>

            {/* Preview Items */}
            {preview.items && preview.items.length > 0 && (
                <div className="mb-3 space-y-2">
                    {preview.items.slice(0, 5).map((item, index) => (
                        <div key={index} className="bg-white rounded p-2 text-sm border border-gray-200">
                            {item.customer && (
                                <div>
                                    <span className="font-medium">{item.customer}</span>
                                    <span className="text-gray-600 ml-2">
                                        Invoice #{item.invoice_number} - ${item.amount}
                                    </span>
                                    {item.days_overdue && (
                                        <div className="text-xs text-gray-500 mt-1">
                                            {item.days_overdue} days overdue
                                        </div>
                                    )}
                                </div>
                            )}
                            {item.type && !item.flow_type && (
                                <div>
                                    <span className="font-medium">{item.type}</span>
                                    <span className="text-gray-600 ml-2">
                                        ${item.amount} - {item.category || 'Uncategorized'}
                                    </span>
                                </div>
                            )}
                            {/* Bank statement transaction */}
                            {(item.flow_type || (item.description && item.amount)) && (
                                <div className="flex items-center justify-between">
                                    <div className="flex-1">
                                        <div className="font-medium text-xs text-gray-500">{item.date || 'Unknown date'}</div>
                                        <div className="text-sm">{item.description || 'No description'}</div>
                                    </div>
                                    <div className="text-right">
                                        <div className={`font-semibold ${item.flow_type === 'income' ? 'text-green-600' : 'text-red-600'}`}>
                                            {item.flow_type === 'income' ? '+' : '-'}${item.amount?.toFixed(2) || '0.00'}
                                        </div>
                                        <div className="text-xs text-gray-500">{item.flow_type || 'expense'}</div>
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}
                    {preview.items.length > 5 && (
                        <div className="text-sm text-teal-600">
                            + {preview.items.length - 5} more transaction(s)
                        </div>
                    )}
                </div>
            )}
            
            {/* Summary for bank statements */}
            {preview.summary && (
                <div className="mb-3 p-3 bg-white rounded border border-gray-200">
                    <div className="text-sm font-semibold text-teal-900 mb-2">Import Summary</div>
                    <div className="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span className="text-gray-600">Total:</span>
                            <span className="font-medium ml-1">{preview.summary.total_transactions}</span>
                        </div>
                        <div>
                            <span className="text-gray-600">Income:</span>
                            <span className="font-medium text-green-600 ml-1">
                                {preview.summary.income_count} (${preview.summary.total_income?.toFixed(2) || '0.00'})
                            </span>
                        </div>
                        <div>
                            <span className="text-gray-600">Expenses:</span>
                            <span className="font-medium text-red-600 ml-1">
                                {preview.summary.expense_count} (${preview.summary.total_expenses?.toFixed(2) || '0.00'})
                            </span>
                        </div>
                        {preview.summary.duplicate_count > 0 && (
                            <div>
                                <span className="text-gray-600">Duplicates:</span>
                                <span className="font-medium text-yellow-600 ml-1">{preview.summary.duplicate_count}</span>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Warnings */}
            {preview.warnings && preview.warnings.length > 0 && (
                <div className="mb-3 p-2 bg-yellow-50 rounded text-sm text-yellow-800 border border-yellow-200">
                    {preview.warnings.map((warning, index) => (
                        <div key={index} className="flex items-start gap-2">
                            <span>⚠️</span>
                            <span>{warning}</span>
                        </div>
                    ))}
                </div>
            )}

            {/* Action Buttons */}
            <div className="flex gap-2">
                <button
                    onClick={handleConfirm}
                    disabled={confirming || executed}
                    className="flex-1 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-colors"
                >
                    {confirming ? (
                        <>
                            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                            <span>Executing...</span>
                        </>
                    ) : executed ? (
                        <>
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Completed</span>
                        </>
                    ) : (
                        <>
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Confirm & Execute</span>
                        </>
                    )}
                </button>

                <button
                    onClick={handleCancel}
                    disabled={confirming || executed}
                    className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    Cancel
                </button>
            </div>
        </div>
    );
}

