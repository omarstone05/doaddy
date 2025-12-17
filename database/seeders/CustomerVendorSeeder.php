<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomerPersona;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Prospect;
use App\Models\Quotation;
use App\Models\Bill;
use App\Models\Organization;

class CustomerVendorSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();

        if (!$organization) {
            $this->command->error('No organization found. Please create an organization first.');
            return;
        }

        $this->command->info('Seeding Customer & Vendor Management data...');

        // Create Customer Personas
        $this->command->info('Creating customer personas...');
        
        $enterprisePersona = CustomerPersona::create([
            'organization_id' => $organization->id,
            'name' => 'Enterprise Clients',
            'slug' => 'enterprise-clients',
            'description' => 'Large organizations with 500+ employees and complex needs',
            'industry' => 'Technology',
            'size' => 'enterprise',
            'payment_behavior' => 'excellent',
            'color' => '#14b8a6',
            'icon' => '🏢',
            'is_active' => true,
        ]);

        $smbPersona = CustomerPersona::create([
            'organization_id' => $organization->id,
            'name' => 'SMB Partners',
            'slug' => 'smb-partners',
            'description' => 'Small to medium businesses looking for growth',
            'industry' => 'Various',
            'size' => 'medium',
            'payment_behavior' => 'good',
            'color' => '#3b82f6',
            'icon' => '🏪',
            'is_active' => true,
        ]);

        $startupPersona = CustomerPersona::create([
            'organization_id' => $organization->id,
            'name' => 'Startups',
            'slug' => 'startups',
            'description' => 'Early-stage companies with high growth potential',
            'industry' => 'Technology',
            'size' => 'small',
            'payment_behavior' => 'fair',
            'color' => '#8b5cf6',
            'icon' => '🚀',
            'is_active' => true,
        ]);

        // Create Sample Customers
        $this->command->info('Creating sample customers...');

        $customers = [
            [
                'persona_id' => $enterprisePersona->id,
                'name' => 'TechCorp International',
                'email' => 'billing@techcorp.com',
                'phone' => '+260-211-123-4567',
                'type' => 'business',
                'city' => 'Lusaka',
                'country' => 'Zambia',
                'credit_limit' => 500000,
                'payment_terms' => 'net_30',
            ],
            [
                'persona_id' => $enterprisePersona->id,
                'name' => 'Global Finance Ltd',
                'email' => 'accounts@globalfinance.com',
                'phone' => '+260-211-234-5678',
                'type' => 'business',
                'city' => 'Lusaka',
                'country' => 'Zambia',
                'credit_limit' => 750000,
                'payment_terms' => 'net_60',
            ],
            [
                'persona_id' => $smbPersona->id,
                'name' => 'Bright Ideas Marketing',
                'email' => 'hello@brightideas.co.zm',
                'phone' => '+260-211-345-6789',
                'type' => 'business',
                'city' => 'Kitwe',
                'country' => 'Zambia',
                'credit_limit' => 100000,
                'payment_terms' => 'net_30',
            ],
            [
                'persona_id' => $smbPersona->id,
                'name' => 'Fresh Foods Distribution',
                'email' => 'orders@freshfoods.zm',
                'phone' => '+260-211-456-7890',
                'type' => 'business',
                'city' => 'Ndola',
                'country' => 'Zambia',
                'credit_limit' => 150000,
                'payment_terms' => 'net_15',
            ],
            [
                'persona_id' => $startupPersona->id,
                'name' => 'CodeCraft Solutions',
                'email' => 'team@codecraft.zm',
                'phone' => '+260-211-567-8901',
                'type' => 'business',
                'city' => 'Lusaka',
                'country' => 'Zambia',
                'credit_limit' => 50000,
                'payment_terms' => 'net_30',
            ],
        ];

        foreach ($customers as $customerData) {
            Customer::create([
                'organization_id' => $organization->id,
                'customer_persona_id' => $customerData['persona_id'],
                'name' => $customerData['name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'type' => $customerData['type'],
                'city' => $customerData['city'],
                'country' => $customerData['country'],
                'credit_limit' => $customerData['credit_limit'],
                'payment_terms' => $customerData['payment_terms'],
                'currency' => 'USD',
                'status' => 'active',
                'lifetime_value' => rand(10000, 500000),
                'outstanding_balance' => rand(0, 50000),
                'total_orders' => rand(5, 50),
                'first_purchase_date' => now()->subMonths(rand(3, 24)),
                'last_purchase_date' => now()->subDays(rand(1, 30)),
            ]);
        }

        // Create Sample Vendors
        $this->command->info('Creating sample vendors...');

        $vendors = [
            [
                'name' => 'Office Supplies Plus',
                'email' => 'sales@officesupplies.zm',
                'phone' => '+260-211-111-2222',
                'category' => 'supplies',
            ],
            [
                'name' => 'CloudHost Services',
                'email' => 'billing@cloudhost.com',
                'phone' => '+1-555-0123',
                'category' => 'services',
            ],
            [
                'name' => 'ZESCO Power',
                'email' => 'corporate@zesco.co.zm',
                'phone' => '+260-211-222-3333',
                'category' => 'utilities',
            ],
            [
                'name' => 'Legal Partners LLP',
                'email' => 'admin@legalpartners.zm',
                'phone' => '+260-211-333-4444',
                'category' => 'professional_fees',
            ],
        ];

        foreach ($vendors as $vendorData) {
            Vendor::create([
                'organization_id' => $organization->id,
                'name' => $vendorData['name'],
                'email' => $vendorData['email'],
                'phone' => $vendorData['phone'],
                'type' => 'business',
                'payment_terms' => 'net_30',
                'currency' => 'USD',
                'status' => 'active',
                'rating' => ['excellent', 'good', 'fair'][rand(0, 2)],
                'total_spent' => rand(50000, 500000),
                'outstanding_balance' => rand(0, 50000),
            ]);
        }

        // Create Sample Prospects
        $this->command->info('Creating sample prospects...');

        $prospects = [
            [
                'name' => 'John Mwamba',
                'company_name' => 'Zambia Retail Group',
                'stage' => 'qualified',
                'estimated_value' => 85000,
                'probability' => 60,
            ],
            [
                'name' => 'Sarah Phiri',
                'company_name' => 'Digital Africa Agency',
                'stage' => 'proposal',
                'estimated_value' => 120000,
                'probability' => 75,
            ],
            [
                'name' => 'Michael Banda',
                'company_name' => 'Construction Masters',
                'stage' => 'contacted',
                'estimated_value' => 200000,
                'probability' => 40,
            ],
            [
                'name' => 'Grace Lungu',
                'company_name' => 'EduTech Solutions',
                'stage' => 'lead',
                'estimated_value' => 45000,
                'probability' => 25,
            ],
        ];

        foreach ($prospects as $prospectData) {
            Prospect::create([
                'organization_id' => $organization->id,
                'name' => $prospectData['name'],
                'company_name' => $prospectData['company_name'],
                'email' => strtolower(str_replace(' ', '.', $prospectData['name'])) . '@example.com',
                'phone' => '+260-' . rand(211, 977) . '-' . rand(100, 999) . '-' . rand(1000, 9999),
                'stage' => $prospectData['stage'],
                'estimated_value' => $prospectData['estimated_value'],
                'probability' => $prospectData['probability'],
                'expected_close_date' => now()->addDays(rand(30, 90)),
                'source' => ['website', 'referral', 'cold_call', 'social_media'][rand(0, 3)],
                'priority' => ['low', 'medium', 'high'][rand(0, 2)],
                'currency' => 'USD',
                'engagement_score' => rand(30, 90),
                'last_contacted_at' => now()->subDays(rand(1, 14)),
            ]);
        }

        $this->command->info('✓ Customer & Vendor Management data seeded successfully!');
        $this->command->info('');
        $this->command->info('Created:');
        $this->command->info('  - 3 Customer Personas');
        $this->command->info('  - ' . count($customers) . ' Customers');
        $this->command->info('  - ' . count($vendors) . ' Vendors');
        $this->command->info('  - ' . count($prospects) . ' Prospects');
    }
}
