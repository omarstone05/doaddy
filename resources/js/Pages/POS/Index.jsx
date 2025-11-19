import { useState, useEffect } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Search, Plus, Minus, Trash2, ShoppingCart, X, DollarSign, Receipt, User } from 'lucide-react';
import axios from 'axios';

export default function POSIndex({ session, products, cashAccount, teamMember }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [customerSearchQuery, setCustomerSearchQuery] = useState('');
    const [customerSearchResults, setCustomerSearchResults] = useState([]);
    const [selectedCustomer, setSelectedCustomer] = useState(null);
    const [showCustomerSearch, setShowCustomerSearch] = useState(false);
    const [cart, setCart] = useState([]);
    const [showPayment, setShowPayment] = useState(false);
    const [showExpense, setShowExpense] = useState(false);
    
    const { data, setData, post, processing } = useForm({
        items: [],
        payment_method: 'cash',
        payment_reference: '',
        customer_id: null,
        money_account_id: cashAccount?.id || '',
        register_session_id: session?.id || null,
    });

    const expenseForm = useForm({
        amount: '',
        description: '',
        category: '',
        transaction_date: new Date().toISOString().split('T')[0],
        from_account_id: cashAccount?.id || '',
    });

    useEffect(() => {
        if (cashAccount) {
            setData('money_account_id', cashAccount.id);
        }
    }, [cashAccount]);

    const searchProducts = async (query) => {
        if (query.length < 2) {
            setSearchResults([]);
            return;
        }
        
        try {
            const response = await axios.get(`/pos/products/search?q=${query}`);
            setSearchResults(response.data);
        } catch (error) {
            console.error('Search error:', error);
        }
    };

    const searchCustomers = async (query) => {
        if (query.length < 2) {
            setCustomerSearchResults([]);
            return;
        }
        
        try {
            const response = await axios.get(`/pos/customers/search?q=${query}`);
            setCustomerSearchResults(response.data);
        } catch (error) {
            console.error('Customer search error:', error);
        }
    };

    const selectCustomer = (customer) => {
        setSelectedCustomer(customer);
        setData('customer_id', customer.id);
        setCustomerSearchQuery('');
        setCustomerSearchResults([]);
        setShowCustomerSearch(false);
    };

    const addToCart = (product, quantity = 1) => {
        const existing = cart.find(item => item.id === product.id);
        
        // Ensure unit_price is always a number
        const unitPrice = parseFloat(product.selling_price) || 0;
        
        if (existing) {
            setCart(cart.map(item =>
                item.id === product.id
                    ? { ...item, quantity: item.quantity + quantity }
                    : item
            ));
        } else {
            setCart([...cart, { 
                ...product, 
                quantity,
                unit_price: unitPrice,
            }]);
        }
        
        setSearchQuery('');
        setSearchResults([]);
    };

    const updateQuantity = (productId, delta) => {
        setCart(cart.map(item => {
            if (item.id === productId) {
                const newQuantity = Math.max(0, item.quantity + delta);
                if (newQuantity === 0) {
                    return null;
                }
                return { ...item, quantity: newQuantity };
            }
            return item;
        }).filter(Boolean));
    };

    const removeFromCart = (productId) => {
        setCart(cart.filter(item => item.id !== productId));
    };

    const total = cart.reduce((sum, item) => 
        sum + ((parseFloat(item.unit_price) || 0) * item.quantity), 0
    );

    const handleCheckout = () => {
        const items = cart.map(item => ({
            goods_service_id: item.id,
            quantity: item.quantity,
        }));

        setData('items', items);
        post('/pos/sales', {
            onSuccess: () => {
                setCart([]);
                setShowPayment(false);
                // Receipt page will be rendered automatically by backend
            },
            preserveScroll: false,
        });
    };

    const handleQuickExpense = () => {
        expenseForm.post('/money/movements', {
            onSuccess: () => {
                setShowExpense(false);
                expenseForm.reset();
            },
        });
    };

    return (
        <SectionLayout sectionName="Sales">
            <Head title="Point of Sale" />
            <div className="min-h-screen bg-gray-50">
                {/* Top Bar */}
                <div className="bg-white border-b border-gray-200 px-6 py-4">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold text-gray-900">Point of Sale</h1>
                        <div className="flex items-center gap-4">
                            {session && (
                                <>
                                    <span className="text-sm text-gray-600">
                                        Session: {session.session_number}
                                    </span>
                                    <span className="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                                        Open
                                    </span>
                                </>
                            )}
                            <div className="relative">
                                <button
                                    onClick={() => setShowCustomerSearch(!showCustomerSearch)}
                                    className="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                                >
                                    <User className="h-4 w-4" />
                                    {selectedCustomer ? selectedCustomer.name : 'Select Customer'}
                                </button>
                                {showCustomerSearch && (
                                    <div className="absolute top-full mt-2 right-0 w-80 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                                        <div className="p-3 border-b border-gray-200">
                                            <input
                                                type="text"
                                                value={customerSearchQuery}
                                                onChange={(e) => {
                                                    setCustomerSearchQuery(e.target.value);
                                                    searchCustomers(e.target.value);
                                                }}
                                                placeholder="Search customers..."
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                                                autoFocus
                                            />
                                        </div>
                                        <div className="max-h-64 overflow-y-auto">
                                            {customerSearchResults.length === 0 && customerSearchQuery.length >= 2 ? (
                                                <div className="p-4 text-center text-gray-500 text-sm">
                                                    No customers found
                                                </div>
                                            ) : customerSearchResults.map((customer) => (
                                                <div
                                                    key={customer.id}
                                                    onClick={() => selectCustomer(customer)}
                                                    className="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0"
                                                >
                                                    <div className="font-medium text-gray-900">{customer.name}</div>
                                                    {customer.email && (
                                                        <div className="text-sm text-gray-500">{customer.email}</div>
                                                    )}
                                                    {customer.phone && (
                                                        <div className="text-sm text-gray-500">{customer.phone}</div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                        {selectedCustomer && (
                                            <div className="p-3 border-t border-gray-200">
                                                <button
                                                    onClick={() => {
                                                        setSelectedCustomer(null);
                                                        setData('customer_id', null);
                                                        setShowCustomerSearch(false);
                                                    }}
                                                    className="text-sm text-red-600 hover:text-red-700"
                                                >
                                                    Clear Customer
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
                
                {/* Main Content */}
                <div className="grid grid-cols-12 gap-6 p-6 h-[calc(100vh-120px)]">
                    {/* Left: Product Search */}
                    <div className="col-span-7">
                        <div className="bg-white border border-gray-200 rounded-lg p-6 h-full overflow-hidden flex flex-col">
                            <div className="relative mb-4">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                                <input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => {
                                        setSearchQuery(e.target.value);
                                        searchProducts(e.target.value);
                                    }}
                                    placeholder="Search products by name, SKU, or barcode..."
                                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-lg"
                                    autoFocus
                                />
                            </div>
                            
                            {searchResults.length > 0 && (
                                <div className="border border-gray-200 rounded-lg mb-4 max-h-64 overflow-y-auto">
                                    {searchResults.map((product) => (
                                        <div
                                            key={product.id}
                                            onClick={() => addToCart(product, 1)}
                                            className="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 flex items-center justify-between"
                                        >
                                            <div>
                                                <div className="font-medium text-gray-900">{product.name}</div>
                                                <div className="text-sm text-gray-500">
                                                    {product.sku && `SKU: ${product.sku} • `}
                                                    Stock: {product.current_stock} {product.unit || ''}
                                                </div>
                                            </div>
                                            <div className="text-lg font-semibold text-gray-900">
                                                K{(parseFloat(product.selling_price) || 0).toFixed(2)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Product Grid */}
                            <div className="flex-1 overflow-y-auto">
                                <div className="grid grid-cols-3 gap-4">
                                    {products.map((product) => (
                                        <div
                                            key={product.id}
                                            onClick={() => addToCart(product, 1)}
                                            className="bg-white border border-gray-200 rounded-lg p-4 hover:border-teal-500 hover:shadow-md cursor-pointer transition-all"
                                        >
                                            <div className="font-medium text-gray-900 mb-1">{product.name}</div>
                                            <div className="text-sm text-gray-500 mb-2">
                                                {product.current_stock} {product.unit || ''} in stock
                                            </div>
                                            <div className="text-lg font-bold text-gray-900">
                                                K{(parseFloat(product.selling_price) || 0).toFixed(2)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {/* Right: Cart */}
                    <div className="col-span-5">
                        <div className="bg-white border border-gray-200 rounded-lg p-6 h-full flex flex-col">
                            <div className="flex items-center gap-2 mb-4">
                                <ShoppingCart className="h-5 w-5 text-gray-600" />
                                <h2 className="text-xl font-semibold text-gray-900">Cart</h2>
                                {cart.length > 0 && (
                                    <span className="px-2 py-1 bg-teal-100 text-teal-700 rounded-full text-sm font-medium">
                                        {cart.length} {cart.length === 1 ? 'item' : 'items'}
                                    </span>
                                )}
                            </div>

                            {cart.length === 0 ? (
                                <div className="flex-1 flex items-center justify-center">
                                    <div className="text-center">
                                        <ShoppingCart className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                                        <p className="text-gray-500">Cart is empty</p>
                                        <p className="text-sm text-gray-400 mt-1">Add products to start</p>
                                    </div>
                                </div>
                            ) : (
                                <>
                                    <div className="flex-1 overflow-y-auto mb-4">
                                        <div className="space-y-2">
                                            {cart.map((item) => (
                                                <div key={item.id} className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                                    <div className="flex-1">
                                                        <div className="font-medium text-gray-900">{item.name}</div>
                                                        <div className="text-sm text-gray-500">
                                                            K{(parseFloat(item.unit_price) || 0).toFixed(2)} × {item.quantity}
                                                        </div>
                                                    </div>
                                                    <div className="text-right">
                                                        <div className="font-semibold text-gray-900">
                                                            K{((parseFloat(item.unit_price) || 0) * item.quantity).toFixed(2)}
                                                        </div>
                                                        <div className="flex items-center gap-1 mt-1">
                                                            <button
                                                                onClick={() => updateQuantity(item.id, -1)}
                                                                className="p-1 text-gray-400 hover:text-gray-600"
                                                            >
                                                                <Minus className="h-4 w-4" />
                                                            </button>
                                                            <span className="text-sm text-gray-600 w-8 text-center">{item.quantity}</span>
                                                            <button
                                                                onClick={() => updateQuantity(item.id, 1)}
                                                                className="p-1 text-gray-400 hover:text-gray-600"
                                                            >
                                                                <Plus className="h-4 w-4" />
                                                            </button>
                                                            <button
                                                                onClick={() => removeFromCart(item.id)}
                                                                className="p-1 text-red-400 hover:text-red-600 ml-2"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="border-t border-gray-200 pt-4">
                                        <div className="flex justify-between items-center mb-4">
                                            <span className="text-lg font-semibold text-gray-900">Total</span>
                                            <span className="text-3xl font-bold text-gray-900">
                                                K{total.toFixed(2)}
                                            </span>
                                        </div>
                                        <Button
                                            onClick={() => setShowPayment(true)}
                                            className="w-full h-14 text-lg"
                                            disabled={!cashAccount || processing}
                                        >
                                            <DollarSign className="h-5 w-5 mr-2" />
                                            Checkout
                                        </Button>
                                        <Button
                                            variant="secondary"
                                            onClick={() => setShowExpense(true)}
                                            className="w-full mt-2"
                                        >
                                            <Receipt className="h-4 w-4 mr-2" />
                                            Quick Expense
                                        </Button>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                </div>
                
                {/* Quick Expense Modal */}
                {showExpense && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-xl font-bold text-gray-900">Quick Expense Entry</h3>
                                <button
                                    onClick={() => setShowExpense(false)}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </div>
                            
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Amount *
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={expenseForm.data.amount}
                                        onChange={(e) => expenseForm.setData('amount', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                        placeholder="0.00"
                                        required
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Description *
                                    </label>
                                    <input
                                        type="text"
                                        value={expenseForm.data.description}
                                        onChange={(e) => expenseForm.setData('description', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                        placeholder="e.g., Office supplies"
                                        required
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Category
                                    </label>
                                    <input
                                        type="text"
                                        value={expenseForm.data.category}
                                        onChange={(e) => expenseForm.setData('category', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                        placeholder="e.g., Supplies"
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Date
                                    </label>
                                    <input
                                        type="date"
                                        value={expenseForm.data.transaction_date}
                                        onChange={(e) => expenseForm.setData('transaction_date', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>
                            </div>
                            
                            <div className="flex gap-3 mt-6">
                                <Button
                                    variant="secondary"
                                    onClick={() => setShowExpense(false)}
                                    className="flex-1"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    onClick={handleQuickExpense}
                                    disabled={expenseForm.processing}
                                    className="flex-1"
                                >
                                    Record Expense
                                </Button>
                            </div>
                        </div>
                    </div>
                )}
                
                {/* Payment Modal */}
                {showPayment && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-xl font-bold text-gray-900">Complete Payment</h3>
                                <button
                                    onClick={() => setShowPayment(false)}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </div>
                            
                            <div className="mb-6">
                                <div className="text-center mb-4">
                                    <div className="text-4xl font-bold text-gray-900 mb-2">
                                        K{total.toFixed(2)}
                                    </div>
                                    <div className="text-sm text-gray-500">{cart.length} items</div>
                                </div>
                                
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Payment Method
                                    </label>
                                    <select
                                        value={data.payment_method}
                                        onChange={(e) => setData('payment_method', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                    >
                                        <option value="cash">Cash</option>
                                        <option value="mobile_money">Mobile Money</option>
                                        <option value="card">Card</option>
                                        <option value="credit">Credit</option>
                                    </select>
                                </div>
                                
                                {data.payment_method === 'credit' && !selectedCustomer && (
                                    <div className="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <p className="text-sm text-yellow-800">
                                            Please select a customer for credit sales
                                        </p>
                                    </div>
                                )}
                                {data.payment_method !== 'cash' && data.payment_method !== 'credit' && (
                                    <div className="mb-4">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Reference Number
                                        </label>
                                        <input
                                            type="text"
                                            value={data.payment_reference}
                                            onChange={(e) => setData('payment_reference', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                            placeholder="Transaction reference"
                                        />
                                    </div>
                                )}
                            </div>
                            
                            <div className="flex gap-3">
                                <Button
                                    variant="secondary"
                                    onClick={() => setShowPayment(false)}
                                    className="flex-1"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    onClick={handleCheckout}
                                    disabled={processing || (data.payment_method === 'credit' && !selectedCustomer)}
                                    className="flex-1"
                                >
                                    Complete Sale
                                </Button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </SectionLayout>
    );
}

