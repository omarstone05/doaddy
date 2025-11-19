import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import QuickCreateCustomerModal from '@/Components/QuickCreateCustomerModal';
import QuickCreateProductModal from '@/Components/QuickCreateProductModal';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';

export default function QuotesCreate({ customers: initialCustomers, products: initialProducts }) {
    const [customers, setCustomers] = useState(initialCustomers);
    const [products, setProducts] = useState(initialProducts);
    const [showCustomerModal, setShowCustomerModal] = useState(false);
    const [showProductModal, setShowProductModal] = useState(false);
    const [currentProductModalIndex, setCurrentProductModalIndex] = useState(null);
    const [items, setItems] = useState([{ name: '', description: '', quantity: 1, unit_price: 0, goods_service_id: '' }]);

    const { data, setData, post, processing, errors } = useForm({
        customer_id: '',
        quote_date: new Date().toISOString().split('T')[0],
        expiry_date: '',
        items: [],
        tax_amount: '0',
        discount_amount: '0',
        notes: '',
        terms: '',
    });

    const addItem = () => {
        setItems([...items, { name: '', description: '', quantity: 1, unit_price: 0, goods_service_id: '' }]);
    };

    const removeItem = (index) => {
        setItems(items.filter((_, i) => i !== index));
    };

    const updateItem = (index, field, value) => {
        const newItems = [...items];
        newItems[index][field] = value;
        
        // If product selected, auto-fill name, description and price
        if (field === 'goods_service_id' && value) {
            const product = products.find(p => p.id === value);
            if (product) {
                newItems[index].name = product.name;
                newItems[index].description = product.description || '';
                newItems[index].unit_price = parseFloat(product.selling_price) || 0;
            }
        }
        
        setItems(newItems);
    };

    const calculateTotal = () => {
        const subtotal = items.reduce((sum, item) => 
            sum + (parseFloat(item.quantity) * parseFloat(item.unit_price)), 0
        );
        const tax = parseFloat(data.tax_amount) || 0;
        const discount = parseFloat(data.discount_amount) || 0;
        return subtotal + tax - discount;
    };

    const handleCustomerCreated = (newCustomer) => {
        setCustomers([...customers, newCustomer]);
        setData('customer_id', newCustomer.id);
    };

    const handleProductCreated = (newProduct) => {
        setProducts([...products, newProduct]);
        if (currentProductModalIndex !== null) {
            updateItem(currentProductModalIndex, 'goods_service_id', newProduct.id);
        }
        setCurrentProductModalIndex(null);
    };

    const submit = (e) => {
        e.preventDefault();
        
        // Validate items
        const validItems = items.filter(item => item.name && item.quantity > 0);
        if (validItems.length === 0) {
            alert('Please add at least one item');
            return;
        }

        setData('items', validItems.map(item => ({
            name: item.name,
            description: item.description || null,
            quantity: parseFloat(item.quantity),
            unit_price: parseFloat(item.unit_price),
            goods_service_id: item.goods_service_id || null,
        })));

        post('/quotes');
    };

    return (
        <AuthenticatedLayout>
            <Head title="Create Quote" />
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
                    <h1 className="text-3xl font-bold text-gray-900">Create Quote</h1>
                    <p className="text-gray-500 mt-1">Create a new quote for your customer</p>
                </div>

                <form onSubmit={submit} className="bg-white border border-gray-200 rounded-lg p-6">
                    <div className="space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <div className="flex items-center justify-between mb-2">
                                    <label htmlFor="customer_id" className="block text-sm font-medium text-gray-700">
                                        Customer *
                                    </label>
                                    <button
                                        type="button"
                                        onClick={() => setShowCustomerModal(true)}
                                        className="text-xs text-teal-600 hover:text-teal-700 font-medium"
                                    >
                                        + Add New
                                    </button>
                                </div>
                                <select
                                    id="customer_id"
                                    value={data.customer_id}
                                    onChange={(e) => setData('customer_id', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                >
                                    <option value="">Select customer</option>
                                    {customers.map((customer) => (
                                        <option key={customer.id} value={customer.id}>
                                            {customer.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.customer_id && <p className="mt-1 text-sm text-red-600">{errors.customer_id}</p>}
                            </div>

                            <div>
                                <label htmlFor="quote_date" className="block text-sm font-medium text-gray-700 mb-2">
                                    Quote Date *
                                </label>
                                <input
                                    id="quote_date"
                                    type="date"
                                    value={data.quote_date}
                                    onChange={(e) => setData('quote_date', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label htmlFor="expiry_date" className="block text-sm font-medium text-gray-700 mb-2">
                                Expiry Date
                            </label>
                            <input
                                id="expiry_date"
                                type="date"
                                value={data.expiry_date}
                                onChange={(e) => setData('expiry_date', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>

                        {/* Items */}
                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <label className="block text-sm font-medium text-gray-700">
                                    Items *
                                </label>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setCurrentProductModalIndex(null);
                                            setShowProductModal(true);
                                        }}
                                        className="px-3 py-1.5 text-xs text-teal-600 hover:text-teal-700 hover:bg-teal-50 rounded-lg border border-teal-200 whitespace-nowrap"
                                        title="Add new product"
                                    >
                                        + New Product
                                    </button>
                                    <Button type="button" variant="secondary" size="sm" onClick={addItem}>
                                        <Plus className="h-4 w-4 mr-2" />
                                        Add Item
                                    </Button>
                                </div>
                            </div>

                            <div className="space-y-3">
                                {items.map((item, index) => (
                                    <div key={index} className="grid grid-cols-12 gap-2 items-start p-3 bg-gray-50 rounded-lg">
                                        <div className="col-span-2">
                                            <select
                                                value={item.goods_service_id}
                                                onChange={(e) => updateItem(index, 'goods_service_id', e.target.value)}
                                                className="w-full px-2 py-2 text-xs border border-gray-300 rounded-lg"
                                                title="Select product to auto-fill"
                                            >
                                                <option value="">Product</option>
                                                {products.map((product) => (
                                                    <option key={product.id} value={product.id}>
                                                        {product.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="col-span-4">
                                            <input
                                                type="text"
                                                value={item.name}
                                                onChange={(e) => updateItem(index, 'name', e.target.value)}
                                                placeholder="Product/Service Name *"
                                                className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg font-medium"
                                                required
                                            />
                                        </div>
                                        <div className="col-span-3">
                                            <input
                                                type="text"
                                                value={item.description}
                                                onChange={(e) => updateItem(index, 'description', e.target.value)}
                                                placeholder="Description (optional)"
                                                className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg"
                                            />
                                        </div>
                                        <div className="col-span-1">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={item.quantity}
                                                onChange={(e) => updateItem(index, 'quantity', e.target.value)}
                                                placeholder="Qty"
                                                className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg"
                                                required
                                            />
                                        </div>
                                        <div className="col-span-2">
                                            <div className="flex gap-1">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={item.unit_price}
                                                    onChange={(e) => updateItem(index, 'unit_price', e.target.value)}
                                                    placeholder="Price"
                                                    className="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg"
                                                    required
                                                />
                                                {items.length > 1 && (
                                                    <button
                                                        type="button"
                                                        onClick={() => removeItem(index)}
                                                        className="p-2 text-red-500 hover:text-red-700"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="tax_amount" className="block text-sm font-medium text-gray-700 mb-2">
                                    Tax Amount
                                </label>
                                <input
                                    id="tax_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.tax_amount}
                                    onChange={(e) => setData('tax_amount', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>

                            <div>
                                <label htmlFor="discount_amount" className="block text-sm font-medium text-gray-700 mb-2">
                                    Discount Amount
                                </label>
                                <input
                                    id="discount_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.discount_amount}
                                    onChange={(e) => setData('discount_amount', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                        </div>

                        <div>
                            <label htmlFor="notes" className="block text-sm font-medium text-gray-700 mb-2">
                                Notes
                            </label>
                            <textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>

                        <div>
                            <label htmlFor="terms" className="block text-sm font-medium text-gray-700 mb-2">
                                Terms & Conditions
                            </label>
                            <textarea
                                id="terms"
                                value={data.terms}
                                onChange={(e) => setData('terms', e.target.value)}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>

                        {/* Total */}
                        <div className="border-t border-gray-200 pt-4">
                            <div className="flex justify-between items-center">
                                <span className="text-lg font-semibold text-gray-900">Total</span>
                                <span className="text-2xl font-bold text-gray-900">
                                    K{calculateTotal().toFixed(2)}
                                </span>
                            </div>
                        </div>

                        <div className="flex gap-4 pt-4">
                            <Button type="submit" disabled={processing} className="flex-1">
                                Create Quote
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => window.history.back()}
                            >
                                Cancel
                            </Button>
                        </div>
                    </div>
                </form>

                <QuickCreateCustomerModal
                    isOpen={showCustomerModal}
                    onClose={() => setShowCustomerModal(false)}
                    onSuccess={handleCustomerCreated}
                />

                <QuickCreateProductModal
                    isOpen={showProductModal}
                    onClose={() => {
                        setShowProductModal(false);
                        setCurrentProductModalIndex(null);
                    }}
                    onSuccess={handleProductCreated}
                />
            </div>
        </AuthenticatedLayout>
    );
}

