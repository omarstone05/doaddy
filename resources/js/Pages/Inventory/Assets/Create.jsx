import { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { ArrowLeft } from 'lucide-react';

export default function AssetsCreate({ users, departments }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        asset_number: '',
        asset_tag: '',
        category: '',
        description: '',
        purchase_date: '',
        purchase_price: '',
        current_value: '',
        supplier: '',
        purchase_order_number: '',
        manufacturer: '',
        model: '',
        serial_number: '',
        location: '',
        assigned_to_user_id: '',
        assigned_to_department_id: '',
        status: 'active',
        condition: 'good',
        warranty_expiry: '',
        last_maintenance_date: '',
        next_maintenance_date: '',
        maintenance_notes: '',
        depreciation_method: 'none',
        useful_life_years: '',
        salvage_value: '',
        notes: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/assets');
    };

    return (
        <SectionLayout sectionName="Inventory">
            <Head title="Create Asset" />
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        onClick={() => router.visit('/assets')}
                        className="mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <h1 className="text-3xl font-bold text-gray-900">Create Asset</h1>
                    <p className="text-gray-500 mt-1">Add a new internal asset to your organization</p>
                </div>

                <form onSubmit={submit}>
                    <Card className="p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
                                        Asset Name *
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
                                <div>
                                    <label htmlFor="asset_number" className="block text-sm font-medium text-gray-700 mb-2">
                                        Asset Number
                                    </label>
                                    <input
                                        id="asset_number"
                                        type="text"
                                        value={data.asset_number}
                                        onChange={(e) => setData('asset_number', e.target.value)}
                                        placeholder="Auto-generated if left empty"
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                    {errors.asset_number && <p className="mt-1 text-sm text-red-600">{errors.asset_number}</p>}
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="asset_tag" className="block text-sm font-medium text-gray-700 mb-2">
                                        Asset Tag
                                    </label>
                                    <input
                                        id="asset_tag"
                                        type="text"
                                        value={data.asset_tag}
                                        onChange={(e) => setData('asset_tag', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="category" className="block text-sm font-medium text-gray-700 mb-2">
                                        Category
                                    </label>
                                    <input
                                        id="category"
                                        type="text"
                                        value={data.category}
                                        onChange={(e) => setData('category', e.target.value)}
                                        placeholder="e.g., Equipment, Furniture, Vehicle, IT"
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                            <div>
                                <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={3}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Purchase Information</h2>
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="purchase_date" className="block text-sm font-medium text-gray-700 mb-2">
                                        Purchase Date
                                    </label>
                                    <input
                                        id="purchase_date"
                                        type="date"
                                        value={data.purchase_date}
                                        onChange={(e) => setData('purchase_date', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="purchase_price" className="block text-sm font-medium text-gray-700 mb-2">
                                        Purchase Price
                                    </label>
                                    <input
                                        id="purchase_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.purchase_price}
                                        onChange={(e) => {
                                            setData('purchase_price', e.target.value);
                                            if (!data.current_value) {
                                                setData('current_value', e.target.value);
                                            }
                                        }}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="current_value" className="block text-sm font-medium text-gray-700 mb-2">
                                        Current Value
                                    </label>
                                    <input
                                        id="current_value"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.current_value}
                                        onChange={(e) => setData('current_value', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="supplier" className="block text-sm font-medium text-gray-700 mb-2">
                                        Supplier
                                    </label>
                                    <input
                                        id="supplier"
                                        type="text"
                                        value={data.supplier}
                                        onChange={(e) => setData('supplier', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                            <div>
                                <label htmlFor="purchase_order_number" className="block text-sm font-medium text-gray-700 mb-2">
                                    Purchase Order Number
                                </label>
                                <input
                                    id="purchase_order_number"
                                    type="text"
                                    value={data.purchase_order_number}
                                    onChange={(e) => setData('purchase_order_number', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Asset Details</h2>
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="manufacturer" className="block text-sm font-medium text-gray-700 mb-2">
                                        Manufacturer
                                    </label>
                                    <input
                                        id="manufacturer"
                                        type="text"
                                        value={data.manufacturer}
                                        onChange={(e) => setData('manufacturer', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="model" className="block text-sm font-medium text-gray-700 mb-2">
                                        Model
                                    </label>
                                    <input
                                        id="model"
                                        type="text"
                                        value={data.model}
                                        onChange={(e) => setData('model', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="serial_number" className="block text-sm font-medium text-gray-700 mb-2">
                                        Serial Number
                                    </label>
                                    <input
                                        id="serial_number"
                                        type="text"
                                        value={data.serial_number}
                                        onChange={(e) => setData('serial_number', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="location" className="block text-sm font-medium text-gray-700 mb-2">
                                        Location
                                    </label>
                                    <input
                                        id="location"
                                        type="text"
                                        value={data.location}
                                        onChange={(e) => setData('location', e.target.value)}
                                        placeholder="e.g., Office, Warehouse, Building A"
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Assignment</h2>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="assigned_to_user_id" className="block text-sm font-medium text-gray-700 mb-2">
                                    Assigned To User
                                </label>
                                <select
                                    id="assigned_to_user_id"
                                    value={data.assigned_to_user_id}
                                    onChange={(e) => setData('assigned_to_user_id', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="">Select User</option>
                                    {users.map((user) => (
                                        <option key={user.id} value={user.id}>
                                            {user.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label htmlFor="assigned_to_department_id" className="block text-sm font-medium text-gray-700 mb-2">
                                    Assigned To Department
                                </label>
                                <select
                                    id="assigned_to_department_id"
                                    value={data.assigned_to_department_id}
                                    onChange={(e) => setData('assigned_to_department_id', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="">Select Department</option>
                                    {departments.map((dept) => (
                                        <option key={dept.id} value={dept.id}>
                                            {dept.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Status & Condition</h2>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-2">
                                    Status *
                                </label>
                                <select
                                    id="status"
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="retired">Retired</option>
                                    <option value="disposed">Disposed</option>
                                    <option value="lost">Lost</option>
                                </select>
                            </div>
                            <div>
                                <label htmlFor="condition" className="block text-sm font-medium text-gray-700 mb-2">
                                    Condition *
                                </label>
                                <select
                                    id="condition"
                                    value={data.condition}
                                    onChange={(e) => setData('condition', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    required
                                >
                                    <option value="excellent">Excellent</option>
                                    <option value="good">Good</option>
                                    <option value="fair">Fair</option>
                                    <option value="poor">Poor</option>
                                    <option value="needs_repair">Needs Repair</option>
                                </select>
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Warranty & Maintenance</h2>
                        <div className="space-y-4">
                            <div className="grid grid-cols-3 gap-4">
                                <div>
                                    <label htmlFor="warranty_expiry" className="block text-sm font-medium text-gray-700 mb-2">
                                        Warranty Expiry
                                    </label>
                                    <input
                                        id="warranty_expiry"
                                        type="date"
                                        value={data.warranty_expiry}
                                        onChange={(e) => setData('warranty_expiry', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="last_maintenance_date" className="block text-sm font-medium text-gray-700 mb-2">
                                        Last Maintenance
                                    </label>
                                    <input
                                        id="last_maintenance_date"
                                        type="date"
                                        value={data.last_maintenance_date}
                                        onChange={(e) => setData('last_maintenance_date', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="next_maintenance_date" className="block text-sm font-medium text-gray-700 mb-2">
                                        Next Maintenance
                                    </label>
                                    <input
                                        id="next_maintenance_date"
                                        type="date"
                                        value={data.next_maintenance_date}
                                        onChange={(e) => setData('next_maintenance_date', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                            <div>
                                <label htmlFor="maintenance_notes" className="block text-sm font-medium text-gray-700 mb-2">
                                    Maintenance Notes
                                </label>
                                <textarea
                                    id="maintenance_notes"
                                    value={data.maintenance_notes}
                                    onChange={(e) => setData('maintenance_notes', e.target.value)}
                                    rows={3}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Depreciation (Optional)</h2>
                        <div className="space-y-4">
                            <div className="grid grid-cols-3 gap-4">
                                <div>
                                    <label htmlFor="depreciation_method" className="block text-sm font-medium text-gray-700 mb-2">
                                        Depreciation Method
                                    </label>
                                    <select
                                        id="depreciation_method"
                                        value={data.depreciation_method}
                                        onChange={(e) => setData('depreciation_method', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    >
                                        <option value="none">None</option>
                                        <option value="straight_line">Straight Line</option>
                                        <option value="declining_balance">Declining Balance</option>
                                    </select>
                                </div>
                                <div>
                                    <label htmlFor="useful_life_years" className="block text-sm font-medium text-gray-700 mb-2">
                                        Useful Life (Years)
                                    </label>
                                    <input
                                        id="useful_life_years"
                                        type="number"
                                        min="1"
                                        value={data.useful_life_years}
                                        onChange={(e) => setData('useful_life_years', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="salvage_value" className="block text-sm font-medium text-gray-700 mb-2">
                                        Salvage Value
                                    </label>
                                    <input
                                        id="salvage_value"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.salvage_value}
                                        onChange={(e) => setData('salvage_value', e.target.value)}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6 mb-6">
                        <div>
                            <label htmlFor="notes" className="block text-sm font-medium text-gray-700 mb-2">
                                Additional Notes
                            </label>
                            <textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                rows={4}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>
                    </Card>

                    <div className="flex justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit('/assets')}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Creating...' : 'Create Asset'}
                        </Button>
                    </div>
                </form>
            </div>
        </SectionLayout>
    );
}

