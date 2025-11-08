import { Head, Link, router } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/Card';
import { SectionInsightCard } from '@/Components/sections/SectionInsightCard';
import { Package, Box, TrendingUp, AlertTriangle, Plus } from 'lucide-react';

export default function InventoryIndex({ stats, insights }) {
    return (
        <SectionLayout sectionName="Inventory">
            <Head title="Inventory" />
            
            {/* Addy Insights Card */}
            <SectionInsightCard 
                sectionName="Inventory" 
                insights={insights || []}
            />
            
            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Total Products</p>
                                <p className="text-3xl font-bold text-teal-500">{stats?.total_products || 0}</p>
                            </div>
                            <div className="p-3 bg-teal-500/10 rounded-lg">
                                <Box className="h-6 w-6 text-teal-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Total Stock Value</p>
                                <p className="text-3xl font-bold text-green-500">{stats?.total_stock_value || 'K0'}</p>
                            </div>
                            <div className="p-3 bg-green-500/10 rounded-lg">
                                <Package className="h-6 w-6 text-green-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Low Stock Items</p>
                                <p className="text-3xl font-bold text-red-500">{stats?.low_stock_items || 0}</p>
                            </div>
                            <div className="p-3 bg-red-500/10 rounded-lg">
                                <AlertTriangle className="h-6 w-6 text-red-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 mb-1">Stock Movements</p>
                                <p className="text-3xl font-bold text-blue-500">{stats?.stock_movements || 0}</p>
                            </div>
                            <div className="p-3 bg-blue-500/10 rounded-lg">
                                <TrendingUp className="h-6 w-6 text-blue-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Quick Actions */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <Card className="hover:shadow-lg transition-shadow cursor-pointer" onClick={() => router.visit('/products/create')}>
                    <CardContent className="pt-6">
                        <div className="flex flex-col items-center text-center">
                            <div className="p-4 bg-teal-500/10 rounded-full mb-4">
                                <Box className="h-8 w-8 text-teal-500" />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">Add Product</h3>
                            <p className="text-sm text-gray-600">Create a new product</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="hover:shadow-lg transition-shadow cursor-pointer" onClick={() => router.visit('/stock/adjustments/create')}>
                    <CardContent className="pt-6">
                        <div className="flex flex-col items-center text-center">
                            <div className="p-4 bg-green-500/10 rounded-full mb-4">
                                <TrendingUp className="h-8 w-8 text-green-500" />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">Stock Adjustment</h3>
                            <p className="text-sm text-gray-600">Adjust stock levels</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="hover:shadow-lg transition-shadow cursor-pointer" onClick={() => router.visit('/stock')}>
                    <CardContent className="pt-6">
                        <div className="flex flex-col items-center text-center">
                            <div className="p-4 bg-blue-500/10 rounded-full mb-4">
                                <Package className="h-8 w-8 text-blue-500" />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">View Stock</h3>
                            <p className="text-sm text-gray-600">View stock levels</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SectionLayout>
    );
}

