import { useState, useEffect } from 'react';
import { useForm, router } from '@inertiajs/react';
import { Modal, ModalHeader, ModalBody, ModalFooter } from '@/Components/ui/Modal/Modal';
import { Button } from '@/Components/ui/Button';
import QuickCreateCustomerModal from '@/Components/QuickCreateCustomerModal';
import QuickCreateProductModal from '@/Components/QuickCreateProductModal';
import ProductSearchInput from '@/Components/ProductSearchInput';
import SearchableSelect from '@/Components/ui/SearchableSelect';
import { Plus, Trash2 } from 'lucide-react';

export default function CreateQuoteModal({ isOpen, onClose, onSuccess, customers: initialCustomers = [], prospects: initialProspects = [], products: initialProducts = [] }) {
    const [customers, setCustomers] = useState(initialCustomers);
    const [prospects, setProspects] = useState(initialProspects);
    const [products, setProducts] = useState(initialProducts);
    const [showCustomerModal, setShowCustomerModal] = useState(false);
    const [showProductModal, setShowProductModal] = useState(false);
    const [currentProductModalIndex, setCurrentProductModalIndex] = useState(null);
    const [clientType, setClientType] = useState('customer');
    const [items, setItems] = useState([{ name: '', description: '', quantity: 1, unit_price: 0, goods_service_id: '' }]);
    const [loading, setLoading] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        customer_id: '',
        prospect_id: '',
        quote_date: new Date().toISOString().split('T')[0],
        expiry_date: '',
        items: [],
        tax_amount: '0',
        discount_amount: '0',
        notes: '',
        terms: '',
    });

    // Update state when props change
    useEffect(() => {
        if (initialCustomers.length > 0) {
            setCustomers(initialCustomers);
        }
        if (initialProspects.length > 0) {
            setProspects(initialProspects);
        }
        if (initialProducts.length > 0) {
            setProducts(initialProducts);
        }
    }, [initialCustomers, initialProspects, initialProducts]);

    // Reset form when modal closes
    useEffect(() => {
        if (!isOpen) {
            reset();
            setItems([{ name: '', description: '', quantity: 1, unit_price: 0, goods_service_id: '' }]);
            setClientType('customer');
        }
    }, [isOpen, reset]);

    const addItem = () => {
        setItems([...items, { name: '', description: '', quantity: 1, unit_price: 0, goods_service_id: '' }]);
    };

    const removeItem = (index) => {
        setItems(items.filter((_, i) => i !== index));
    };

    const updateItem = (index, field, value) => {
        const newItems = [...items];
        newItems[index][field] = value;
        setItems(newItems);
    };

    const handleProductSelect = (index, product) => {
        if (!product) return;
        
        const newItems = [...items];
        newItems[index].goods_service_id = product.id;
        newItems[index].name = product.name;
        newItems[index].description = product.description || '';
        newItems[index].unit_price = parseFloat(product.selling_price) || 0;
        setItems(newItems);
    };

    const handleProductTextChange = (index, text) => {
        // When user types in the search input, update the name field for manual entry
        const newItems = [...items];
        // Only update name if no product is selected (goods_service_id is empty)
        // This allows manual entry when user types but doesn't select from dropdown
        if (!newItems[index].goods_service_id || !newItems[index].name) {
            newItems[index].name = text;
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
        
        if (clientType === 'customer' && !data.customer_id) {
            alert('Please select a customer');
            return;
        }
        if (clientType === 'prospect' && !data.prospect_id) {
            alert('Please select a prospect');
            return;
        }
        
        // Ensure all items have required fields
        const itemsWithNames = items.map(item => {
            // If product was selected but name is missing, try to get it from products array
            if (item.goods_service_id && !item.name) {
                const product = products.find(p => p.id === item.goods_service_id);
                if (product) {
                    item.name = product.name;
                }
            }
            return item;
        });
        
        // Filter out invalid items - must have name and valid quantity
        // Allow manual entry (name typed) OR product selection (goods_service_id)
        const validItems = itemsWithNames.filter(item => {
            const hasName = item.name && item.name.trim() !== '';
            const hasValidQuantity = item.quantity && parseFloat(item.quantity) > 0;
            const hasValidPrice = item.unit_price !== undefined && item.unit_price !== null && parseFloat(item.unit_price) >= 0;
            
            return hasName && hasValidQuantity && hasValidPrice;
        });
        
        if (validItems.length === 0) {
            alert('Please add at least one item. Each item must have:\n- Product name (type to search or enter manually)\n- Quantity (greater than 0)\n- Unit price');
            return;
        }

        console.log('Valid items before submission:', validItems);

        // Prepare items data for submission
        const itemsData = validItems.map(item => ({
            name: item.name.trim(),
            description: item.description?.trim() || null,
            quantity: parseFloat(item.quantity),
            unit_price: parseFloat(item.unit_price) || 0,
            goods_service_id: item.goods_service_id || null,
        }));

        console.log('Submitting items:', itemsData);

        // Format dates to ensure they're in yyyy-MM-dd format
        const formatDate = (dateValue) => {
            if (!dateValue) return '';
            // If it's already in yyyy-MM-dd format, return as is
            if (typeof dateValue === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateValue)) {
                return dateValue;
            }
            // If it's an ISO string, extract just the date part
            if (typeof dateValue === 'string' && dateValue.includes('T')) {
                return dateValue.split('T')[0];
            }
            // If it's a Date object, format it
            if (dateValue instanceof Date) {
                return dateValue.toISOString().split('T')[0];
            }
            return dateValue;
        };

        // Prepare final data with items and properly formatted dates
        const finalData = {
            customer_id: clientType === 'customer' ? data.customer_id : null,
            prospect_id: clientType === 'prospect' ? data.prospect_id : null,
            quote_date: formatDate(data.quote_date),
            expiry_date: formatDate(data.expiry_date) || null,
            tax_amount: data.tax_amount || '0',
            discount_amount: data.discount_amount || '0',
            notes: data.notes || null,
            terms: data.terms || null,
            items: itemsData,
        };
        
        console.log('Submitting quotation with data:', finalData);

        setLoading(true);
        
        // Use router.post instead of useForm's post - useForm's post doesn't accept data as parameter
        router.post('/quotations', finalData, {
            preserveState: false,
            preserveScroll: false,
            onSuccess: () => {
                setLoading(false);
                alert('Quotation created successfully!');
                onSuccess?.();
                onClose();
            },
            onError: (errors) => {
                setLoading(false);
                console.error('Quote creation errors:', errors);
                
                let errorMessage = 'Failed to create quotation. ';
                
                if (errors.error) {
                    errorMessage += Array.isArray(errors.error) ? errors.error[0] : errors.error;
                } else if (errors.customer_id) {
                    errorMessage += Array.isArray(errors.customer_id) ? errors.customer_id[0] : errors.customer_id;
                } else if (errors.prospect_id) {
                    errorMessage += Array.isArray(errors.prospect_id) ? errors.prospect_id[0] : errors.prospect_id;
                } else if (errors.items) {
                    errorMessage += 'Items are required. Please add at least one item.';
                } else {
                    errorMessage += 'Please check all fields and try again.';
                }
                alert(errorMessage);
            },
        });
    };

    return (
        <>
            <Modal isOpen={isOpen} onClose={onClose} size="2xl">
                <form onSubmit={submit}>
                    <ModalHeader 
                        title="Create New Quote" 
                        subtitle="Add products and services to create a quotation"
                        onClose={onClose}
                    />
                    
                    <ModalBody className="max-h-[70vh] overflow-y-auto">
                        {loading ? (
                            <div className="py-8 text-center text-gray-500">Loading...</div>
                        ) : (
                            <div className="space-y-6">
                                {/* Client Type Selection */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Client Type *
                                    </label>
                                    <div className="flex gap-4">
                                        <label className="flex items-center">
                                            <input
                                                type="radio"
                                                name="client_type"
                                                value="customer"
                                                checked={clientType === 'customer'}
                                                onChange={(e) => {
                                                    setClientType('customer');
                                                    setData('prospect_id', '');
                                                }}
                                                className="mr-2"
                                            />
                                            <span className="text-sm text-gray-700">Customer</span>
                                        </label>
                                        <label className="flex items-center">
                                            <input
                                                type="radio"
                                                name="client_type"
                                                value="prospect"
                                                checked={clientType === 'prospect'}
                                                onChange={(e) => {
                                                    setClientType('prospect');
                                                    setData('customer_id', '');
                                                }}
                                                className="mr-2"
                                            />
                                            <span className="text-sm text-gray-700">Prospect</span>
                                        </label>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        {clientType === 'customer' ? (
                                            <div className="space-y-1">
                                                <div className="flex items-center justify-between">
                                                    <label className="block text-sm font-medium text-gray-700">
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
                                                <SearchableSelect
                                                    value={data.customer_id}
                                                    onChange={(id) => setData('customer_id', id)}
                                                    options={customers}
                                                    placeholder="Type to search customers..."
                                                    displayField="name"
                                                    valueField="id"
                                                    maxResults={5}
                                                    error={errors.customer_id}
                                                />
                                            </div>
                                        ) : (
                                            <div className="space-y-1">
                                                <label className="block text-sm font-medium text-gray-700">
                                                    Prospect *
                                                </label>
                                                <SearchableSelect
                                                    value={data.prospect_id}
                                                    onChange={(id) => setData('prospect_id', id)}
                                                    options={prospects.map(p => ({ ...p, name: p.company_name || p.name }))}
                                                    placeholder="Type to search prospects..."
                                                    displayField="name"
                                                    valueField="id"
                                                    maxResults={5}
                                                    error={errors.prospect_id}
                                                />
                                            </div>
                                        )}
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

                                    <div className="space-y-3 overflow-x-auto">
                                        {items.map((item, index) => (
                                            <div key={index} className="grid grid-cols-12 gap-2 items-start p-3 bg-gray-50 rounded-lg min-w-full">
                                                <div className="col-span-5 min-w-0">
                                                    <ProductSearchInput
                                                        value={item.goods_service_id || ''}
                                                        onChange={(productId) => updateItem(index, 'goods_service_id', productId)}
                                                        onProductSelect={(product) => handleProductSelect(index, product)}
                                                        onTextChange={(text) => handleProductTextChange(index, text)}
                                                        placeholder="Type to search products..."
                                                    />
                                                </div>
                                                <div className="col-span-4 min-w-0">
                                                    <input
                                                        type="text"
                                                        value={item.description}
                                                        onChange={(e) => updateItem(index, 'description', e.target.value)}
                                                        placeholder="Description (optional)"
                                                        className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg"
                                                    />
                                                </div>
                                                <div className="col-span-1 min-w-[60px]">
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
                                                <div className="col-span-2 min-w-[120px]">
                                                    <div className="flex gap-1">
                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            value={item.unit_price}
                                                            onChange={(e) => updateItem(index, 'unit_price', e.target.value)}
                                                            placeholder="Price"
                                                            className="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg min-w-0"
                                                            required
                                                        />
                                                        {items.length > 1 && (
                                                            <button
                                                                type="button"
                                                                onClick={() => removeItem(index)}
                                                                className="p-2 text-red-500 hover:text-red-700 flex-shrink-0"
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
                            </div>
                        )}
                    </ModalBody>

                    <ModalFooter>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={onClose}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing || loading}>
                            {processing ? 'Creating...' : 'Create Quote'}
                        </Button>
                    </ModalFooter>
                </form>
            </Modal>

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
        </>
    );
}


