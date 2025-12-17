<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\OrganizationRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test organization
        $organization = Organization::firstOrCreate(
            ['slug' => 'test-company'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Test Company',
                'slug' => 'test-company',
                'business_type' => 'retail',
                'industry' => 'retail',
                'tone_preference' => 'conversational',
                'currency' => 'ZMW',
                'timezone' => 'Africa/Lusaka',
            ]
        );

        // Get organization roles
        $ownerRole = OrganizationRole::where('slug', 'owner')->first();
        $memberRole = OrganizationRole::where('slug', 'member')->first();

        if (!$ownerRole || !$memberRole) {
            $this->command->error('Organization roles not found. Please run OrganizationRoleSeeder first.');
            return;
        }

        // Create test admin user (as owner)
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Test Admin',
                'email' => 'admin@test.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Attach admin to organization with owner role
        if (!$admin->organizations()->where('organizations.id', $organization->id)->exists()) {
            $admin->organizations()->attach($organization->id, [
                'role' => 'owner',
                'role_id' => $ownerRole->id,
                'is_active' => true,
                'joined_at' => now(),
            ]);
        } else {
            // Update existing pivot
            $admin->organizations()->updateExistingPivot($organization->id, [
                'role' => 'owner',
                'role_id' => $ownerRole->id,
                'is_active' => true,
            ]);
        }

        // Set current organization for admin
        $admin->update(['organization_id' => $organization->id]);

        // Create test regular user (as member)
        $user = User::firstOrCreate(
            ['email' => 'user@test.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Test User',
                'email' => 'user@test.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Attach user to organization with member role
        if (!$user->organizations()->where('organizations.id', $organization->id)->exists()) {
            $user->organizations()->attach($organization->id, [
                'role' => 'member',
                'role_id' => $memberRole->id,
                'is_active' => true,
                'joined_at' => now(),
            ]);
        } else {
            // Update existing pivot
            $user->organizations()->updateExistingPivot($organization->id, [
                'role' => 'member',
                'role_id' => $memberRole->id,
                'is_active' => true,
            ]);
        }

        // Set current organization for user
        $user->update(['organization_id' => $organization->id]);

        $this->command->info('Test users created successfully!');
        $this->command->info('Admin (Owner): admin@test.com / password');
        $this->command->info('Regular User (Member): user@test.com / password');
    }
}


