<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
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
                'description' => 'Business owner with full access to everything',
                'level' => 100,
                'permissions' => [
                    // Business management
                    'business.view',
                    'business.update',
                    'business.delete',
                    'business.settings',
                    
                    // User management
                    'users.view',
                    'users.invite',
                    'users.remove',
                    'users.change_role',
                    
                    // Financial
                    'transactions.view',
                    'transactions.create',
                    'transactions.update',
                    'transactions.delete',
                    'invoices.view',
                    'invoices.create',
                    'invoices.update',
                    'invoices.delete',
                    
                    // Customers & Products
                    'customers.view',
                    'customers.create',
                    'customers.update',
                    'customers.delete',
                    'products.view',
                    'products.create',
                    'products.update',
                    'products.delete',
                    
                    // Reports
                    'reports.view',
                    'reports.export',
                    
                    // Advanced
                    'god_engine.access',
                    'integrations.manage',
                ],
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrator with most permissions except business deletion',
                'level' => 80,
                'permissions' => [
                    'business.view',
                    'business.update',
                    'business.settings',
                    
                    'users.view',
                    'users.invite',
                    
                    'transactions.view',
                    'transactions.create',
                    'transactions.update',
                    'transactions.delete',
                    'invoices.view',
                    'invoices.create',
                    'invoices.update',
                    'invoices.delete',
                    
                    'customers.view',
                    'customers.create',
                    'customers.update',
                    'customers.delete',
                    'products.view',
                    'products.create',
                    'products.update',
                    'products.delete',
                    
                    'reports.view',
                    'reports.export',
                    
                    'god_engine.access',
                ],
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Manager with access to day-to-day operations',
                'level' => 60,
                'permissions' => [
                    'business.view',
                    
                    'users.view',
                    
                    'transactions.view',
                    'transactions.create',
                    'transactions.update',
                    'invoices.view',
                    'invoices.create',
                    'invoices.update',
                    
                    'customers.view',
                    'customers.create',
                    'customers.update',
                    'products.view',
                    'products.create',
                    'products.update',
                    
                    'reports.view',
                    
                    'god_engine.access',
                ],
            ],
            [
                'name' => 'Accountant',
                'slug' => 'accountant',
                'description' => 'Accountant with financial access only',
                'level' => 50,
                'permissions' => [
                    'business.view',
                    
                    'transactions.view',
                    'transactions.create',
                    'transactions.update',
                    'invoices.view',
                    'invoices.create',
                    'invoices.update',
                    
                    'customers.view',
                    
                    'reports.view',
                    'reports.export',
                ],
            ],
            [
                'name' => 'Employee',
                'slug' => 'employee',
                'description' => 'Basic employee with limited access',
                'level' => 30,
                'permissions' => [
                    'business.view',
                    
                    'transactions.view',
                    'transactions.create',
                    'invoices.view',
                    
                    'customers.view',
                    'customers.create',
                    'products.view',
                ],
            ],
            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access for reports and viewing data',
                'level' => 10,
                'permissions' => [
                    'business.view',
                    'transactions.view',
                    'invoices.view',
                    'customers.view',
                    'products.view',
                    'reports.view',
                ],
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert([
                'name' => $role['name'],
                'slug' => $role['slug'],
                'description' => $role['description'],
                'level' => $role['level'],
                'permissions' => json_encode($role['permissions']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
