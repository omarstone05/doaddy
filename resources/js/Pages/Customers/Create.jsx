import { Head, useForm } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { ArrowLeft } from 'lucide-react';

export default function CustomersCreate({ personas }) {
    const { data, setData, post, processing, errors } = useForm({
        customer_persona_id: '',
        type: 'business',
        name: '',
        email: '',
        phone: '',
        website: '',
        tax_id: '',
        billing_address: '',
        shipping_address: '',
        city: '',
        state: '',
        country: '',
        postal_code: '',
        credit_limit: '',
        payment_terms: 'net_30',
        custom_payment_days: '',
        currency: 'ZMW',
        primary_contact_name: '',
        primary_contact_email: '',
        primary_contact_phone: '',
        notes: '',
        tags: [],
    });

    const submit = (e) => {
        e.preventDefault();
        post('/customers');
    };

    return (
        <SectionLayout sectionName="Sales">
            <Head title="Create Customer" />
            <div className="max-w-2xl mx-auto">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => window.history.back()}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <h1 className="text-3xl font-bold text-gray-900">Create Customer</h1>
                    <p className="text-gray-500 mt-1">Add a new customer to your database</p>
                </div>

                <form onSubmit={submit} className="bg-white border border-gray-200 rounded-lg p-6">
                    <div className="space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="type" className="block text-sm font-medium text-gray-700 mb-2">
                                    Customer Type *
                                </label>
                                <select
                                    id="type"
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                >
                                    <option value="individual">Individual</option>
                                    <option value="business">Business</option>
                                </select>
                                {errors.type && <p className="mt-1 text-sm text-red-600">{errors.type}</p>}
                            </div>

                            <div>
                                <label htmlFor="customer_persona_id" className="block text-sm font-medium text-gray-700 mb-2">
                                    Customer Persona
                                </label>
                                <select
                                    id="customer_persona_id"
                                    value={data.customer_persona_id}
                                    onChange={(e) => setData('customer_persona_id', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="">Select persona</option>
                                    {personas && personas.map((persona) => (
                                        <option key={persona.id} value={persona.id}>{persona.name}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div>
                            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
                                {data.type === 'business' ? 'Business Name' : 'Full Name'} *
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                required
                            />
                            {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                                {errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
                            </div>

                            <div>
                                <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-2">
                                    Phone
                                </label>
                                <input
                                    id="phone"
                                    type="text"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                                {errors.phone && <p className="mt-1 text-sm text-red-600">{errors.phone}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="website" className="block text-sm font-medium text-gray-700 mb-2">
                                    Website
                                </label>
                                <input
                                    id="website"
                                    type="url"
                                    value={data.website}
                                    onChange={(e) => setData('website', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="https://"
                                />
                                {errors.website && <p className="mt-1 text-sm text-red-600">{errors.website}</p>}
                            </div>

                            <div>
                                <label htmlFor="tax_id" className="block text-sm font-medium text-gray-700 mb-2">
                                    Tax ID
                                </label>
                                <input
                                    id="tax_id"
                                    type="text"
                                    value={data.tax_id}
                                    onChange={(e) => setData('tax_id', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                                {errors.tax_id && <p className="mt-1 text-sm text-red-600">{errors.tax_id}</p>}
                            </div>
                        </div>

                        <div className="border-t border-gray-200 pt-4">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Address Information</h3>
                            <div>
                                <label htmlFor="billing_address" className="block text-sm font-medium text-gray-700 mb-2">
                                    Billing Address
                                </label>
                                <textarea
                                    id="billing_address"
                                    value={data.billing_address}
                                    onChange={(e) => setData('billing_address', e.target.value)}
                                    rows={2}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                                {errors.billing_address && <p className="mt-1 text-sm text-red-600">{errors.billing_address}</p>}
                            </div>

                            <div className="mt-4">
                                <label htmlFor="shipping_address" className="block text-sm font-medium text-gray-700 mb-2">
                                    Shipping Address
                                </label>
                                <textarea
                                    id="shipping_address"
                                    value={data.shipping_address}
                                    onChange={(e) => setData('shipping_address', e.target.value)}
                                    rows={2}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                                {errors.shipping_address && <p className="mt-1 text-sm text-red-600">{errors.shipping_address}</p>}
                            </div>

                            <div className="grid grid-cols-4 gap-4 mt-4">
                                <div>
                                    <label htmlFor="city" className="block text-sm font-medium text-gray-700 mb-2">City</label>
                                    <input
                                        id="city"
                                        type="text"
                                        value={data.city}
                                        onChange={(e) => setData('city', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="state" className="block text-sm font-medium text-gray-700 mb-2">State</label>
                                    <input
                                        id="state"
                                        type="text"
                                        value={data.state}
                                        onChange={(e) => setData('state', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="country" className="block text-sm font-medium text-gray-700 mb-2">Country</label>
                                    <input
                                        id="country"
                                        type="text"
                                        value={data.country}
                                        onChange={(e) => setData('country', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="postal_code" className="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                                    <input
                                        id="postal_code"
                                        type="text"
                                        value={data.postal_code}
                                        onChange={(e) => setData('postal_code', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="border-t border-gray-200 pt-4">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Payment & Financial</h3>
                            <div className="grid grid-cols-3 gap-4">
                                <div>
                                    <label htmlFor="payment_terms" className="block text-sm font-medium text-gray-700 mb-2">
                                        Payment Terms *
                                    </label>
                                    <select
                                        id="payment_terms"
                                        value={data.payment_terms}
                                        onChange={(e) => setData('payment_terms', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="immediate">Immediate</option>
                                        <option value="net_15">Net 15</option>
                                        <option value="net_30">Net 30</option>
                                        <option value="net_60">Net 60</option>
                                        <option value="net_90">Net 90</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                    {errors.payment_terms && <p className="mt-1 text-sm text-red-600">{errors.payment_terms}</p>}
                                </div>

                                {data.payment_terms === 'custom' && (
                                    <div>
                                        <label htmlFor="custom_payment_days" className="block text-sm font-medium text-gray-700 mb-2">
                                            Custom Days *
                                        </label>
                                        <input
                                            id="custom_payment_days"
                                            type="number"
                                            min="1"
                                            value={data.custom_payment_days}
                                            onChange={(e) => setData('custom_payment_days', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                            required={data.payment_terms === 'custom'}
                                        />
                                        {errors.custom_payment_days && <p className="mt-1 text-sm text-red-600">{errors.custom_payment_days}</p>}
                                    </div>
                                )}

                                <div>
                                    <label htmlFor="currency" className="block text-sm font-medium text-gray-700 mb-2">
                                        Currency *
                                    </label>
                                    <select
                                        id="currency"
                                        value={data.currency}
                                        onChange={(e) => setData('currency', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="ZMW">ZMW (Zambian Kwacha)</option>
                                        <option value="USD">USD (US Dollar)</option>
                                        <option value="EUR">EUR (Euro)</option>
                                        <option value="GBP">GBP (British Pound)</option>
                                    </select>
                                    {errors.currency && <p className="mt-1 text-sm text-red-600">{errors.currency}</p>}
                                </div>

                                <div>
                                    <label htmlFor="credit_limit" className="block text-sm font-medium text-gray-700 mb-2">
                                        Credit Limit
                                    </label>
                                    <input
                                        id="credit_limit"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.credit_limit}
                                        onChange={(e) => setData('credit_limit', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                    {errors.credit_limit && <p className="mt-1 text-sm text-red-600">{errors.credit_limit}</p>}
                                </div>
                            </div>
                        </div>

                        {data.type === 'business' && (
                            <div className="border-t border-gray-200 pt-4">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Primary Contact</h3>
                                <div className="grid grid-cols-3 gap-4">
                                    <div>
                                        <label htmlFor="primary_contact_name" className="block text-sm font-medium text-gray-700 mb-2">
                                            Contact Name
                                        </label>
                                        <input
                                            id="primary_contact_name"
                                            type="text"
                                            value={data.primary_contact_name}
                                            onChange={(e) => setData('primary_contact_name', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        />
                                    </div>
                                    <div>
                                        <label htmlFor="primary_contact_email" className="block text-sm font-medium text-gray-700 mb-2">
                                            Contact Email
                                        </label>
                                        <input
                                            id="primary_contact_email"
                                            type="email"
                                            value={data.primary_contact_email}
                                            onChange={(e) => setData('primary_contact_email', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        />
                                    </div>
                                    <div>
                                        <label htmlFor="primary_contact_phone" className="block text-sm font-medium text-gray-700 mb-2">
                                            Contact Phone
                                        </label>
                                        <input
                                            id="primary_contact_phone"
                                            type="text"
                                            value={data.primary_contact_phone}
                                            onChange={(e) => setData('primary_contact_phone', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        />
                                    </div>
                                </div>
                            </div>
                        )}

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
                            {errors.notes && <p className="mt-1 text-sm text-red-600">{errors.notes}</p>}
                        </div>

                        <div className="flex gap-4 pt-4">
                            <Button type="submit" disabled={processing} className="flex-1">
                                Create Customer
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
            </div>
        </SectionLayout>
    );
}

