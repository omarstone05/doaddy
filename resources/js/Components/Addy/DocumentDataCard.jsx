import React from 'react';
import { 
    Receipt, 
    FileText, 
    CreditCard, 
    Smartphone, 
    Building2,
    Calendar,
    DollarSign,
    User,
    Hash,
    CheckCircle,
    AlertTriangle,
    Loader2,
    Download
} from 'lucide-react';
import { useCurrency } from '@/hooks/useCurrency';

// Main DocumentDataCard component with variants
export default function DocumentDataCard({ 
    ocrResult, 
    onImport, 
    onReview, 
    isImporting = false,
    isImported = false 
}) {
    const { formatCurrency, symbol } = useCurrency();
    const { document_type, data, confidence, auto_importable, requires_review } = ocrResult;

    const getConfidenceLevel = (score) => {
        if (score >= 0.85) return { label: 'High Confidence', color: 'text-green-600', bg: 'bg-green-100' };
        if (score >= 0.65) return { label: 'Medium Confidence', color: 'text-amber-600', bg: 'bg-amber-100' };
        return { label: 'Low Confidence', color: 'text-red-600', bg: 'bg-red-100' };
    };

    const confidenceInfo = getConfidenceLevel(confidence);
    const confidencePercent = Math.round(confidence * 100);

    // Render based on document type
    const renderContent = () => {
        switch (document_type) {
            case 'receipt':
                return <ReceiptCardContent data={data} formatCurrency={formatCurrency} symbol={symbol} />;
            case 'invoice':
                return <InvoiceCardContent data={data} formatCurrency={formatCurrency} symbol={symbol} />;
            case 'bank_statement':
                return <BankStatementCardContent data={data} formatCurrency={formatCurrency} symbol={symbol} />;
            case 'mobile_money':
                return <MobileMoneyCardContent data={data} formatCurrency={formatCurrency} symbol={symbol} />;
            default:
                return <GenericDocumentContent data={data} formatCurrency={formatCurrency} symbol={symbol} />;
        }
    };

    const getIcon = () => {
        switch (document_type) {
            case 'receipt':
                return <Receipt className="w-5 h-5" />;
            case 'invoice':
                return <FileText className="w-5 h-5" />;
            case 'bank_statement':
                return <Building2 className="w-5 h-5" />;
            case 'mobile_money':
                return <Smartphone className="w-5 h-5" />;
            default:
                return <CreditCard className="w-5 h-5" />;
        }
    };

    const getTitle = () => {
        switch (document_type) {
            case 'receipt':
                return 'Receipt';
            case 'invoice':
                return 'Invoice';
            case 'bank_statement':
                return 'Bank Statement';
            case 'mobile_money':
                return 'Mobile Money';
            default:
                return 'Document';
        }
    };

    if (isImported) {
        return (
            <div className="bg-green-50 border border-green-200 rounded-xl p-4 mt-3">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-green-100 rounded-lg">
                        <CheckCircle className="w-5 h-5 text-green-600" />
                    </div>
                    <div>
                        <p className="font-medium text-green-800">Successfully Imported</p>
                        <p className="text-sm text-green-600">
                            {getTitle()} has been added to your records
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white border border-gray-200 rounded-xl overflow-hidden mt-3 shadow-sm">
            {/* Header */}
            <div className="flex items-center justify-between p-3 bg-gray-50 border-b border-gray-100">
                <div className="flex items-center gap-2">
                    <div className="p-1.5 bg-teal-100 rounded-lg text-teal-600">
                        {getIcon()}
                    </div>
                    <span className="font-semibold text-gray-900">{getTitle()}</span>
                </div>
                <div className={`flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium ${confidenceInfo.bg} ${confidenceInfo.color}`}>
                    {confidence >= 0.85 ? (
                        <CheckCircle className="w-3.5 h-3.5" />
                    ) : (
                        <AlertTriangle className="w-3.5 h-3.5" />
                    )}
                    {confidencePercent}%
                </div>
            </div>

            {/* Content */}
            <div className="p-4">
                {renderContent()}
            </div>

            {/* Actions */}
            <div className="flex items-center gap-2 p-3 bg-gray-50 border-t border-gray-100">
                {auto_importable && !requires_review ? (
                    <button
                        onClick={onImport}
                        disabled={isImporting}
                        className="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 transition-colors font-medium text-sm"
                    >
                        {isImporting ? (
                            <>
                                <Loader2 className="w-4 h-4 animate-spin" />
                                Importing...
                            </>
                        ) : (
                            <>
                                <Download className="w-4 h-4" />
                                Import {getTitle()}
                            </>
                        )}
                    </button>
                ) : (
                    <button
                        onClick={onReview}
                        disabled={isImporting}
                        className="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 disabled:opacity-50 transition-colors font-medium text-sm"
                    >
                        <AlertTriangle className="w-4 h-4" />
                        Review & Import
                    </button>
                )}
                
                {auto_importable && (
                    <button
                        onClick={onReview}
                        disabled={isImporting}
                        className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 disabled:opacity-50 transition-colors text-sm"
                    >
                        Review
                    </button>
                )}
            </div>
        </div>
    );
}

// Receipt Card Content
function ReceiptCardContent({ data, formatCurrency, symbol }) {
    return (
        <div className="space-y-3">
            {/* Merchant */}
            {data.merchant && (
                <div className="flex items-center gap-2">
                    <User className="w-4 h-4 text-gray-400" />
                    <span className="font-medium text-gray-900">{data.merchant}</span>
                </div>
            )}
            
            {/* Amount */}
            {(data.total || data.amount) && (
                <div className="flex items-center gap-2">
                    <DollarSign className="w-4 h-4 text-gray-400" />
                    <span className="text-xl font-bold text-gray-900">
                        {formatCurrency(data.total || data.amount)}
                    </span>
                </div>
            )}
            
            {/* Date */}
            {data.date && (
                <div className="flex items-center gap-2">
                    <Calendar className="w-4 h-4 text-gray-400" />
                    <span className="text-gray-600">{data.date}</span>
                </div>
            )}

            {/* Category */}
            {data.category && (
                <div className="inline-block px-2 py-1 bg-purple-100 text-purple-700 rounded-lg text-xs font-medium">
                    {data.category}
                </div>
            )}

            {/* Items preview */}
            {data.items && data.items.length > 0 && (
                <div className="pt-2 border-t border-gray-100">
                    <p className="text-xs text-gray-500 mb-1">{data.items.length} items detected</p>
                    <div className="text-sm text-gray-600 truncate">
                        {data.items.slice(0, 2).map(item => item.name || item.description).join(', ')}
                        {data.items.length > 2 && '...'}
                    </div>
                </div>
            )}
        </div>
    );
}

// Invoice Card Content
function InvoiceCardContent({ data, formatCurrency, symbol }) {
    return (
        <div className="space-y-3">
            {/* Vendor/Customer */}
            {(data.vendor || data.customer) && (
                <div className="flex items-center gap-2">
                    <User className="w-4 h-4 text-gray-400" />
                    <span className="font-medium text-gray-900">{data.vendor || data.customer}</span>
                </div>
            )}

            {/* Invoice Number */}
            {data.invoice_number && (
                <div className="flex items-center gap-2">
                    <Hash className="w-4 h-4 text-gray-400" />
                    <span className="text-gray-600">Invoice #{data.invoice_number}</span>
                </div>
            )}
            
            {/* Amount */}
            {(data.total || data.amount) && (
                <div className="flex items-center gap-2">
                    <DollarSign className="w-4 h-4 text-gray-400" />
                    <span className="text-xl font-bold text-gray-900">
                        {formatCurrency(data.total || data.amount)}
                    </span>
                </div>
            )}
            
            {/* Date */}
            {data.date && (
                <div className="flex items-center gap-2">
                    <Calendar className="w-4 h-4 text-gray-400" />
                    <span className="text-gray-600">{data.date}</span>
                </div>
            )}

            {/* Due Date */}
            {data.due_date && (
                <div className="text-sm">
                    <span className="text-gray-500">Due: </span>
                    <span className="text-gray-700">{data.due_date}</span>
                </div>
            )}

            {/* Items */}
            {data.items && data.items.length > 0 && (
                <div className="pt-2 border-t border-gray-100">
                    <p className="text-xs text-gray-500 mb-1">{data.items.length} line items</p>
                </div>
            )}
        </div>
    );
}

// Bank Statement Card Content
function BankStatementCardContent({ data, formatCurrency, symbol }) {
    const transactionCount = data.transactions?.length || 0;
    
    return (
        <div className="space-y-3">
            {/* Bank Name */}
            {data.bank_name && (
                <div className="flex items-center gap-2">
                    <Building2 className="w-4 h-4 text-gray-400" />
                    <span className="font-medium text-gray-900">{data.bank_name}</span>
                </div>
            )}

            {/* Account Number */}
            {data.account_number && (
                <div className="flex items-center gap-2">
                    <Hash className="w-4 h-4 text-gray-400" />
                    <span className="text-gray-600">****{data.account_number.slice(-4)}</span>
                </div>
            )}
            
            {/* Period */}
            {(data.start_date || data.period) && (
                <div className="flex items-center gap-2">
                    <Calendar className="w-4 h-4 text-gray-400" />
                    <span className="text-gray-600">
                        {data.period || `${data.start_date} - ${data.end_date || 'Present'}`}
                    </span>
                </div>
            )}

            {/* Transaction Summary */}
            <div className="pt-2 border-t border-gray-100">
                <div className="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p className="text-gray-500">Transactions</p>
                        <p className="font-semibold text-gray-900">{transactionCount} found</p>
                    </div>
                    {data.closing_balance && (
                        <div>
                            <p className="text-gray-500">Closing Balance</p>
                            <p className="font-semibold text-gray-900">{formatCurrency(data.closing_balance)}</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

// Mobile Money Card Content
function MobileMoneyCardContent({ data, formatCurrency, symbol }) {
    return (
        <div className="space-y-3">
            {/* Provider */}
            {data.provider && (
                <div className="flex items-center gap-2">
                    <Smartphone className="w-4 h-4 text-gray-400" />
                    <span className="font-medium text-gray-900">{data.provider}</span>
                </div>
            )}

            {/* Transaction ID */}
            {data.transaction_id && (
                <div className="flex items-center gap-2">
                    <Hash className="w-4 h-4 text-gray-400" />
                    <span className="text-gray-600 font-mono text-sm">{data.transaction_id}</span>
                </div>
            )}
            
            {/* Amount */}
            {data.amount && (
                <div className="flex items-center gap-2">
                    <DollarSign className="w-4 h-4 text-gray-400" />
                    <span className="text-xl font-bold text-gray-900">
                        {formatCurrency(data.amount)}
                    </span>
                    {data.type && (
                        <span className={`text-xs px-2 py-0.5 rounded-full ${
                            data.type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                        }`}>
                            {data.type}
                        </span>
                    )}
                </div>
            )}
            
            {/* Date */}
            {data.date && (
                <div className="flex items-center gap-2">
                    <Calendar className="w-4 h-4 text-gray-400" />
                    <span className="text-gray-600">{data.date}</span>
                </div>
            )}

            {/* Phone */}
            {data.phone && (
                <div className="text-sm text-gray-600">
                    Phone: {data.phone}
                </div>
            )}
        </div>
    );
}

// Generic Document Content
function GenericDocumentContent({ data, formatCurrency, symbol }) {
    const displayFields = Object.entries(data)
        .filter(([key, value]) => 
            value && 
            !['type', 'raw_text', 'items', 'transactions'].includes(key) &&
            typeof value !== 'object'
        )
        .slice(0, 6);

    return (
        <div className="space-y-2">
            {displayFields.map(([key, value]) => (
                <div key={key} className="flex items-start gap-2 text-sm">
                    <span className="text-gray-500 capitalize min-w-24">{key.replace(/_/g, ' ')}:</span>
                    <span className="text-gray-900 font-medium">
                        {key.includes('amount') || key.includes('total') ? formatCurrency(value) : String(value)}
                    </span>
                </div>
            ))}
        </div>
    );
}
