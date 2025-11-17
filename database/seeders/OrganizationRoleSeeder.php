<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrganizationRole;

class OrganizationRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'Full access to all organization features and settings',
                'level' => 100,
                'is_system' => true,
                'permissions' => [
                    // Organization management
                    'organization.view',
                    'organization.update',
                    'organization.delete',
                    'organization.settings',
                    'organization.billing',
                    
                    // User management
                    'users.view',
                    'users.invite',
                    'users.remove',
                    'users.change_role',
                    'users.manage',
                    
                    // Financial
                    'money.view',
                    'money.create',
                    'money.update',
                    'money.delete',
                    'accounts.view',
                    'accounts.create',
                    'accounts.update',
                    'accounts.delete',
                    'budgets.view',
                    'budgets.create',
                    'budgets.update',
                    'budgets.delete',
                    'invoices.view',
                    'invoices.create',
                    'invoices.update',
                    'invoices.delete',
                    'payments.view',
                    'payments.create',
                    'payments.update',
                    'payments.delete',
                    'pos.view',
                    'pos.create',
                    'pos.update',
                    
                    // Customers & Products
                    'customers.view',
                    'customers.create',
                    'customers.update',
                    'customers.delete',
                    'products.view',
                    'products.create',
                    'products.update',
                    'products.delete',
                    
                    // People & HR
                    'people.view',
                    'people.create',
                    'people.update',
                    'people.delete',
                    'team.view',
                    'team.create',
                    'team.update',
                    'team.delete',
                    'payroll.view',
                    'payroll.create',
                    'payroll.update',
                    'payroll.delete',
                    'leave.view',
                    'leave.create',
                    'leave.update',
                    'leave.delete',
                    'commissions.view',
                    'commissions.create',
                    'commissions.update',
                    'commissions.delete',
                    
                    // Reports
                    'reports.view',
                    'reports.export',
                    
                    // Documents
                    'documents.view',
                    'documents.create',
                    'documents.update',
                    'documents.delete',
                    
                    // Addy AI
                    'addy.view',
                    'addy.chat',
                    'addy.insights',
                ],
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Can manage most organization features except billing and deletion',
                'level' => 80,
                'is_system' => true,
                'permissions' => [
                    // Organization management (limited)
                    'organization.view',
                    'organization.update',
                    'organization.settings',
                    
                    // User management
                    'users.view',
                    'users.invite',
                    'users.remove',
                    'users.change_role',
                    
                    // Financial
                    'money.view',
                    'money.create',
                    'money.update',
                    'accounts.view',
                    'accounts.create',
                    'accounts.update',
                    'budgets.view',
                    'budgets.create',
                    'budgets.update',
                    'invoices.view',
                    'invoices.create',
                    'invoices.update',
                    'payments.view',
                    'payments.create',
                    'payments.update',
                    'pos.view',
                    'pos.create',
                    
                    // Customers & Products
                    'customers.view',
                    'customers.create',
                    'customers.update',
                    'customers.delete',
                    'quotes.view',
                    'quotes.create',
                    'quotes.update',
                    'quotes.delete',
                    'sales.view',
                    'sales.create',
                    'sales.update',
                    'products.view',
                    'products.create',
                    'products.update',
                    'products.delete',
                    'inventory.view',
                    'inventory.create',
                    'inventory.update',
                    'stock.view',
                    'stock.create',
                    'stock.update',
                    
                    // People & HR
                    'people.view',
                    'people.create',
                    'people.update',
                    'people.delete',
                    'team.view',
                    'team.create',
                    'team.update',
                    'team.delete',
                    'payroll.view',
                    'payroll.create',
                    'payroll.update',
                    'leave.view',
                    'leave.create',
                    'leave.update',
                    'commissions.view',
                    'commissions.create',
                    'commissions.update',
                    
                    // Reports
                    'reports.view',
                    'reports.export',
                    
                    // Decisions
                    'decisions.view',
                    'decisions.create',
                    'decisions.update',
                    'okrs.view',
                    'okrs.create',
                    'okrs.update',
                    'goals.view',
                    'goals.create',
                    'goals.update',
                    'projects.view',
                    'projects.create',
                    'projects.update',
                    
                    // Compliance
                    'compliance.view',
                    'compliance.create',
                    'compliance.update',
                    'documents.view',
                    'documents.create',
                    'documents.update',
                    'documents.delete',
                    'licenses.view',
                    'licenses.create',
                    'licenses.update',
                    'tax.view',
                    'tax.create',
                    'tax.update',
                    
                    // Addy AI
                    'addy.view',
                    'addy.chat',
                    'addy.insights',
                ],
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Can manage day-to-day operations and view reports',
                'level' => 60,
                'is_system' => true,
                'permissions' => [
                    // Organization management (view only)
                    'organization.view',
                    
                    // User management (view only)
                    'users.view',
                    
                    // Financial
                    'money.view',
                    'money.create',
                    'money.update',
                    'accounts.view',
                    'budgets.view',
                    'invoices.view',
                    'invoices.create',
                    'invoices.update',
                    'payments.view',
                    'payments.create',
                    'pos.view',
                    
                    // Customers & Products
                    'customers.view',
                    'customers.create',
                    'customers.update',
                    'quotes.view',
                    'quotes.create',
                    'quotes.update',
                    'sales.view',
                    'sales.create',
                    'products.view',
                    'products.create',
                    'products.update',
                    'inventory.view',
                    'stock.view',
                    
                    // People & HR
                    'people.view',
                    'people.create',
                    'people.update',
                    'team.view',
                    'team.create',
                    'team.update',
                    'payroll.view',
                    'leave.view',
                    'leave.create',
                    'leave.update',
                    
                    // Reports
                    'reports.view',
                    'reports.export',
                    
                    // Decisions
                    'decisions.view',
                    'okrs.view',
                    'goals.view',
                    'projects.view',
                    'projects.create',
                    'projects.update',
                    
                    // Compliance
                    'compliance.view',
                    'documents.view',
                    'documents.create',
                    'documents.update',
                    'licenses.view',
                    'tax.view',
                    
                    // Addy AI
                    'addy.view',
                    'addy.chat',
                ],
            ],
            [
                'name' => 'Member',
                'slug' => 'member',
                'description' => 'Basic access to view and create content',
                'level' => 40,
                'is_system' => true,
                'permissions' => [
                    // Organization management (view only)
                    'organization.view',
                    
                    // Financial (view and create)
                    'money.view',
                    'money.create',
                    'accounts.view',
                    'invoices.view',
                    'invoices.create',
                    'payments.view',
                    'payments.create',
                    
                    // Customers & Products
                    'customers.view',
                    'customers.create',
                    'quotes.view',
                    'quotes.create',
                    'sales.view',
                    'products.view',
                    'products.create',
                    'inventory.view',
                    
                    // People & HR (view only)
                    'people.view',
                    'team.view',
                    
                    // Reports (view only)
                    'reports.view',
                    
                    // Decisions (view only)
                    'decisions.view',
                    'okrs.view',
                    'goals.view',
                    'projects.view',
                    
                    // Compliance (view and create)
                    'compliance.view',
                    'documents.view',
                    'documents.create',
                    'licenses.view',
                    
                    // Addy AI
                    'addy.view',
                    'addy.chat',
                ],
            ],
            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to organization data',
                'level' => 20,
                'is_system' => true,
                'permissions' => [
                    'organization.view',
                    'money.view',
                    'accounts.view',
                    'invoices.view',
                    'payments.view',
                    'customers.view',
                    'quotes.view',
                    'sales.view',
                    'products.view',
                    'inventory.view',
                    'people.view',
                    'team.view',
                    'reports.view',
                    'decisions.view',
                    'okrs.view',
                    'goals.view',
                    'projects.view',
                    'compliance.view',
                    'documents.view',
                    'licenses.view',
                    'addy.view',
                ],
            ],
            [
                'name' => 'Accountant',
                'slug' => 'accountant',
                'description' => 'Full access to financial records and reports',
                'level' => 50,
                'is_system' => true,
                'permissions' => [
                    // Organization management (view only)
                    'organization.view',
                    
                    // Financial (full access)
                    'money.view',
                    'money.create',
                    'money.update',
                    'money.delete',
                    'invoices.view',
                    'invoices.create',
                    'invoices.update',
                    'invoices.delete',
                    'payments.view',
                    'payments.create',
                    'payments.update',
                    'payments.delete',
                    
                    // Customers (view and update)
                    'customers.view',
                    'customers.update',
                    
                    // Products (view only)
                    'products.view',
                    
                    // People (view only)
                    'people.view',
                    'payroll.view',
                    
                    // Reports (full access)
                    'reports.view',
                    'reports.export',
                    
                    // Documents (financial documents)
                    'documents.view',
                    'documents.create',
                    'documents.update',
                    
                    // Addy AI
                    'addy.view',
                    'addy.chat',
                ],
            ],
            [
                'name' => 'Sales Representative',
                'slug' => 'sales_rep',
                'description' => 'Can manage sales, customers, quotes, and invoices',
                'level' => 45,
                'is_system' => true,
                'permissions' => [
                    // Organization management (view only)
                    'organization.view',
                    
                    // Financial (view and create)
                    'money.view',
                    'money.create',
                    'invoices.view',
                    'invoices.create',
                    'invoices.update',
                    'payments.view',
                    'payments.create',
                    
                    // Customers (full access)
                    'customers.view',
                    'customers.create',
                    'customers.update',
                    'customers.delete',
                    
                    // Products (view and create)
                    'products.view',
                    'products.create',
                    'products.update',
                    
                    // Sales (quotes, invoices, payments)
                    'quotes.view',
                    'quotes.create',
                    'quotes.update',
                    'quotes.delete',
                    'sales.view',
                    'sales.create',
                    'sales.update',
                    
                    // People (view only)
                    'people.view',
                    
                    // Reports (sales reports)
                    'reports.view',
                    'reports.export',
                    
                    // Documents (sales documents)
                    'documents.view',
                    'documents.create',
                    
                    // Addy AI
                    'addy.view',
                    'addy.chat',
                ],
            ],
            [
                'name' => 'Inventory Manager',
                'slug' => 'inventory_manager',
                'description' => 'Can manage inventory, products, and stock',
                'level' => 55,
                'is_system' => true,
                'permissions' => [
                    // Organization management (view only)
                    'organization.view',
                    
                    // Financial (view only)
                    'money.view',
                    'invoices.view',
                    'payments.view',
                    
                    // Customers (view only)
                    'customers.view',
                    
                    // Products (full access)
                    'products.view',
                    'products.create',
                    'products.update',
                    'products.delete',
                    
                    // Inventory (full access)
                    'inventory.view',
                    'inventory.create',
                    'inventory.update',
                    'inventory.delete',
                    'stock.view',
                    'stock.create',
                    'stock.update',
                    'stock.delete',
                    
                    // People (view only)
                    'people.view',
                    
                    // Reports (inventory reports)
                    'reports.view',
                    'reports.export',
                    
                    // Documents (inventory documents)
                    'documents.view',
                    'documents.create',
                    'documents.update',
                    
                    // Addy AI
                    'addy.view',
                    'addy.chat',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            OrganizationRole::updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }
    }
}
