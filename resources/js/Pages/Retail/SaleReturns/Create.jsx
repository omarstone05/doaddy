import { useState, useEffect } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft, Plus, Minus, Search } from 'lucide-react';
import axios from 'axios';

export default function SaleReturnsCreate({ sale: initialSale }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [sale, setSale] = useState(initialSale);
    const [searchResults, setSearchResults] = useState([]);
    const [selectedItems, setSelectedItems] = useState({});

    const { data, setData, post, processing, errors } = useForm({
        sale_id: initialSale?.id || '',
        return_date: new Date().toISOString().split('T')[0],
        items: [],
        return_reason: '',
        refund_method: 'cash',
        refund_reference: '',
    });

    useEffect(() => {
        if (initialSale) {
            setSale(initialSale);
            setData('sale_id', initialSale.id);
        }
    }, [initialSale]);

    const searchSales = async (query) => {
        if (query.length < 2) {
            setSearchResults([]);
            return;
        }
        
        try {
            const response = await axios.get(`/sales/search?q=${query}`);
            setSearchResults(response.data);
        } catch (error) {
            console.error('Search error:', error);
        }
    };

    const selectSale = (selectedSale) => {
        setSale(selectedSale);
        setData('sale_id', selectedSale.id);
        setSearchQuery('');
        setSearchResults([]);
        setSelectedItems({});
    };

    const toggleItem = (saleItem) => {
        const itemId = saleItem.id;
        if (selectedItems[itemId]) {
            const newItems = { ...selectedItems };
            delete newItems[itemId];
            setSelectedItems(newItems);
        } else {
            setSelectedItems({
                ...selectedItems,
                [itemId]: {
                    sale_item_id: itemId,
                    quantity_returned: saleItem.quantity,
                },
            });
        }
    };

    const updateReturnQuantity = (saleItemId, quantity) => {
        const saleItem = sale.items.find(item => item.id === saleItemId);
        if (!saleItem) return;

        const maxQuantity = saleItem.quantity;
        const returnQuantity = Math.max(0, Math.min(quantity, maxQuantity));

        setSelectedItems({
            ...selectedItems,
            [saleItemId]: {
                sale_item_id: saleItemId,
                quantity_returned: returnQuantity,
            },
        });
    };

    const calculateTotal = () => {
        return Object.values(selectedItems).reduce((sum, item) => {
            const saleItem = sale?.items.find(si => si.id === item.sale_item_id);
            if (!saleItem) return sum;
            const itemTotal = saleItem.total * (item.quantity_returned / saleItem.quantity);
            return sum + itemTotal;
        }, 0);
    };

    const submit = (e) => {
        e.preventDefault();
        
        if (!sale) {
            alert('Please select a sale');
            return;
        }

        const items = Object.values(selectedItems).filter(item => item.quantity_returned > 0);
        if (items.length === 0) {
            alert('Please select at least one item to return');
            return;
        }

        setData('items', items);
        post('/sale-returns');
    };

    return (
        <AuthenticatedLayout>
            <Head title="Process Return" />
            <div className="max-w-4xl mx-auto ">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => window.history.back()}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <h1 className="text-3xl font-bold text-gray-900">Process Sale Return</h1>
                    <p className="text-gray-500 mt-1">Return items from a sale</p>
                </div>

                <form onSubmit={submit} className="bg-white border border-gray-200 rounded-lg p-6">
                    <div className="space-y-6">
                        {!sale ? (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Search Sale
                                </label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        type="text"
                                        value={searchQuery}
                                        onChange={(e) => {
                                            setSearchQuery(e.target.value);
                                            searchSales(e.target.value);
                                        }}
                                        placeholder="Search by sale number..."
                                        className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>
                                {searchResults.length > 0 && (
                                    <div className="mt-2 border border-gray-200 rounded-lg max-h-64 overflow-y-auto">
                                        {searchResults.map((result) => (
                                            <div
                                                key={result.id}
                                                onClick={() => selectSale(result)}
                                                className="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0"
                                            >
                                                <div className="font-medium text-gray-900">{result.sale_number}</div>
                                                <div className="text-sm text-gray-500">
                                                    {new Date(result.sale_date).toLocaleDateString()} - K{result.total_amount.toFixed(2)}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        ) : (
                            <>
                                <div className="p-4 bg-gray-50 rounded-lg">
                                    <div className="flex items-center justify-between mb-2">
                                        <div>
                                            <h3 className="font-semibold text-gray-900">Sale {sale.sale_number}</h3>
                                            <p className="text-sm text-gray-600">
                                                {sale.customer?.name} • {new Date(sale.sale_date).toLocaleDateString()}
                                            </p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            onClick={() => {
                                                setSale(null);
                                                setData('sale_id', '');
                                                setSelectedItems({});
                                            }}
                                        >
                                            Change Sale
                                        </Button>
                                    </div>
                                </div>

                                {/* Items */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-4">
                                        Select Items to Return
                                    </label>
                                    <div className="space-y-3">
                                        {sale.items?.map((item) => {
                                            const isSelected = !!selectedItems[item.id];
                                            const returnQuantity = selectedItems[item.id]?.quantity_returned || 0;
                                            const refundAmount = item.total * (returnQuantity / item.quantity);

                                            return (
                                                <div
                                                    key={item.id}
                                                    className={`p-4 border rounded-lg ${
                                                        isSelected ? 'border-teal-500 bg-teal-50' : 'border-gray-200'
                                                    }`}
                                                >
                                                    <div className="flex items-center justify-between mb-2">
                                                        <div className="flex items-center gap-3">
                                                            <input
                                                                type="checkbox"
                                                                checked={isSelected}
                                                                onChange={() => toggleItem(item)}
                                                                className="rounded border-gray-300 text-teal-500"
                                                            />
                                                            <div>
                                                                <div className="font-medium text-gray-900">{item.product_name}</div>
                                                                <div className="text-sm text-gray-500">
                                                                    Sold: {item.quantity} × K{item.unit_price.toFixed(2)} = K{item.total.toFixed(2)}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    {isSelected && (
                                                        <div className="mt-3 flex items-center gap-3">
                                                            <div className="flex-1">
                                                                <label className="block text-xs font-medium text-gray-700 mb-1">
                                                                    Quantity to Return
                                                                </label>
                                                                <div className="flex items-center gap-2">
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => updateReturnQuantity(item.id, returnQuantity - 1)}
                                                                        className="p-1 text-gray-400 hover:text-gray-600"
                                                                    >
                                                                        <Minus className="h-4 w-4" />
                                                                    </button>
                                                                    <input
                                                                        type="number"
                                                                        step="0.01"
                                                                        min="0"
                                                                        max={item.quantity}
                                                                        value={returnQuantity}
                                                                        onChange={(e) => updateReturnQuantity(item.id, parseFloat(e.target.value))}
                                                                        className="w-24 px-2 py-1 text-sm border border-gray-300 rounded-lg text-center"
                                                                    />
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => updateReturnQuantity(item.id, returnQuantity + 1)}
                                                                        className="p-1 text-gray-400 hover:text-gray-600"
                                                                    >
                                                                        <Plus className="h-4 w-4" />
                                                                    </button>
                                                                    <span className="text-sm text-gray-500">
                                                                        / {item.quantity}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div className="text-right">
                                                                <div className="text-sm text-gray-500">Refund Amount</div>
                                                                <div className="text-lg font-bold text-gray-900">
                                                                    K{refundAmount.toFixed(2)}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label htmlFor="return_date" className="block text-sm font-medium text-gray-700 mb-2">
                                            Return Date *
                                        </label>
                                        <input
                                            id="return_date"
                                            type="date"
                                            value={data.return_date}
                                            onChange={(e) => setData('return_date', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label htmlFor="refund_method" className="block text-sm font-medium text-gray-700 mb-2">
                                            Refund Method *
                                        </label>
                                        <select
                                            id="refund_method"
                                            value={data.refund_method}
                                            onChange={(e) => setData('refund_method', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                            required
                                        >
                                            <option value="cash">Cash</option>
                                            <option value="mobile_money">Mobile Money</option>
                                            <option value="card">Card</option>
                                            <option value="credit_note">Credit Note</option>
                                        </select>
                                    </div>
                                </div>

                                {data.refund_method !== 'credit_note' && (
                                    <div>
                                        <label htmlFor="refund_reference" className="block text-sm font-medium text-gray-700 mb-2">
                                            Reference Number
                                        </label>
                                        <input
                                            id="refund_reference"
                                            type="text"
                                            value={data.refund_reference}
                                            onChange={(e) => setData('refund_reference', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                            placeholder="Transaction reference"
                                        />
                                    </div>
                                )}

                                <div>
                                    <label htmlFor="return_reason" className="block text-sm font-medium text-gray-700 mb-2">
                                        Return Reason
                                    </label>
                                    <textarea
                                        id="return_reason"
                                        value={data.return_reason}
                                        onChange={(e) => setData('return_reason', e.target.value)}
                                        rows={3}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                        placeholder="Reason for return..."
                                    />
                                </div>

                                {/* Total */}
                                <div className="border-t border-gray-200 pt-4">
                                    <div className="flex justify-between items-center">
                                        <span className="text-lg font-semibold text-gray-900">Total Refund</span>
                                        <span className="text-2xl font-bold text-gray-900">
                                            K{calculateTotal().toFixed(2)}
                                        </span>
                                    </div>
                                </div>

                                <div className="flex gap-4 pt-4">
                                    <Button type="submit" disabled={processing} className="flex-1">
                                        Process Return
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={() => window.history.back()}
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </>
                        )}
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}

