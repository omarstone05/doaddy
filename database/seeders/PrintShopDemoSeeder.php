<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Organization;
use App\Models\User;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Print\PrintMaterial;
use App\Models\Print\InkConfiguration;
use App\Models\Print\PricingRule;
use App\Models\Print\PrintJob;

/**
 * Seeder for a demo print shop organization with complete data
 * 
 * Creates:
 * - Print shop organization with PrintShop module enabled
 * - Admin and staff users
 * - Print shop customers
 * - Materials, ink configurations, pricing rules
 * - Active print jobs
 * - Active quotations
 * - Previous invoices (completed transactions)
 */
class PrintShopDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🖨️ Creating Print Shop Demo Account...');
        
        // 1. Create the print shop organization
        $organization = $this->createOrganization();
        
        // 2. Create users
        $users = $this->createUsers($organization);
        
        // 3. Create customers
        $customers = $this->createCustomers($organization);
        
        // 4. Create PrintShop module data
        $materials = $this->createMaterials($organization);
        $inkConfigs = $this->createInkConfigurations($organization);
        $this->attachInkToMaterials($materials, $inkConfigs);
        $pricingRules = $this->createPricingRules($organization, $materials);
        
        // 5. Create print jobs
        $this->createPrintJobs($organization, $customers, $materials, $inkConfigs, $pricingRules, $users['admin']);
        
        // 6. Create quotations
        $this->createQuotations($organization, $customers, $users['admin']);
        
        // 7. Create invoices (completed transactions)
        $this->createInvoices($organization, $customers);
        
        $this->command->info('✅ Print Shop Demo Account created successfully!');
        $this->command->newLine();
        $this->command->info('📧 Login credentials:');
        $this->command->info('   Email: printshop@demo.com');
        $this->command->info('   Password: password');
    }

    private function createOrganization(): Organization
    {
        $organization = Organization::create([
            'id' => Str::uuid(),
            'name' => 'Zambia Print Solutions',
            'slug' => 'zambia-print-solutions',
            'business_type' => 'service',
            'industry' => 'printing',
            'currency' => 'ZMW',
            'timezone' => 'Africa/Lusaka',
            'status' => 'active',
            'business_description' => 'Professional large format printing, signage, and vinyl cutting services in Lusaka, Zambia.',
            'business_category' => 'Printing & Signage',
            'team_size' => '5-10',
            'enabled_modules' => ['print-shop'], // Enable PrintShop module
            'onboarding_completed_at' => now(),
            'settings' => [
                'default_tax_rate' => 16,
                'invoice_prefix' => 'ZPS',
                'quotation_prefix' => 'QUO',
            ],
        ]);
        
        $this->command->info('   ✓ Created organization: ' . $organization->name);
        return $organization;
    }

    private function createUsers(Organization $organization): array
    {
        // Admin user
        $adminId = Str::uuid()->toString();
        $admin = User::create([
            'id' => $adminId,
            'name' => 'Chilufya Mwamba',
            'email' => 'printshop@demo.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'organization_id' => $organization->id,
        ]);
        
        // Directly insert pivot record to avoid observer issues
        \DB::table('organization_user')->insert([
            'organization_id' => $organization->id,
            'user_id' => $adminId,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sales staff
        $salesId = Str::uuid()->toString();
        $sales = User::create([
            'id' => $salesId,
            'name' => 'Bwalya Musonda',
            'email' => 'sales@zambiaprintsolutions.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'organization_id' => $organization->id,
        ]);
        
        \DB::table('organization_user')->insert([
            'organization_id' => $organization->id,
            'user_id' => $salesId,
            'role' => 'staff',
            'is_active' => true,
            'joined_at' => now()->subDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('   ✓ Created 2 users');
        return ['admin' => $admin, 'sales' => $sales];
    }

    private function createCustomers(Organization $organization): array
    {
        $customersData = [
            [
                'name' => 'Shoprite Zambia Ltd',
                'type' => 'business',
                'email' => 'marketing@shoprite.co.zm',
                'phone' => '+260 211 123 456',
                'billing_address' => 'Cairo Road, Lusaka',
                'city' => 'Lusaka',
                'country' => 'Zambia',
                'payment_terms' => 'net_30',
                'status' => 'active',
                'notes' => 'Large retail client - regular signage orders for stores',
            ],
            [
                'name' => 'Zambia National Commercial Bank',
                'type' => 'business',
                'email' => 'procurement@zanaco.co.zm',
                'phone' => '+260 211 228 858',
                'billing_address' => 'Cairo Road, Head Office',
                'city' => 'Lusaka',
                'country' => 'Zambia',
                'payment_terms' => 'net_30',
                'status' => 'active',
                'notes' => 'Banking client - branding materials and ATM signage',
            ],
            [
                'name' => 'Protea Hotel Lusaka',
                'type' => 'business',
                'email' => 'events@proteahotels.co.zm',
                'phone' => '+260 211 254 455',
                'billing_address' => 'Arcades Shopping Centre',
                'city' => 'Lusaka',
                'country' => 'Zambia',
                'payment_terms' => 'net_15',
                'status' => 'active',
                'notes' => 'Hospitality client - event banners and directional signage',
            ],
            [
                'name' => 'Chisamba Farm Fresh',
                'type' => 'business',
                'email' => 'admin@chisambafarm.com',
                'phone' => '+260 955 123 789',
                'billing_address' => 'Chisamba Road',
                'city' => 'Chisamba',
                'country' => 'Zambia',
                'payment_terms' => 'immediate',
                'status' => 'active',
                'notes' => 'Agriculture client - vehicle wraps and farm signage',
            ],
            [
                'name' => 'Manda Hill Shopping Centre',
                'type' => 'business',
                'email' => 'management@mandahill.co.zm',
                'phone' => '+260 211 256 789',
                'billing_address' => 'Great East Road',
                'city' => 'Lusaka',
                'country' => 'Zambia',
                'payment_terms' => 'net_30',
                'status' => 'active',
                'notes' => 'Mall client - directional signs, window graphics, promotional banners',
            ],
            [
                'name' => 'James Phiri',
                'type' => 'individual',
                'email' => 'jamesphiri@gmail.com',
                'phone' => '+260 977 456 123',
                'billing_address' => 'Kabulonga',
                'city' => 'Lusaka',
                'country' => 'Zambia',
                'payment_terms' => 'immediate',
                'status' => 'active',
                'notes' => 'Individual client - personal business signage',
            ],
        ];

        $customers = [];
        foreach ($customersData as $data) {
            $customer = Customer::create(array_merge($data, [
                'id' => Str::uuid(),
                'organization_id' => $organization->id,
            ]));
            $customers[] = $customer;
        }

        $this->command->info('   ✓ Created ' . count($customers) . ' customers');
        return $customers;
    }

    private function createMaterials(Organization $organization): array
    {
        $materialsData = [
            [
                'name' => 'Standard Vinyl (White)',
                'material_type' => 'vinyl',
                'roll_width' => 1.0,
                'roll_length' => 40.0,
                'material_cost' => 1400.00,
                'off_cut_cost' => 7.00,
                'notes' => 'Standard white vinyl for indoor/outdoor signage - most popular',
            ],
            [
                'name' => 'Vinyl (Colored)',
                'material_type' => 'vinyl',
                'roll_width' => 1.0,
                'roll_length' => 40.0,
                'material_cost' => 1600.00,
                'off_cut_cost' => 7.00,
                'notes' => 'Colored vinyl base - premium option',
            ],
            [
                'name' => 'Banner Material 440gsm',
                'material_type' => 'banner',
                'roll_width' => 1.0,
                'roll_length' => 40.0,
                'material_cost' => 1700.00,
                'off_cut_cost' => 7.00,
                'notes' => 'Standard PVC banner - durable outdoor use',
            ],
            [
                'name' => 'Banner Flex (Backlit)',
                'material_type' => 'banner_flex',
                'roll_width' => 0.9,
                'roll_length' => 40.0,
                'material_cost' => 2100.00,
                'off_cut_cost' => 7.00,
                'notes' => 'Backlit banner flex for lightbox applications',
            ],
            [
                'name' => 'Contra Vision (One-Way)',
                'material_type' => 'contra_vision',
                'roll_width' => 1.2,
                'roll_length' => 50.0,
                'material_cost' => 2600.00,
                'off_cut_cost' => 0.00,
                'notes' => 'One-way vision film for windows - see out, graphic outside',
            ],
            [
                'name' => 'Clear Vinyl (Transparent)',
                'material_type' => 'clear_vinyl',
                'roll_width' => 1.2,
                'roll_length' => 50.0,
                'material_cost' => 1900.00,
                'off_cut_cost' => 0.00,
                'notes' => 'Transparent vinyl for glass door graphics',
            ],
            [
                'name' => 'Vehicle Wrap Vinyl',
                'material_type' => 'vinyl',
                'roll_width' => 1.5,
                'roll_length' => 25.0,
                'material_cost' => 3500.00,
                'off_cut_cost' => 15.00,
                'notes' => 'Premium cast vinyl for vehicle wraps - conformable',
            ],
        ];

        $materials = [];
        foreach ($materialsData as $data) {
            $material = PrintMaterial::create(array_merge($data, [
                'organization_id' => $organization->id,
                'is_active' => true,
            ]));
            $materials[$data['name']] = $material;
        }

        $this->command->info('   ✓ Created ' . count($materials) . ' print materials');
        return $materials;
    }

    private function createInkConfigurations(Organization $organization): array
    {
        $inkData = [
            [
                'name' => 'Standard Eco-Solvent (4 Bottles)',
                'bottles_per_set' => 4,
                'cost_per_set' => 1400.00,
                'coverage_area' => 50.0,
                'coverage_multiplier' => 1,
                'is_default' => true,
                'notes' => 'Standard eco-solvent ink - CMYK for most jobs',
            ],
            [
                'name' => 'Premium Eco-Solvent (High Coverage)',
                'bottles_per_set' => 4,
                'cost_per_set' => 1400.00,
                'coverage_area' => 60.0,
                'coverage_multiplier' => 3,
                'is_default' => false,
                'notes' => 'High coverage ink for contra vision and specialty materials',
            ],
            [
                'name' => 'UV Ink Set',
                'bottles_per_set' => 6,
                'cost_per_set' => 2200.00,
                'coverage_area' => 45.0,
                'coverage_multiplier' => 1,
                'is_default' => false,
                'notes' => 'UV-curable ink for rigid substrates',
            ],
        ];

        $inkConfigs = [];
        foreach ($inkData as $data) {
            $ink = InkConfiguration::create(array_merge($data, [
                'organization_id' => $organization->id,
            ]));
            $inkConfigs[$data['name']] = $ink;
        }

        $this->command->info('   ✓ Created ' . count($inkConfigs) . ' ink configurations');
        return $inkConfigs;
    }

    private function attachInkToMaterials(array $materials, array $inkConfigs): void
    {
        $standardInk = $inkConfigs['Standard Eco-Solvent (4 Bottles)'];
        $premiumInk = $inkConfigs['Premium Eco-Solvent (High Coverage)'];

        // Standard materials use standard ink
        foreach (['Standard Vinyl (White)', 'Vinyl (Colored)', 'Banner Material 440gsm', 'Banner Flex (Backlit)', 'Vehicle Wrap Vinyl'] as $name) {
            if (isset($materials[$name])) {
                $materials[$name]->inkConfigurations()->attach($standardInk->id);
            }
        }

        // Specialty materials use premium ink
        foreach (['Contra Vision (One-Way)', 'Clear Vinyl (Transparent)'] as $name) {
            if (isset($materials[$name])) {
                $materials[$name]->inkConfigurations()->attach($premiumInk->id);
            }
        }
    }

    private function createPricingRules(Organization $organization, array $materials): array
    {
        $rules = [];

        // Standard pricing
        $rules['standard'] = PricingRule::create([
            'organization_id' => $organization->id,
            'rule_name' => 'Standard Rate (K150/sqm)',
            'markup_type' => 'fixed_price',
            'markup_value' => 150.00,
            'is_default' => true,
            'is_active' => true,
            'priority' => 1,
        ]);

        // Premium material pricing
        if (isset($materials['Contra Vision (One-Way)'])) {
            $rules['contra_vision'] = PricingRule::create([
                'organization_id' => $organization->id,
                'print_material_id' => $materials['Contra Vision (One-Way)']->id,
                'rule_name' => 'Contra Vision Premium (K350/sqm)',
                'markup_type' => 'fixed_price',
                'markup_value' => 350.00,
                'is_default' => false,
                'is_active' => true,
                'priority' => 2,
            ]);
        }

        if (isset($materials['Vehicle Wrap Vinyl'])) {
            $rules['vehicle_wrap'] = PricingRule::create([
                'organization_id' => $organization->id,
                'print_material_id' => $materials['Vehicle Wrap Vinyl']->id,
                'rule_name' => 'Vehicle Wrap (K400/sqm)',
                'markup_type' => 'fixed_price',
                'markup_value' => 400.00,
                'is_default' => false,
                'is_active' => true,
                'priority' => 2,
            ]);
        }

        // Volume discounts
        $rules['bulk_10'] = PricingRule::create([
            'organization_id' => $organization->id,
            'rule_name' => 'Bulk Discount (10+ sqm)',
            'markup_type' => 'fixed_price',
            'markup_value' => 140.00,
            'min_area' => 10.0,
            'is_default' => false,
            'is_active' => true,
            'priority' => 3,
        ]);

        $rules['bulk_50'] = PricingRule::create([
            'organization_id' => $organization->id,
            'rule_name' => 'Large Order (50+ sqm)',
            'markup_type' => 'fixed_price',
            'markup_value' => 120.00,
            'min_area' => 50.0,
            'is_default' => false,
            'is_active' => true,
            'priority' => 4,
        ]);

        $this->command->info('   ✓ Created ' . count($rules) . ' pricing rules');
        return $rules;
    }

    private function createPrintJobs(Organization $organization, array $customers, array $materials, array $inkConfigs, array $pricingRules, User $createdBy): void
    {
        $vinyl = $materials['Standard Vinyl (White)'];
        $banner = $materials['Banner Material 440gsm'];
        $contraVision = $materials['Contra Vision (One-Way)'];
        $vehicleWrap = $materials['Vehicle Wrap Vinyl'];
        $standardInk = $inkConfigs['Standard Eco-Solvent (4 Bottles)'];
        $premiumInk = $inkConfigs['Premium Eco-Solvent (High Coverage)'];

        $jobsData = [
            // Completed jobs (past)
            [
                'customer_id' => $customers[0]->id, // Shoprite
                'print_material_id' => $vinyl->id,
                'ink_configuration_id' => $standardInk->id,
                'pricing_rule_id' => $pricingRules['bulk_10']->id,
                'width' => 3.0,
                'height' => 1.5,
                'quantity' => 5,
                'material_unit_cost' => 35.00,
                'ink_unit_cost' => 28.00,
                'off_cut_cost' => 7.00,
                'price_per_sqm' => 140.00,
                'setup_cost' => 150.00,
                'finishing_cost' => 200.00,
                'status' => 'completed',
                'notes' => 'Promotional banners for new store opening - Kalingalinga',
                'created_at' => now()->subDays(45),
                'completed_at' => now()->subDays(40),
            ],
            [
                'customer_id' => $customers[1]->id, // ZANACO
                'print_material_id' => $vinyl->id,
                'ink_configuration_id' => $standardInk->id,
                'pricing_rule_id' => $pricingRules['standard']->id,
                'width' => 0.8,
                'height' => 1.2,
                'quantity' => 20,
                'material_unit_cost' => 35.00,
                'ink_unit_cost' => 28.00,
                'off_cut_cost' => 7.00,
                'price_per_sqm' => 150.00,
                'setup_cost' => 100.00,
                'status' => 'completed',
                'notes' => 'ATM directional stickers for branches',
                'created_at' => now()->subDays(30),
                'completed_at' => now()->subDays(25),
            ],
            [
                'customer_id' => $customers[2]->id, // Protea Hotel
                'print_material_id' => $banner->id,
                'ink_configuration_id' => $standardInk->id,
                'pricing_rule_id' => $pricingRules['bulk_10']->id,
                'width' => 4.0,
                'height' => 2.0,
                'quantity' => 3,
                'material_unit_cost' => 42.50,
                'ink_unit_cost' => 28.00,
                'off_cut_cost' => 7.00,
                'price_per_sqm' => 140.00,
                'setup_cost' => 100.00,
                'finishing_cost' => 300.00,
                'notes' => 'Conference event banners with grommets',
                'status' => 'completed',
                'created_at' => now()->subDays(20),
                'completed_at' => now()->subDays(15),
            ],
            // In-progress jobs
            [
                'customer_id' => $customers[4]->id, // Manda Hill
                'print_material_id' => $contraVision->id,
                'ink_configuration_id' => $premiumInk->id,
                'pricing_rule_id' => $pricingRules['contra_vision']->id,
                'width' => 2.5,
                'height' => 3.0,
                'quantity' => 8,
                'material_unit_cost' => 43.33,
                'ink_unit_cost' => 7.78,
                'off_cut_cost' => 0,
                'price_per_sqm' => 350.00,
                'setup_cost' => 200.00,
                'finishing_cost' => 500.00,
                'notes' => 'Window graphics for main entrance - Mall renovation',
                'status' => 'in_progress',
                'approved_at' => now()->subDays(3),
                'created_at' => now()->subDays(5),
            ],
            [
                'customer_id' => $customers[3]->id, // Chisamba Farm
                'print_material_id' => $vehicleWrap->id,
                'ink_configuration_id' => $standardInk->id,
                'pricing_rule_id' => $pricingRules['vehicle_wrap']->id,
                'width' => 6.0,
                'height' => 2.5,
                'quantity' => 1,
                'material_unit_cost' => 93.33,
                'ink_unit_cost' => 28.00,
                'off_cut_cost' => 15.00,
                'price_per_sqm' => 400.00,
                'setup_cost' => 500.00,
                'finishing_cost' => 0,
                'delivery_cost' => 200.00,
                'notes' => 'Full delivery truck wrap - company branding',
                'status' => 'in_progress',
                'approved_at' => now()->subDays(2),
                'created_at' => now()->subDays(7),
            ],
            // Approved jobs (waiting to start)
            [
                'customer_id' => $customers[0]->id, // Shoprite
                'print_material_id' => $banner->id,
                'ink_configuration_id' => $standardInk->id,
                'pricing_rule_id' => $pricingRules['bulk_50']->id,
                'width' => 3.0,
                'height' => 1.0,
                'quantity' => 25,
                'material_unit_cost' => 42.50,
                'ink_unit_cost' => 28.00,
                'off_cut_cost' => 7.00,
                'price_per_sqm' => 120.00,
                'setup_cost' => 150.00,
                'finishing_cost' => 500.00,
                'notes' => 'Monthly promotional banners - all Lusaka stores',
                'status' => 'approved',
                'approved_at' => now()->subDay(),
                'created_at' => now()->subDays(3),
            ],
            // Quoted jobs (awaiting approval)
            [
                'customer_id' => $customers[1]->id, // ZANACO
                'print_material_id' => $contraVision->id,
                'ink_configuration_id' => $premiumInk->id,
                'pricing_rule_id' => $pricingRules['contra_vision']->id,
                'width' => 2.0,
                'height' => 2.5,
                'quantity' => 15,
                'material_unit_cost' => 43.33,
                'ink_unit_cost' => 7.78,
                'off_cut_cost' => 0,
                'price_per_sqm' => 350.00,
                'setup_cost' => 300.00,
                'finishing_cost' => 600.00,
                'notes' => 'Branch window graphics - privacy film with branding',
                'status' => 'quoted',
                'quoted_at' => now()->subDays(2),
                'created_at' => now()->subDays(4),
            ],
            // Draft jobs
            [
                'customer_id' => $customers[5]->id, // James Phiri
                'print_material_id' => $vinyl->id,
                'ink_configuration_id' => $standardInk->id,
                'pricing_rule_id' => $pricingRules['standard']->id,
                'width' => 1.0,
                'height' => 0.5,
                'quantity' => 2,
                'material_unit_cost' => 35.00,
                'ink_unit_cost' => 28.00,
                'off_cut_cost' => 7.00,
                'price_per_sqm' => 150.00,
                'setup_cost' => 50.00,
                'notes' => 'Business door signage - draft pricing',
                'status' => 'draft',
                'created_at' => now(),
            ],
        ];

        foreach ($jobsData as $data) {
            PrintJob::create(array_merge($data, [
                'organization_id' => $organization->id,
                'created_by' => $createdBy->id,
            ]));
        }

        $this->command->info('   ✓ Created ' . count($jobsData) . ' print jobs');
    }

    private function createQuotations(Organization $organization, array $customers, User $createdBy): void
    {
        $quotationsData = [
            [
                'customer_id' => $customers[0]->id, // Shoprite
                'title' => 'Annual Signage Package - All Lusaka Stores',
                'description' => 'Comprehensive signage package including directional signs, promotional displays, and window graphics for all 12 Lusaka stores.',
                'status' => 'sent',
                'issue_date' => now()->subDays(5),
                'valid_until' => now()->addDays(25),
                'sent_at' => now()->subDays(5),
                'conversion_probability' => 75.00,
                'terms_and_conditions' => "1. 50% deposit required to commence work\n2. Balance due on completion\n3. Installation included for Lusaka area\n4. 1-year warranty on materials",
                'payment_terms' => 'Net 30 days from invoice date',
                'items' => [
                    ['name' => 'Directional Vinyl Signs (60x90cm)', 'quantity' => 48, 'unit_price' => 180.00],
                    ['name' => 'Large Format Promotional Banners (3x1.5m)', 'quantity' => 24, 'unit_price' => 850.00],
                    ['name' => 'Window Graphics per sqm', 'quantity' => 100, 'unit_price' => 150.00],
                    ['name' => 'Installation Services', 'quantity' => 1, 'unit_price' => 5000.00],
                ],
            ],
            [
                'customer_id' => $customers[1]->id, // ZANACO
                'title' => 'Branch Rebranding Package',
                'description' => 'Complete rebranding package for 5 branches including exterior signage, ATM branding, and interior directional signs.',
                'status' => 'viewed',
                'issue_date' => now()->subDays(3),
                'valid_until' => now()->addDays(27),
                'sent_at' => now()->subDays(3),
                'viewed_at' => now()->subDays(1),
                'conversion_probability' => 60.00,
                'terms_and_conditions' => "1. Design approval required before production\n2. 40% deposit, 60% on completion\n3. Installation scheduled per branch\n4. Materials warranty: 3 years outdoor, 5 years indoor",
                'payment_terms' => 'Net 30 days',
                'items' => [
                    ['name' => 'External Building Signage (LED)', 'quantity' => 5, 'unit_price' => 8500.00],
                    ['name' => 'ATM Surround Branding', 'quantity' => 15, 'unit_price' => 1200.00],
                    ['name' => 'Interior Directional Signs', 'quantity' => 50, 'unit_price' => 250.00],
                    ['name' => 'Design & Artwork Services', 'quantity' => 1, 'unit_price' => 3500.00],
                ],
            ],
            [
                'customer_id' => $customers[4]->id, // Manda Hill
                'title' => 'Holiday Season Promotional Materials',
                'description' => 'Christmas and New Year promotional materials including hanging banners, floor graphics, and window displays.',
                'status' => 'draft',
                'issue_date' => now(),
                'valid_until' => now()->addDays(14),
                'conversion_probability' => 85.00,
                'terms_and_conditions' => "1. Rush delivery available (+15%)\n2. Installation during off-peak hours\n3. Removal after season included",
                'payment_terms' => 'Net 15 days',
                'items' => [
                    ['name' => 'Hanging Banners (1.5x3m)', 'quantity' => 20, 'unit_price' => 650.00],
                    ['name' => 'Floor Graphics (per sqm)', 'quantity' => 50, 'unit_price' => 180.00],
                    ['name' => 'Window Clings (per sqm)', 'quantity' => 30, 'unit_price' => 160.00],
                    ['name' => 'Installation & Removal', 'quantity' => 1, 'unit_price' => 4000.00],
                ],
            ],
            [
                'customer_id' => $customers[2]->id, // Protea Hotel
                'title' => 'Conference Room Signage Upgrade',
                'description' => 'Modern wayfinding and conference room signage with interchangeable name plates.',
                'status' => 'sent',
                'issue_date' => now()->subDays(7),
                'valid_until' => now()->addDays(23),
                'sent_at' => now()->subDays(7),
                'conversion_probability' => 50.00,
                'terms_and_conditions' => "1. Site survey included\n2. Mockups provided for approval\n3. 2-week lead time from approval",
                'payment_terms' => 'Net 15 days',
                'items' => [
                    ['name' => 'Conference Room Door Signs', 'quantity' => 8, 'unit_price' => 450.00],
                    ['name' => 'Directional Wayfinding Signs', 'quantity' => 12, 'unit_price' => 380.00],
                    ['name' => 'Reception Backdrop (3x2m)', 'quantity' => 1, 'unit_price' => 2800.00],
                    ['name' => 'Design Services', 'quantity' => 1, 'unit_price' => 1500.00],
                ],
            ],
        ];

        foreach ($quotationsData as $data) {
            $items = $data['items'];
            unset($data['items']);
            
            // Calculate totals
            $subtotal = collect($items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);
            $taxAmount = $subtotal * 0.16; // 16% VAT
            
            $quotation = Quotation::create(array_merge($data, [
                'organization_id' => $organization->id,
                'created_by' => $createdBy->id,
                'subtotal' => $subtotal,
                'tax_percentage' => 16.00,
                'tax_amount' => $taxAmount,
                'total' => $subtotal + $taxAmount,
                'currency' => 'ZMW',
            ]));

            // Create quotation items
            foreach ($items as $index => $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'order' => $index + 1,
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit' => 'pcs',
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        }

        $this->command->info('   ✓ Created ' . count($quotationsData) . ' quotations');
    }

    private function createInvoices(Organization $organization, array $customers): void
    {
        $invoicesData = [
            // Paid invoices (completed transactions)
            [
                'customer_id' => $customers[0]->id, // Shoprite
                'invoice_date' => now()->subDays(60),
                'due_date' => now()->subDays(30),
                'status' => 'paid',
                'paid_amount' => 28420.00,
                'paid_at' => now()->subDays(35),
                'notes' => 'Store opening promotional materials - Kalingalinga branch',
                'items' => [
                    ['name' => 'Vinyl Banners (3x1.5m) x5', 'quantity' => 1, 'unit_price' => 3150.00],
                    ['name' => 'Setup Fee', 'quantity' => 1, 'unit_price' => 150.00],
                    ['name' => 'Finishing (Grommets, Hemming)', 'quantity' => 1, 'unit_price' => 200.00],
                ],
            ],
            [
                'customer_id' => $customers[1]->id, // ZANACO
                'invoice_date' => now()->subDays(45),
                'due_date' => now()->subDays(15),
                'status' => 'paid',
                'paid_amount' => 4480.00,
                'paid_at' => now()->subDays(20),
                'notes' => 'ATM directional stickers x20',
                'items' => [
                    ['name' => 'Vinyl Stickers (0.8x1.2m) x20', 'quantity' => 20, 'unit_price' => 144.00],
                    ['name' => 'Setup Fee', 'quantity' => 1, 'unit_price' => 100.00],
                    ['name' => 'Cutting & Finishing', 'quantity' => 20, 'unit_price' => 25.00],
                ],
            ],
            [
                'customer_id' => $customers[2]->id, // Protea Hotel
                'invoice_date' => now()->subDays(30),
                'due_date' => now()->subDays(15),
                'status' => 'paid',
                'paid_amount' => 5220.00,
                'paid_at' => now()->subDays(12),
                'notes' => 'Conference event banners x3',
                'items' => [
                    ['name' => 'Banner Material (4x2m) x3', 'quantity' => 3, 'unit_price' => 1120.00],
                    ['name' => 'Setup Fee', 'quantity' => 1, 'unit_price' => 100.00],
                    ['name' => 'Finishing (Heavy-duty grommets)', 'quantity' => 1, 'unit_price' => 300.00],
                    ['name' => 'Banner Stands (Rental)', 'quantity' => 3, 'unit_price' => 200.00],
                ],
            ],
            // Sent (partial payment received - tracked via paid_amount)
            [
                'customer_id' => $customers[4]->id, // Manda Hill
                'invoice_date' => now()->subDays(20),
                'due_date' => now()->addDays(10),
                'status' => 'sent',
                'paid_amount' => 15000.00,
                'paid_at' => now()->subDays(15),
                'notes' => '50% deposit received for entrance window graphics project - balance due on completion',
                'items' => [
                    ['name' => 'Contra Vision Graphics (2.5x3m) x8', 'quantity' => 8, 'unit_price' => 2625.00],
                    ['name' => 'Setup & Design', 'quantity' => 1, 'unit_price' => 200.00],
                    ['name' => 'Professional Installation', 'quantity' => 1, 'unit_price' => 500.00],
                ],
            ],
            // Sent (awaiting payment)
            [
                'customer_id' => $customers[3]->id, // Chisamba Farm
                'invoice_date' => now()->subDays(10),
                'due_date' => now()->addDays(20),
                'status' => 'sent',
                'paid_amount' => 0,
                'notes' => '50% deposit for vehicle wrap project',
                'items' => [
                    ['name' => 'Vehicle Wrap Deposit (50%)', 'quantity' => 1, 'unit_price' => 4350.00],
                ],
            ],
            // Overdue
            [
                'customer_id' => $customers[5]->id, // James Phiri
                'invoice_date' => now()->subDays(45),
                'due_date' => now()->subDays(15),
                'status' => 'overdue',
                'paid_amount' => 0,
                'notes' => 'Business signage - follow up required',
                'items' => [
                    ['name' => 'Shop Front Vinyl Sign (2x0.8m)', 'quantity' => 1, 'unit_price' => 480.00],
                    ['name' => 'Door Operating Hours Decal', 'quantity' => 1, 'unit_price' => 120.00],
                    ['name' => 'Installation', 'quantity' => 1, 'unit_price' => 150.00],
                ],
            ],
        ];

        foreach ($invoicesData as $data) {
            $items = $data['items'];
            unset($data['items']);
            
            // Calculate totals
            $subtotal = collect($items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);
            $taxAmount = $subtotal * 0.16; // 16% VAT
            $totalAmount = $subtotal + $taxAmount;
            
            // If paid_amount is set to total, use the calculated total
            if ($data['status'] === 'paid') {
                $data['paid_amount'] = $totalAmount;
            }

            $invoice = Invoice::create(array_merge($data, [
                'id' => Str::uuid(),
                'organization_id' => $organization->id,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]));

            // Create invoice items
            foreach ($items as $index => $item) {
                InvoiceItem::create([
                    'id' => Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                    'display_order' => $index,
                ]);
            }
        }

        $this->command->info('   ✓ Created ' . count($invoicesData) . ' invoices');
    }
}

