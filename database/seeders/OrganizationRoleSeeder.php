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
                    'invoices.view',
                    'invoices.create',
                    'invoices.update',
                    'invoices.delete',
                    'payments.view',
                    'payments.create',
                    'payments.update',
                    'payments.delete',
                    
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
                    'payroll.view',
                    'payroll.create',
                    'payroll.update',
                    'payroll.delete',
                    
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
                    'invoices.view',
                    'invoices.create',
                    'invoices.update',
                    'payments.view',
                    'payments.create',
                    'payments.update',
                    
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
                    'payroll.view',
                    'payroll.create',
                    'payroll.update',
                    
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
                    'invoices.view',
                    'invoices.create',
                    'invoices.update',
                    'payments.view',
                    'payments.create',
                    
                    // Customers & Products
                    'customers.view',
                    'customers.create',
                    'customers.update',
                    'products.view',
                    'products.create',
                    'products.update',
                    
                    // People & HR
                    'people.view',
                    'people.create',
                    'people.update',
                    'payroll.view',
                    
                    // Reports
                    'reports.view',
                    'reports.export',
                    
                    // Documents
                    'documents.view',
                    'documents.create',
                    'documents.update',
                    
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
                    'invoices.view',
                    'invoices.create',
                    'payments.view',
                    'payments.create',
                    
                    // Customers & Products
                    'customers.view',
                    'customers.create',
                    'products.view',
                    'products.create',
                    
                    // People & HR (view only)
                    'people.view',
                    
                    // Reports (view only)
                    'reports.view',
                    
                    // Documents
                    'documents.view',
                    'documents.create',
                    
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
                    'invoices.view',
                    'payments.view',
                    'customers.view',
                    'products.view',
                    'people.view',
                    'reports.view',
                    'documents.view',
                    'addy.view',
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
