<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
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

        // Create test admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => 'Test Admin',
                'email' => 'admin@test.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create test regular user
        $user = User::firstOrCreate(
            ['email' => 'user@test.com'],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'name' => 'Test User',
                'email' => 'user@test.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Test users created successfully!');
        $this->command->info('Admin: admin@test.com / password');
        $this->command->info('User: user@test.com / password');
    }
}


