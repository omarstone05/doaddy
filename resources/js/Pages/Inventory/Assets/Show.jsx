import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Card } from '@/Components/ui/Card';
import { ArrowLeft, Edit, Package, AlertTriangle, Calendar, DollarSign, MapPin, User, Building } from 'lucide-react';

export default function AssetsShow({ asset }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount || 0);
    };

    const formatDate = (date) => {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('en-ZM', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const getStatusColor = (status) => {
        const colors = {
            active: 'bg-green-100 text-green-800',
            inactive: 'bg-gray-100 text-gray-800',
            maintenance: 'bg-yellow-100 text-yellow-800',
            retired: 'bg-blue-100 text-blue-800',
            disposed: 'bg-red-100 text-red-800',
            lost: 'bg-red-100 text-red-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getConditionColor = (condition) => {
        const colors = {
            excellent: 'bg-green-100 text-green-800',
            good: 'bg-blue-100 text-blue-800',
            fair: 'bg-yellow-100 text-yellow-800',
            poor: 'bg-orange-100 text-orange-800',
            needs_repair: 'bg-red-100 text-red-800',
        };
        return colors[condition] || 'bg-gray-100 text-gray-800';
    };

    return (
        <SectionLayout sectionName="Inventory">
            <Head title={asset.name} />
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
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">{asset.name}</h1>
                            {asset.asset_number && (
                                <p className="text-gray-500 mt-1">Asset #{asset.asset_number}</p>
                            )}
                        </div>
                        <Link href={`/assets/${asset.id}/edit`}>
                            <Button>
                                <Edit className="h-4 w-4 mr-2" />
                                Edit
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Status Badges */}
                <div className="flex gap-4 mb-6">
                    <span className={`px-4 py-2 rounded-full text-sm font-medium ${getStatusColor(asset.status)}`}>
                        {asset.status.charAt(0).toUpperCase() + asset.status.slice(1)}
                    </span>
                    <span className={`px-4 py-2 rounded-full text-sm font-medium ${getConditionColor(asset.condition)}`}>
                        {asset.condition.charAt(0).toUpperCase() + asset.condition.slice(1)}
                    </span>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    {/* Basic Information */}
                    <Card className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <Package className="h-5 w-5 text-teal-500" />
                            Basic Information
                        </h2>
                        <div className="space-y-3">
                            {asset.asset_tag && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">Asset Tag:</span>
                                    <span className="ml-2 text-sm text-gray-900">{asset.asset_tag}</span>
                                </div>
                            )}
                            {asset.category && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">Category:</span>
                                    <span className="ml-2 text-sm text-gray-900">{asset.category}</span>
                                </div>
                            )}
                            {asset.description && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">Description:</span>
                                    <p className="mt-1 text-sm text-gray-900">{asset.description}</p>
                                </div>
                            )}
                            {asset.location && (
                                <div className="flex items-center gap-2">
                                    <MapPin className="h-4 w-4 text-gray-400" />
                                    <span className="text-sm font-medium text-gray-500">Location:</span>
                                    <span className="text-sm text-gray-900">{asset.location}</span>
                                </div>
                            )}
                        </div>
                    </Card>

                    {/* Assignment */}
                    <Card className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <User className="h-5 w-5 text-teal-500" />
                            Assignment
                        </h2>
                        <div className="space-y-3">
                            {asset.assigned_to_user ? (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">Assigned To:</span>
                                    <span className="ml-2 text-sm text-gray-900">{asset.assigned_to_user.name}</span>
                                </div>
                            ) : asset.assigned_to_department ? (
                                <div className="flex items-center gap-2">
                                    <Building className="h-4 w-4 text-gray-400" />
                                    <span className="text-sm font-medium text-gray-500">Department:</span>
                                    <span className="text-sm text-gray-900">{asset.assigned_to_department.name}</span>
                                </div>
                            ) : (
                                <span className="text-sm text-gray-400">Unassigned</span>
                            )}
                        </div>
                    </Card>
                </div>

                {/* Purchase Information */}
                <Card className="p-6 mb-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <DollarSign className="h-5 w-5 text-teal-500" />
                        Purchase Information
                    </h2>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {asset.purchase_date && (
                            <div>
                                <span className="text-sm font-medium text-gray-500">Purchase Date</span>
                                <p className="text-sm text-gray-900 mt-1">{formatDate(asset.purchase_date)}</p>
                            </div>
                        )}
                        {asset.purchase_price && (
                            <div>
                                <span className="text-sm font-medium text-gray-500">Purchase Price</span>
                                <p className="text-sm text-gray-900 mt-1">{formatCurrency(asset.purchase_price)}</p>
                            </div>
                        )}
                        {asset.current_value && (
                            <div>
                                <span className="text-sm font-medium text-gray-500">Current Value</span>
                                <p className="text-sm font-semibold text-gray-900 mt-1">{formatCurrency(asset.current_value)}</p>
                            </div>
                        )}
                        {asset.supplier && (
                            <div>
                                <span className="text-sm font-medium text-gray-500">Supplier</span>
                                <p className="text-sm text-gray-900 mt-1">{asset.supplier}</p>
                            </div>
                        )}
                    </div>
                </Card>

                {/* Asset Details */}
                {(asset.manufacturer || asset.model || asset.serial_number) && (
                    <Card className="p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Asset Details</h2>
                        <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                            {asset.manufacturer && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">Manufacturer</span>
                                    <p className="text-sm text-gray-900 mt-1">{asset.manufacturer}</p>
                                </div>
                            )}
                            {asset.model && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">Model</span>
                                    <p className="text-sm text-gray-900 mt-1">{asset.model}</p>
                                </div>
                            )}
                            {asset.serial_number && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">Serial Number</span>
                                    <p className="text-sm text-gray-900 mt-1">{asset.serial_number}</p>
                                </div>
                            )}
                        </div>
                    </Card>
                )}

                {/* Warranty & Maintenance */}
                {(asset.warranty_expiry || asset.last_maintenance_date || asset.next_maintenance_date) && (
                    <Card className="p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <Calendar className="h-5 w-5 text-teal-500" />
                            Warranty & Maintenance
                        </h2>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {asset.warranty_expiry && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">Warranty Expiry</span>
                                    <p className="text-sm text-gray-900 mt-1">{formatDate(asset.warranty_expiry)}</p>
                                </div>
                            )}
                            {asset.last_maintenance_date && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">Last Maintenance</span>
                                    <p className="text-sm text-gray-900 mt-1">{formatDate(asset.last_maintenance_date)}</p>
                                </div>
                            )}
                            {asset.next_maintenance_date && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">Next Maintenance</span>
                                    <p className={`text-sm font-medium mt-1 ${
                                        new Date(asset.next_maintenance_date) <= new Date()
                                            ? 'text-red-600'
                                            : 'text-gray-900'
                                    }`}>
                                        {formatDate(asset.next_maintenance_date)}
                                        {new Date(asset.next_maintenance_date) <= new Date() && (
                                            <AlertTriangle className="h-4 w-4 inline ml-2" />
                                        )}
                                    </p>
                                </div>
                            )}
                        </div>
                        {asset.maintenance_notes && (
                            <div className="mt-4">
                                <span className="text-sm font-medium text-gray-500">Maintenance Notes</span>
                                <p className="text-sm text-gray-900 mt-1">{asset.maintenance_notes}</p>
                            </div>
                        )}
                    </Card>
                )}

                {/* Notes */}
                {asset.notes && (
                    <Card className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Additional Notes</h2>
                        <p className="text-sm text-gray-900 whitespace-pre-wrap">{asset.notes}</p>
                    </Card>
                )}
            </div>
        </SectionLayout>
    );
}

