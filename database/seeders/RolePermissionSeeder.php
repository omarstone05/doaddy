<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Dashboard
            'view dashboard',
            
            // Money
            'view money accounts',
            'create money accounts',
            'edit money accounts',
            'delete money accounts',
            'view money movements',
            'create money movements',
            'edit money movements',
            'delete money movements',
            'view budgets',
            'create budgets',
            'edit budgets',
            'delete budgets',
            
            // POS
            'view pos',
            'create sales',
            'view sales',
            'void sales',
            'view register sessions',
            'open register',
            'close register',
            
            // Products
            'view products',
            'create products',
            'edit products',
            'delete products',
            
            // Customers
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',
            
            // Team
            'view team',
            'create team members',
            'edit team members',
            'delete team members',
            
            // Reports
            'view reports',
            'export reports',
            
            // Settings
            'view settings',
            'edit settings',
            'manage users',
            'manage roles',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles
        $owner = Role::create(['name' => 'Owner']);
        $manager = Role::create(['name' => 'Manager']);
        $cashier = Role::create(['name' => 'Cashier']);
        $staff = Role::create(['name' => 'Staff']);

        // Assign permissions to Owner (all permissions)
        $owner->givePermissionTo(Permission::all());

        // Assign permissions to Manager
        $manager->givePermissionTo([
            'view dashboard',
            'view money accounts',
            'create money accounts',
            'edit money accounts',
            'view money movements',
            'create money movements',
            'edit money movements',
            'view budgets',
            'create budgets',
            'edit budgets',
            'view pos',
            'create sales',
            'view sales',
            'view register sessions',
            'open register',
            'close register',
            'view products',
            'create products',
            'edit products',
            'view customers',
            'create customers',
            'edit customers',
            'view team',
            'view reports',
            'view settings',
        ]);

        // Assign permissions to Cashier
        $cashier->givePermissionTo([
            'view dashboard',
            'view pos',
            'create sales',
            'view sales',
            'view products',
            'view customers',
            'create customers',
        ]);

        // Assign permissions to Staff
        $staff->givePermissionTo([
            'view dashboard',
            'view products',
            'view customers',
        ]);
    }
}
