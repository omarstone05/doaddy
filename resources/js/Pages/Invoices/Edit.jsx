import { useState, useEffect } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/Button';
import QuickCreateCustomerModal from '@/Components/QuickCreateCustomerModal';
import QuickCreateProductModal from '@/Components/QuickCreateProductModal';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';

export default function InvoicesEdit({ invoice, customers: initialCustomers, products: initialProducts, bankDetails }) {
    const [customers, setCustomers] = useState(initialCustomers);
    const [products, setProducts] = useState(initialProducts);
    const [showCustomerModal, setShowCustomerModal] = useState(false);
    const [showProductModal, setShowProductModal] = useState(false);
    const [currentProductModalIndex, setCurrentProductModalIndex] = useState(null);
    const [items, setItems] = useState(
        invoice?.items?.map(item => ({
            name: item.name || item.description || '',
            description: item.description && item.description !== item.name ? item.description : '',
            quantity: item.quantity,
            unit_price: item.unit_price,
            goods_service_id: item.goods_service_id,
        })) || [{ name: '', description: '', quantity: 1, unit_price: 0, goods_service_id: '' }]
    );

    const { data, setData, put, processing, errors } = useForm({
        customer_id: invoice?.customer_id || '',
        invoice_date: invoice?.invoice_date || new Date().toISOString().split('T')[0],
        due_date: invoice?.due_date || '',
        items: [],
        tax_amount: invoice?.tax_amount?.toString() || '0',
        discount_amount: invoice?.discount_amount?.toString() || '0',
        notes: invoice?.notes || '',
        terms: invoice?.terms || '',
        payment_details: invoice?.payment_details || {
            show_bank_name: false,
            show_account_name: false,
            show_account_number: false,
            show_branch: false,
            show_swift_code: false,
            show_mobile_money: false,
            show_payment_options: false,
            show_notes: false,
        },
        is_recurring: invoice?.is_recurring || false,
        recurrence_frequency: invoice?.recurrence_frequency || '',
        recurrence_day: invoice?.recurrence_day?.toString() || '',
        recurrence_end_date: invoice?.recurrence_end_date || '',
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
        
        const validItems = items.filter(item => item.name && item.quantity > 0);
        if (validItems.length === 0) {
            alert('Please add at least one item');
            return;
        }

        const formattedItems = validItems.map(item => ({
            name: item.name,
            description: item.description || null,
            quantity: parseFloat(item.quantity),
            unit_price: parseFloat(item.unit_price),
            goods_service_id: item.goods_service_id || null,
        }));

        // Ensure due_date is set if empty
        let dueDate = data.due_date;
        if (!dueDate && data.invoice_date) {
            const invoiceDate = new Date(data.invoice_date);
            invoiceDate.setDate(invoiceDate.getDate() + 30);
            dueDate = invoiceDate.toISOString().split('T')[0];
        }

        // Prepare the complete form data
        const formData = {
            ...data,
            items: formattedItems,
            due_date: dueDate || data.due_date,
        };

        // Use router.put to update
        router.put(`/invoices/${invoice.id}`, formData, {
            preserveScroll: true,
            onError: (errors) => {
                console.error('Invoice update errors:', errors);
            },
        });
    };

    // Check if invoice can be edited - must not be paid status and have no payments
    // Handle paid_amount which might be string "0.00", null, undefined, or number
    const paidAmountValue = invoice?.paid_amount;
    const paidAmount = paidAmountValue ? parseFloat(paidAmountValue) : 0;
    // Check if payments array exists and has items
    const hasPayments = invoice?.payments && Array.isArray(invoice.payments) && invoice.payments.length > 0;
    // Use <= 0.01 to account for floating point precision issues
    // Debug: Log values to console for troubleshooting
    if (process.env.NODE_ENV === 'development') {
        console.log('Invoice Edit Check:', {
            status: invoice?.status,
            paidAmountValue,
            paidAmount,
            hasPayments,
            paymentsLength: invoice?.payments?.length,
            canEdit: invoice?.status !== 'paid' && !hasPayments && paidAmount <= 0.01
        });
    }
    const canEdit = invoice?.status !== 'paid' && !hasPayments && paidAmount <= 0.01;

    return (
        <AuthenticatedLayout>
            <Head title={`Edit Invoice - ${invoice?.invoice_number}`} />
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
                    <h1 className="text-3xl font-bold text-gray-900">Edit Invoice</h1>
                    <p className="text-gray-500 mt-1">Invoice #{invoice?.invoice_number}</p>
                </div>

                {!canEdit && (
                    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <p className="text-yellow-800">
                            This invoice cannot be edited because it has been paid or has payments.
                        </p>
                    </div>
                )}

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
                                        disabled={!canEdit}
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
                                    disabled={!canEdit}
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
                                <label htmlFor="invoice_date" className="block text-sm font-medium text-gray-700 mb-2">
                                    Invoice Date *
                                </label>
                                <input
                                    id="invoice_date"
                                    type="date"
                                    value={data.invoice_date}
                                    onChange={(e) => setData('invoice_date', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                    disabled={!canEdit}
                                />
                            </div>
                        </div>

                        <div>
                            <label htmlFor="due_date" className="block text-sm font-medium text-gray-700 mb-2">
                                Due Date
                            </label>
                            <input
                                id="due_date"
                                type="date"
                                value={data.due_date}
                                onChange={(e) => setData('due_date', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                disabled={!canEdit}
                            />
                        </div>

                        {/* Items */}
                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <label className="block text-sm font-medium text-gray-700">
                                    Items *
                                </label>
                                {canEdit && (
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
                                )}
                            </div>

                            <div className="space-y-3">
                                {items.map((item, index) => (
                                    <div key={index} className="grid grid-cols-12 gap-2 items-start p-3 bg-gray-50 rounded-lg">
                                        <div className="col-span-3">
                                            <select
                                                value={item.goods_service_id}
                                                onChange={(e) => updateItem(index, 'goods_service_id', e.target.value)}
                                                className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg"
                                                disabled={!canEdit}
                                            >
                                                <option value="">Select Product</option>
                                                {products.map((product) => (
                                                    <option key={product.id} value={product.id}>
                                                        {product.name} - K{(parseFloat(product.selling_price) || 0).toFixed(2)}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="col-span-3">
                                            <input
                                                type="text"
                                                value={item.name}
                                                onChange={(e) => updateItem(index, 'name', e.target.value)}
                                                placeholder="Product/Service Name *"
                                                className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg"
                                                required
                                                disabled={!canEdit}
                                            />
                                        </div>
                                        <div className="col-span-3">
                                            <input
                                                type="text"
                                                value={item.description}
                                                onChange={(e) => updateItem(index, 'description', e.target.value)}
                                                placeholder="Description (optional)"
                                                className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg"
                                                disabled={!canEdit}
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
                                                disabled={!canEdit}
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
                                                    disabled={!canEdit}
                                                />
                                                {canEdit && items.length > 1 && (
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
                                    disabled={!canEdit}
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
                                    disabled={!canEdit}
                                />
                            </div>
                        </div>

                        {/* Recurring Invoice */}
                        <div className="border-t border-gray-200 pt-4">
                            <label className="flex items-center gap-2 mb-4">
                                <input
                                    type="checkbox"
                                    checked={data.is_recurring}
                                    onChange={(e) => setData('is_recurring', e.target.checked)}
                                    className="rounded border-gray-300 text-teal-500 focus:ring-teal-500"
                                    disabled={!canEdit}
                                />
                                <span className="text-sm font-medium text-gray-700">Recurring Invoice</span>
                            </label>
                            
                            {data.is_recurring && (
                                <div className="grid grid-cols-3 gap-4 ml-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Frequency
                                        </label>
                                        <select
                                            value={data.recurrence_frequency}
                                            onChange={(e) => setData('recurrence_frequency', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                            disabled={!canEdit}
                                        >
                                            <option value="">Select</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly">Monthly</option>
                                            <option value="quarterly">Quarterly</option>
                                            <option value="annually">Annually</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Day of Month
                                        </label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="31"
                                            value={data.recurrence_day}
                                            onChange={(e) => setData('recurrence_day', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                            disabled={!canEdit}
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            End Date
                                        </label>
                                        <input
                                            type="date"
                                            value={data.recurrence_end_date}
                                            onChange={(e) => setData('recurrence_end_date', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                            disabled={!canEdit}
                                        />
                                    </div>
                                </div>
                            )}
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
                                disabled={!canEdit}
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
                                disabled={!canEdit}
                            />
                        </div>

                        {/* Payment Details Selection */}
                        {bankDetails && (
                            <div className="border-t border-gray-200 pt-4">
                                <h3 className="text-sm font-semibold text-gray-900 mb-3">Payment Information on Invoice</h3>
                                <p className="text-xs text-gray-500 mb-4">Select which payment details to display on the invoice footer</p>
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    {bankDetails.bank_name && (
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={data.payment_details?.show_bank_name || false}
                                                onChange={(e) => setData('payment_details', {
                                                    ...data.payment_details,
                                                    show_bank_name: e.target.checked
                                                })}
                                                disabled={!canEdit}
                                                className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                            />
                                            <span className="text-sm text-gray-700">Bank Name</span>
                                        </label>
                                    )}
                                    {bankDetails.account_name && (
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={data.payment_details?.show_account_name || false}
                                                onChange={(e) => setData('payment_details', {
                                                    ...data.payment_details,
                                                    show_account_name: e.target.checked
                                                })}
                                                disabled={!canEdit}
                                                className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                            />
                                            <span className="text-sm text-gray-700">Account Name</span>
                                        </label>
                                    )}
                                    {bankDetails.account_number && (
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={data.payment_details?.show_account_number || false}
                                                onChange={(e) => setData('payment_details', {
                                                    ...data.payment_details,
                                                    show_account_number: e.target.checked
                                                })}
                                                disabled={!canEdit}
                                                className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                            />
                                            <span className="text-sm text-gray-700">Account Number</span>
                                        </label>
                                    )}
                                    {bankDetails.branch && (
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={data.payment_details?.show_branch || false}
                                                onChange={(e) => setData('payment_details', {
                                                    ...data.payment_details,
                                                    show_branch: e.target.checked
                                                })}
                                                disabled={!canEdit}
                                                className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                            />
                                            <span className="text-sm text-gray-700">Branch</span>
                                        </label>
                                    )}
                                    {bankDetails.swift_code && (
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={data.payment_details?.show_swift_code || false}
                                                onChange={(e) => setData('payment_details', {
                                                    ...data.payment_details,
                                                    show_swift_code: e.target.checked
                                                })}
                                                disabled={!canEdit}
                                                className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                            />
                                            <span className="text-sm text-gray-700">SWIFT Code</span>
                                        </label>
                                    )}
                                    {bankDetails.mobile_money && (
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={data.payment_details?.show_mobile_money || false}
                                                onChange={(e) => setData('payment_details', {
                                                    ...data.payment_details,
                                                    show_mobile_money: e.target.checked
                                                })}
                                                disabled={!canEdit}
                                                className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                            />
                                            <span className="text-sm text-gray-700">Mobile Money</span>
                                        </label>
                                    )}
                                    {bankDetails.payment_options && (
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={data.payment_details?.show_payment_options || false}
                                                onChange={(e) => setData('payment_details', {
                                                    ...data.payment_details,
                                                    show_payment_options: e.target.checked
                                                })}
                                                disabled={!canEdit}
                                                className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                            />
                                            <span className="text-sm text-gray-700">Payment Options</span>
                                        </label>
                                    )}
                                    {bankDetails.notes && (
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={data.payment_details?.show_notes || false}
                                                onChange={(e) => setData('payment_details', {
                                                    ...data.payment_details,
                                                    show_notes: e.target.checked
                                                })}
                                                disabled={!canEdit}
                                                className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                            />
                                            <span className="text-sm text-gray-700">Payment Notes</span>
                                        </label>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Total */}
                        <div className="border-t border-gray-200 pt-4">
                            <div className="flex justify-between items-center">
                                <span className="text-lg font-semibold text-gray-900">Total</span>
                                <span className="text-2xl font-bold text-gray-900">
                                    K{calculateTotal().toFixed(2)}
                                </span>
                            </div>
                        </div>

                        {canEdit && (
                            <div className="flex gap-4 pt-4">
                                <Button type="submit" disabled={processing} className="flex-1">
                                    {processing ? 'Updating...' : 'Update Invoice'}
                                </Button>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => window.history.back()}
                                >
                                    Cancel
                                </Button>
                            </div>
                        )}
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

