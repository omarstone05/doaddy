import { useState, useRef, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import UploadModal from '@/Components/UploadModal';
import { 
    Plus, 
    ChevronDown, 
    Search, 
    Filter, 
    ArrowUpDown, 
    Check, 
    MoreVertical,
    Edit,
    Upload,
    Trash2,
    Calendar,
    ShieldCheck,
    X,
    AlertTriangle,
    CheckCircle2
} from 'lucide-react';
import axios from 'axios';

export default function TransactionsIndex({ transactions, accounts, totalBalance, outstandingInvoices, categories, filters }) {
    const [selectedTransactions, setSelectedTransactions] = useState([]);
    const [showAddDropdown, setShowAddDropdown] = useState(false);
    const [showMoreDropdown, setShowMoreDropdown] = useState(false);
    const [showCategoryDropdown, setShowCategoryDropdown] = useState({});
    const [showActionsDropdown, setShowActionsDropdown] = useState({});
    const [showFilterModal, setShowFilterModal] = useState(false);
    const [showSortModal, setShowSortModal] = useState(false);
    const [showVerifyModal, setShowVerifyModal] = useState(false);
    const [verificationData, setVerificationData] = useState(null);
    const [verificationLoading, setVerificationLoading] = useState(false);
    const [selectedAccount, setSelectedAccount] = useState(filters?.account_id || 'all');
    const [uploadReceiptModal, setUploadReceiptModal] = useState(null);
    const [uploadReceiptFile, setUploadReceiptFile] = useState(null);
    const [showUploadModal, setShowUploadModal] = useState(false);
    const fileInputRef = useRef(null);
    const addDropdownRef = useRef(null);
    const moreDropdownRef = useRef(null);

    const formatCurrency = (amount, currency = 'ZMW') => {
        const numAmount = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
        }).format(numAmount);
    };

    const formatDate = (date) => {
        return new Date(date).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    };

    const handleSelectAll = (e) => {
        if (e.target.checked) {
            setSelectedTransactions(transactions.data.map(t => t.id));
        } else {
            setSelectedTransactions([]);
        }
    };

    const handleSelectTransaction = (id) => {
        setSelectedTransactions(prev => 
            prev.includes(id) 
                ? prev.filter(t => t !== id)
                : [...prev, id]
        );
    };

    const handleBulkDelete = async () => {
        if (selectedTransactions.length === 0) return;
        if (!confirm(`Are you sure you want to delete ${selectedTransactions.length} transaction(s)?`)) return;

        try {
            const response = await axios.post('/transactions/bulk-delete', {
                ids: selectedTransactions,
            });
            if (response.data.success) {
                setSelectedTransactions([]);
                router.reload();
            }
        } catch (error) {
            alert('Failed to delete transactions');
        }
    };

    const handleUpdateCategory = async (transactionId, category) => {
        try {
            await axios.patch(`/transactions/${transactionId}`, { category });
            setShowCategoryDropdown({});
            router.reload({ only: ['transactions'] });
        } catch (error) {
            alert('Failed to update category');
        }
    };

    const handleUploadReceipt = async () => {
        if (!uploadReceiptFile || !uploadReceiptModal) return;

        const formData = new FormData();
        formData.append('file', uploadReceiptFile);

        try {
            const response = await axios.post(`/transactions/${uploadReceiptModal}/upload-receipt`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            if (response.data.success) {
                setUploadReceiptModal(null);
                setUploadReceiptFile(null);
                router.reload();
            }
        } catch (error) {
            alert('Failed to upload receipt');
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this transaction?')) return;
        try {
            await axios.post('/transactions/bulk-delete', { ids: [id] });
            router.reload();
        } catch (error) {
            alert('Failed to delete transaction');
        }
    };

    const getAccountName = (transaction) => {
        if (transaction.flow_type === 'income' && transaction.to_account) {
            return transaction.to_account.name;
        }
        if (transaction.flow_type === 'expense' && transaction.from_account) {
            return transaction.from_account.name;
        }
        if (transaction.flow_type === 'transfer') {
            return `${transaction.from_account?.name || ''} → ${transaction.to_account?.name || ''}`;
        }
        return '-';
    };

    const getAmountColor = (transaction) => {
        if (transaction.flow_type === 'income') return 'text-green-600';
        if (transaction.flow_type === 'expense') return 'text-gray-900';
        return 'text-blue-600';
    };

    const getAmountPrefix = (transaction) => {
        if (transaction.flow_type === 'expense') return '';
        if (transaction.flow_type === 'income') return '+';
        return '';
    };

    // Close dropdowns when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (addDropdownRef.current && !addDropdownRef.current.contains(event.target)) {
                setShowAddDropdown(false);
            }
            if (moreDropdownRef.current && !moreDropdownRef.current.contains(event.target)) {
                setShowMoreDropdown(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    return (
        <SectionLayout sectionName="Money">
            <Head title="Transactions" />
            <div>
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <h1 className="text-3xl font-bold text-gray-900">Transactions</h1>
                    <div className="flex gap-2 relative">
                        <div className="relative" ref={addDropdownRef}>
                            <Button onClick={() => setShowAddDropdown(!showAddDropdown)}>
                                Add transaction
                                <ChevronDown className="h-4 w-4 ml-2" />
                            </Button>
                            {showAddDropdown && (
                                <div className="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                                    <Link href="/transactions/create?type=income" className="block px-4 py-2 hover:bg-gray-50">
                                        Income
                                    </Link>
                                    <Link href="/transactions/create?type=expense" className="block px-4 py-2 hover:bg-gray-50">
                                        Expense
                                    </Link>
                                    <Link href="/transactions/create?type=transfer" className="block px-4 py-2 hover:bg-gray-50">
                                        Transfer
                                    </Link>
                                </div>
                            )}
                        </div>
                        <div className="relative" ref={moreDropdownRef}>
                            <Button variant="outline" onClick={() => setShowMoreDropdown(!showMoreDropdown)}>
                                More
                                <ChevronDown className="h-4 w-4 ml-2" />
                            </Button>
                            {showMoreDropdown && (
                                <div className="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                                    <button 
                                        onClick={() => {
                                            setShowMoreDropdown(false);
                                            setShowUploadModal(true);
                                        }}
                                        className="block w-full text-left px-4 py-2 hover:bg-gray-50"
                                    >
                                        Upload transactions
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Account Summary and Reconciliation */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div className="relative">
                            <select
                                value={selectedAccount}
                                onChange={(e) => {
                                    setSelectedAccount(e.target.value);
                                    router.visit(e.target.value === 'all' 
                                        ? '/transactions' 
                                        : `/transactions?account_id=${e.target.value}`
                                    );
                                }}
                                className="appearance-none bg-transparent border-none text-lg font-semibold text-gray-900 pr-8 cursor-pointer"
                            >
                                <option value="all">All accounts</option>
                                {accounts.map(account => (
                                    <option key={account.id} value={account.id}>{account.name}</option>
                                ))}
                            </select>
                            <ChevronDown className="absolute right-0 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500 pointer-events-none" />
                        </div>
                        <div className="text-2xl font-bold text-gray-900">
                            {formatCurrency(totalBalance)}
                        </div>
                    </div>
                    <Button
                        variant="outline"
                        onClick={async () => {
                            // First filter to only unverified transactions
                            router.visit('/transactions?is_verified=false', {
                                preserveState: false,
                                preserveScroll: false,
                                onSuccess: async () => {
                                    setVerificationLoading(true);
                                    setShowVerifyModal(true);
                                    try {
                                        const response = await axios.get('/transactions/verify');
                                        setVerificationData(response.data);
                                    } catch (error) {
                                        console.error('Failed to load verification data:', error);
                                        alert('Failed to load verification data');
                                    } finally {
                                        setVerificationLoading(false);
                                    }
                                }
                            });
                        }}
                    >
                        <ShieldCheck className="h-4 w-4 mr-2" />
                        Verify
                    </Button>
                </div>

                {/* Transaction Management Bar */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={selectedTransactions.length === transactions.data.length && transactions.data.length > 0}
                                onChange={handleSelectAll}
                                className="rounded border-gray-300 text-teal-500 focus:ring-teal-500"
                            />
                            <span className="text-sm font-medium text-gray-700">Select all</span>
                        </label>
                        {selectedTransactions.length > 0 && (
                            <div className="flex items-center gap-2">
                                <button
                                    onClick={handleBulkDelete}
                                    className="p-2 text-red-600 hover:bg-red-50 rounded-lg"
                                    title="Delete"
                                >
                                    <Trash2 className="h-4 w-4" />
                                </button>
                                <span className="text-sm text-gray-600">{selectedTransactions.length} selected</span>
                            </div>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setShowFilterModal(true)}
                        >
                            <Filter className="h-4 w-4 mr-2" />
                            Filter
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setShowSortModal(true)}
                        >
                            <ArrowUpDown className="h-4 w-4 mr-2" />
                            Sort
                        </Button>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                                type="text"
                                placeholder="Search transactions"
                                defaultValue={filters?.search || ''}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        router.visit(`/transactions?search=${e.target.value}`);
                                    }
                                }}
                                className="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent w-64"
                            />
                        </div>
                    </div>
                </div>

                {/* Transactions Table */}
                <Card className="overflow-hidden">
                    {transactions.data && transactions.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                            <input
                                                type="checkbox"
                                                checked={selectedTransactions.length === transactions.data.length}
                                                onChange={handleSelectAll}
                                                className="rounded border-gray-300 text-teal-500 focus:ring-teal-500"
                                            />
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Verified</th>
                                        <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {transactions.data.map((transaction) => (
                                        <tr 
                                            key={transaction.id} 
                                            className={`hover:bg-gray-50 ${selectedTransactions.includes(transaction.id) ? 'bg-blue-50' : ''}`}
                                        >
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <input
                                                    type="checkbox"
                                                    checked={selectedTransactions.includes(transaction.id)}
                                                    onChange={() => handleSelectTransaction(transaction.id)}
                                                    className="rounded border-gray-300 text-teal-500 focus:ring-teal-500"
                                                />
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div className="flex items-center gap-2">
                                                    <Calendar className="h-4 w-4 text-gray-400" />
                                                    {formatDate(transaction.transaction_date)}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                <div className="font-medium">{transaction.description}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {getAccountName(transaction)}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600 relative">
                                                <div className="relative">
                                                    <button
                                                        onClick={() => setShowCategoryDropdown({
                                                            ...showCategoryDropdown,
                                                            [transaction.id]: !showCategoryDropdown[transaction.id]
                                                        })}
                                                        className="text-left w-full hover:bg-gray-100 px-2 py-1 rounded"
                                                    >
                                                        {transaction.category || 'Uncategorized'}
                                                        <ChevronDown className="inline-block h-3 w-3 ml-1" />
                                                    </button>
                                                    {showCategoryDropdown[transaction.id] && (
                                                        <div className="absolute left-0 mt-1 w-80 bg-white border border-gray-200 rounded-lg shadow-lg z-50 max-h-96 overflow-y-auto">
                                                            <div className="p-2 border-b">
                                                                <input
                                                                    type="text"
                                                                    placeholder="Search categories..."
                                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                                                />
                                                            </div>
                                                            {outstandingInvoices.length > 0 && (
                                                                <div className="p-2">
                                                                    <div className="text-xs font-semibold text-gray-500 uppercase mb-2 px-2">
                                                                        Outstanding Invoices
                                                                    </div>
                                                                    {outstandingInvoices.map((invoice) => (
                                                                        <button
                                                                            key={invoice.id}
                                                                            onClick={() => handleUpdateCategory(transaction.id, invoice.display)}
                                                                            className="w-full text-left px-2 py-2 hover:bg-gray-100 rounded text-sm"
                                                                        >
                                                                            <div className="font-medium">{invoice.display}</div>
                                                                        </button>
                                                                    ))}
                                                                </div>
                                                            )}
                                                            <div className="p-2 border-t">
                                                                <div className="text-xs font-semibold text-gray-500 uppercase mb-2 px-2">
                                                                    Categories
                                                                </div>
                                                                {categories.map((category) => (
                                                                    <button
                                                                        key={category}
                                                                        onClick={() => handleUpdateCategory(transaction.id, category)}
                                                                        className="w-full text-left px-2 py-2 hover:bg-gray-100 rounded text-sm"
                                                                    >
                                                                        {category}
                                                                    </button>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className={`px-6 py-4 whitespace-nowrap text-sm text-right font-medium ${getAmountColor(transaction)}`}>
                                                {getAmountPrefix(transaction)}{formatCurrency(transaction.amount, transaction.currency)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-center">
                                                {transaction.is_verified ? (
                                                    <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                                        <CheckCircle2 className="h-3 w-3" />
                                                        Verified
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                                        <AlertTriangle className="h-3 w-3" />
                                                        Unverified
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-center">
                                                <div className="flex items-center justify-center gap-1">
                                                    <button className="p-1 text-gray-400 hover:text-gray-600">
                                                        <Check className="h-4 w-4" />
                                                    </button>
                                                    <div className="relative">
                                                        <button
                                                            onClick={() => setShowActionsDropdown({
                                                                ...showActionsDropdown,
                                                                [transaction.id]: !showActionsDropdown[transaction.id]
                                                            })}
                                                            className="p-1 text-gray-400 hover:text-gray-600"
                                                        >
                                                            <ChevronDown className="h-4 w-4" />
                                                        </button>
                                                        {showActionsDropdown[transaction.id] && (
                                                            <div className="absolute right-0 mt-1 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                                                                <Link
                                                                    href={`/transactions/${transaction.id}`}
                                                                    className="block px-4 py-2 hover:bg-gray-50 text-sm"
                                                                >
                                                                    <Edit className="inline-block h-4 w-4 mr-2" />
                                                                    Edit more details
                                                                </Link>
                                                                <button
                                                                    onClick={() => {
                                                                        setUploadReceiptModal(transaction.id);
                                                                        setShowActionsDropdown({});
                                                                        fileInputRef.current?.click();
                                                                    }}
                                                                    className="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm"
                                                                >
                                                                    <Upload className="inline-block h-4 w-4 mr-2" />
                                                                    Upload receipt
                                                                </button>
                                                                <button
                                                                    onClick={() => {
                                                                        setShowActionsDropdown({});
                                                                        handleDelete(transaction.id);
                                                                    }}
                                                                    className="w-full text-left px-4 py-2 hover:bg-red-50 text-red-600 text-sm"
                                                                >
                                                                    <Trash2 className="inline-block h-4 w-4 mr-2" />
                                                                    Delete
                                                                </button>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <p className="text-gray-500 mb-4">No transactions found</p>
                            <Button onClick={() => router.visit('/transactions/create')}>
                                <Plus className="h-4 w-4 mr-2" />
                                Add transaction
                            </Button>
                        </div>
                    )}

                    {/* Pagination */}
                    {transactions.links && transactions.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-gray-500">
                                    Showing {transactions.from} to {transactions.to} of {transactions.total} results
                                </div>
                                <div className="flex gap-2">
                                    {transactions.links.map((link, index) => (
                                        <button
                                            key={index}
                                            onClick={() => link.url && router.visit(link.url)}
                                            disabled={!link.url}
                                            className={`px-3 py-1 text-sm rounded-lg ${
                                                link.active
                                                    ? 'bg-teal-500 text-white'
                                                    : link.url
                                                    ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </Card>

                {/* Hidden file input for receipt upload */}
                <input
                    ref={fileInputRef}
                    type="file"
                    accept=".pdf,.jpg,.jpeg,.png"
                    className="hidden"
                    onChange={(e) => {
                        if (e.target.files[0]) {
                            setUploadReceiptFile(e.target.files[0]);
                        }
                    }}
                />

                {/* Upload Receipt Modal */}
                {uploadReceiptModal && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div className="bg-white rounded-lg p-6 max-w-md w-full">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-semibold">Upload Receipt</h3>
                                <button
                                    onClick={() => {
                                        setUploadReceiptModal(null);
                                        setUploadReceiptFile(null);
                                    }}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </div>
                            {uploadReceiptFile ? (
                                <div className="space-y-4">
                                    <p className="text-sm text-gray-600">File: {uploadReceiptFile.name}</p>
                                    <div className="flex gap-2">
                                        <Button onClick={handleUploadReceipt}>Upload</Button>
                                        <Button
                                            variant="outline"
                                            onClick={() => {
                                                setUploadReceiptFile(null);
                                                fileInputRef.current.value = '';
                                            }}
                                        >
                                            Change File
                                        </Button>
                                    </div>
                                </div>
                            ) : (
                                <div>
                                    <p className="text-sm text-gray-600 mb-4">Please select a file to upload</p>
                                    <Button onClick={() => fileInputRef.current?.click()}>
                                        Choose File
                                    </Button>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Verify Modal */}
                {showVerifyModal && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                        <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                            <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <ShieldCheck className="h-6 w-6 text-teal-500" />
                                    <h3 className="text-xl font-semibold">Transaction Verification</h3>
                                </div>
                                <button
                                    onClick={() => {
                                        setShowVerifyModal(false);
                                        setVerificationData(null);
                                    }}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </div>

                            {verificationLoading ? (
                                <div className="p-12 text-center">
                                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-teal-500 mx-auto mb-4"></div>
                                    <p className="text-gray-600">Analyzing transactions...</p>
                                </div>
                            ) : verificationData ? (
                                <div className="p-6 space-y-6">
                                    {/* Summary */}
                                    <div className="grid grid-cols-3 gap-4">
                                        <Card className="p-4">
                                            <div className="flex items-center gap-2 mb-2">
                                                <CheckCircle2 className="h-5 w-5 text-green-500" />
                                                <span className="text-sm font-medium text-gray-700">Matched</span>
                                            </div>
                                            <p className="text-2xl font-bold text-gray-900">{verificationData.matched_count || 0}</p>
                                        </Card>
                                        <Card className="p-4">
                                            <div className="flex items-center gap-2 mb-2">
                                                <AlertTriangle className="h-5 w-5 text-yellow-500" />
                                                <span className="text-sm font-medium text-gray-700">Suggestions</span>
                                            </div>
                                            <p className="text-2xl font-bold text-gray-900">{verificationData.suggestions_count || 0}</p>
                                        </Card>
                                        <Card className="p-4">
                                            <div className="flex items-center gap-2 mb-2">
                                                <X className="h-5 w-5 text-red-500" />
                                                <span className="text-sm font-medium text-gray-700">Discrepancies</span>
                                            </div>
                                            <p className="text-2xl font-bold text-gray-900">{verificationData.discrepancies_count || 0}</p>
                                        </Card>
                                    </div>

                                    {/* Invoice Matches */}
                                    {verificationData.invoice_matches && verificationData.invoice_matches.length > 0 && (
                                        <div>
                                            <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                                <CheckCircle2 className="h-5 w-5 text-green-500" />
                                                Automatic Invoice Matches
                                            </h4>
                                            <div className="space-y-3">
                                                {verificationData.invoice_matches.map((match, idx) => (
                                                    <Card key={idx} className="p-4 border-l-4 border-l-green-500">
                                                        <div className="flex items-start justify-between">
                                                            <div className="flex-1">
                                                                <div className="flex items-center gap-2 mb-2">
                                                                    <span className="font-medium text-gray-900">Transaction: {match.transaction_description}</span>
                                                                    <span className="text-sm text-gray-500">({formatCurrency(match.transaction_amount)})</span>
                                                                </div>
                                                                <div className="text-sm text-gray-600">
                                                                    Matches <span className="font-medium">Invoice #{match.invoice_number}</span> from {match.customer_name}
                                                                    <span className="ml-2">({formatCurrency(match.invoice_outstanding)})</span>
                                                                </div>
                                                                <div className="text-xs text-gray-500 mt-1">
                                                                    Match confidence: {Math.round(match.confidence * 100)}%
                                                                </div>
                                                            </div>
                                                            <Button
                                                                size="sm"
                                                                onClick={async () => {
                                                                    try {
                                                                        await axios.post(`/transactions/${match.transaction_id}/match-invoice`, {
                                                                            invoice_id: match.invoice_id
                                                                        });
                                                                        alert('Transaction matched to invoice successfully');
                                                                        setShowVerifyModal(false);
                                                                        router.reload();
                                                                    } catch (error) {
                                                                        alert('Failed to match transaction');
                                                                    }
                                                                }}
                                                            >
                                                                Apply Match
                                                            </Button>
                                                        </div>
                                                    </Card>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Payment Allocation Suggestions */}
                                    {verificationData.allocation_suggestions && verificationData.allocation_suggestions.length > 0 && (
                                        <div>
                                            <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                                <AlertTriangle className="h-5 w-5 text-yellow-500" />
                                                Payment Allocation Suggestions
                                            </h4>
                                            <div className="space-y-3">
                                                {verificationData.allocation_suggestions.map((suggestion, idx) => (
                                                    <Card key={idx} className="p-4 border-l-4 border-l-yellow-500">
                                                        <div className="flex items-start justify-between">
                                                            <div className="flex-1">
                                                                <div className="flex items-center gap-2 mb-2">
                                                                    <span className="font-medium text-gray-900">Payment: {suggestion.payment_reference || suggestion.payment_id}</span>
                                                                    <span className="text-sm text-gray-500">({formatCurrency(suggestion.payment_amount)})</span>
                                                                </div>
                                                                <div className="text-sm text-gray-600 mb-2">
                                                                    Unallocated: <span className="font-medium">{formatCurrency(suggestion.unallocated_amount)}</span>
                                                                </div>
                                                                <div className="text-sm">
                                                                    <span className="font-medium text-gray-700">Suggested allocations:</span>
                                                                    <ul className="list-disc list-inside mt-1 space-y-1">
                                                                        {suggestion.suggested_allocations.map((alloc, aIdx) => (
                                                                            <li key={aIdx} className="text-gray-600">
                                                                                Invoice #{alloc.invoice_number}: {formatCurrency(alloc.amount)}
                                                                                {alloc.outstanding < alloc.amount && (
                                                                                    <span className="text-red-500 ml-2">(Only {formatCurrency(alloc.outstanding)} outstanding)</span>
                                                                                )}
                                                                            </li>
                                                                        ))}
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => router.visit(`/payments/${suggestion.payment_id}/allocate`)}
                                                            >
                                                                Allocate
                                                            </Button>
                                                        </div>
                                                    </Card>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Discrepancies */}
                                    {verificationData.discrepancies && verificationData.discrepancies.length > 0 && (
                                        <div>
                                            <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                                <X className="h-5 w-5 text-red-500" />
                                                Discrepancies
                                            </h4>
                                            <div className="space-y-3">
                                                {verificationData.discrepancies.map((discrepancy, idx) => (
                                                    <Card key={idx} className="p-4 border-l-4 border-l-red-500">
                                                        <div className="flex items-start gap-3">
                                                            <AlertTriangle className="h-5 w-5 text-red-500 mt-0.5" />
                                                            <div className="flex-1">
                                                                <div className="font-medium text-gray-900 mb-1">{discrepancy.type}</div>
                                                                <div className="text-sm text-gray-600">{discrepancy.description}</div>
                                                                {discrepancy.transaction_id && (
                                                                    <Link
                                                                        href={`/transactions/${discrepancy.transaction_id}`}
                                                                        className="text-sm text-teal-500 hover:text-teal-600 mt-2 inline-block"
                                                                    >
                                                                        View Transaction →
                                                                    </Link>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </Card>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {(!verificationData.invoice_matches || verificationData.invoice_matches.length === 0) &&
                                     (!verificationData.allocation_suggestions || verificationData.allocation_suggestions.length === 0) &&
                                     (!verificationData.discrepancies || verificationData.discrepancies.length === 0) && (
                                        <div className="text-center py-12">
                                            <CheckCircle2 className="h-12 w-12 text-green-500 mx-auto mb-4" />
                                            <h4 className="text-lg font-semibold text-gray-900 mb-2">All Clear!</h4>
                                            <p className="text-gray-600">No issues found. All transactions are verified.</p>
                                        </div>
                                    )}
                                </div>
                            ) : null}
                        </div>
                    </div>
                )}

                {/* Central Upload Modal */}
                <UploadModal
                    isOpen={showUploadModal}
                    onClose={() => setShowUploadModal(false)}
                    onSuccess={(results) => {
                        console.log('Upload successful:', results);
                        router.reload();
                    }}
                    context="transactions"
                />
            </div>
        </SectionLayout>
    );
}

