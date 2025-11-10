import React from 'react';
import { Card } from '../ui/Card';
import { QuickActionCard } from './QuickActionCard';
import { Plus, DollarSign, FileText, ShoppingCart, Package, Receipt, Users, CreditCard } from 'lucide-react';
import { router } from '@inertiajs/react';

const defaultActions = [
  { title: 'Add Expense', icon: DollarSign, url: '/money/movements/create?type=expense' },
  { title: 'Create Invoice', icon: FileText, url: '/invoices/create' },
  { title: 'New Sale', icon: ShoppingCart, url: '/pos' },
  { title: 'Add Product', icon: Package, url: '/products/create' },
  { title: 'Create Quote', icon: Receipt, url: '/quotes/create' },
  { title: 'Add Customer', icon: Users, url: '/customers/create' },
  { title: 'Record Payment', icon: CreditCard, url: '/payments/create' },
];

export function QuickActionsCard({ actions = defaultActions, columns = 2 }) {
  return (
    <Card className="h-full flex flex-col">
      <div className="mb-4">
        <h3 className="text-lg font-semibold text-gray-900 mb-1">Quick Actions</h3>
        <p className="text-sm text-gray-500">Common tasks</p>
      </div>
      
      <div className={`grid ${columns === 2 ? 'grid-cols-2' : 'grid-cols-3'} gap-3 flex-1`}>
        {actions.map((action, index) => (
          <QuickActionCard
            key={index}
            title={action.title}
            icon={action.icon}
            onClick={() => action.url && router.visit(action.url)}
            url={action.url}
          />
        ))}
      </div>
    </Card>
  );
}

