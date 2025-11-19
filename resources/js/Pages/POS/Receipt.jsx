import { Head } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Button } from '@/Components/ui/Button';
import { Printer } from 'lucide-react';

export default function Receipt({ sale }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-ZM', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const formatDate = (date) => {
        return new Date(date).toLocaleString('en-ZM', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <SectionLayout sectionName="Sales">
            <Head title={`Receipt - ${sale.sale_number}`} />
            <div className="max-w-2xl mx-auto">
                {/* Print Button (hidden when printing) */}
                <div className="mb-6 no-print">
                    <Button onClick={() => window.print()}>
                        <Printer className="h-4 w-4 mr-2" />
                        Print Receipt
                    </Button>
                </div>

                {/* Receipt */}
                <div className="bg-white border border-gray-200 rounded-lg p-8 print:border-0 print:shadow-none">
                    {/* Header */}
                    <div className="text-center mb-6">
                        <h1 className="text-2xl font-bold text-gray-900 mb-2">Addy Business</h1>
                        <p className="text-sm text-gray-500">Receipt</p>
                    </div>

                    {/* Sale Details */}
                    <div className="border-b border-gray-200 pb-4 mb-4">
                        <div className="flex justify-between mb-2">
                            <span className="text-sm text-gray-600">Sale Number:</span>
                            <span className="text-sm font-medium text-gray-900">{sale.sale_number}</span>
                        </div>
                        <div className="flex justify-between mb-2">
                            <span className="text-sm text-gray-600">Date:</span>
                            <span className="text-sm font-medium text-gray-900">{formatDate(sale.created_at)}</span>
                        </div>
                        {(sale.customer_name || sale.customer?.name) && (
                            <div className="flex justify-between">
                                <span className="text-sm text-gray-600">Customer:</span>
                                <span className="text-sm font-medium text-gray-900">
                                    {sale.customer_name || sale.customer?.name}
                                </span>
                            </div>
                        )}
                    </div>

                    {/* Items */}
                    <div className="mb-4">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-gray-200">
                                    <th className="text-left py-2 text-sm font-medium text-gray-700">Item</th>
                                    <th className="text-right py-2 text-sm font-medium text-gray-700">Qty</th>
                                    <th className="text-right py-2 text-sm font-medium text-gray-700">Price</th>
                                    <th className="text-right py-2 text-sm font-medium text-gray-700">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {sale.items?.map((item, index) => (
                                    <tr key={index} className="border-b border-gray-100">
                                        <td className="py-2 text-sm text-gray-900">
                                            <div className="font-medium">{item.product_name}</div>
                                            {item.sku && (
                                                <div className="text-xs text-gray-500">SKU: {item.sku}</div>
                                            )}
                                        </td>
                                        <td className="py-2 text-sm text-right text-gray-600">{item.quantity}</td>
                                        <td className="py-2 text-sm text-right text-gray-600">{formatCurrency(item.unit_price)}</td>
                                        <td className="py-2 text-sm text-right font-medium text-gray-900">{formatCurrency(item.total)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Totals */}
                    <div className="border-t border-gray-200 pt-4 space-y-2">
                        {sale.discount_amount > 0 && (
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Subtotal:</span>
                                <span className="text-gray-900">{formatCurrency(sale.total_amount + sale.discount_amount)}</span>
                            </div>
                        )}
                        {sale.discount_amount > 0 && (
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Discount:</span>
                                <span className="text-red-600">-{formatCurrency(sale.discount_amount)}</span>
                            </div>
                        )}
                        {sale.tax_amount > 0 && (
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Tax:</span>
                                <span className="text-gray-900">{formatCurrency(sale.tax_amount)}</span>
                            </div>
                        )}
                        <div className="flex justify-between text-lg font-bold pt-2 border-t border-gray-200">
                            <span>Total:</span>
                            <span>{formatCurrency(sale.total_amount)}</span>
                        </div>
                    </div>

                    {/* Payment Method */}
                    <div className="mt-6 pt-4 border-t border-gray-200">
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-600">Payment Method:</span>
                            <span className="font-medium text-gray-900 capitalize">{sale.payment_method.replace('_', ' ')}</span>
                        </div>
                        {sale.payment_reference && (
                            <div className="flex justify-between text-sm mt-1">
                                <span className="text-gray-600">Reference:</span>
                                <span className="text-gray-900">{sale.payment_reference}</span>
                            </div>
                        )}
                    </div>

                    {/* Footer */}
                    <div className="mt-8 pt-4 border-t border-gray-200 text-center text-xs text-gray-500">
                        <p>Thank you for your business!</p>
                        <p className="mt-1">This is a computer-generated receipt.</p>
                    </div>
                </div>

                {/* Print Styles */}
                <style jsx>{`
                    @media print {
                        .no-print {
                            display: none;
                        }
                        body {
                            background: white;
                        }
                    }
                `}</style>
            </div>
        </SectionLayout>
    );
}

