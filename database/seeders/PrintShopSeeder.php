<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Print\PrintMaterial;
use App\Models\Print\InkConfiguration;
use App\Models\Print\PricingRule;

class PrintShopSeeder extends Seeder
{
    public function run(): void
    {
        $organizationId = 1; // Default organization ID - adjust as needed

        // Create Standard Ink Configuration
        $standardInk = InkConfiguration::create([
            'organization_id' => $organizationId,
            'name' => 'Standard Ink Set (4 Bottles)',
            'bottles_per_set' => 4,
            'cost_per_set' => 1400.00,
            'coverage_area' => 50.0,
            'coverage_multiplier' => 1,
            'is_default' => true,
            'notes' => 'Standard ink configuration for vinyl, banner, and banner flex materials',
        ]);

        // Create Special Ink Configuration for Contra Vision
        $specialInk = InkConfiguration::create([
            'organization_id' => $organizationId,
            'name' => 'Special Ink (3x Coverage)',
            'bottles_per_set' => 4,
            'cost_per_set' => 1400.00,
            'coverage_area' => 60.0,
            'coverage_multiplier' => 3,
            'is_default' => false,
            'notes' => 'Special ink configuration for contra vision and clear vinyl materials with 3x coverage',
        ]);

        // 1. Vinyl
        $vinyl = PrintMaterial::create([
            'organization_id' => $organizationId,
            'name' => 'Standard Vinyl',
            'material_type' => 'vinyl',
            'roll_width' => 1.0,
            'roll_length' => 40.0,
            'material_cost' => 1400.00,
            'off_cut_cost' => 7.00,
            'is_active' => true,
            'notes' => 'Standard vinyl for indoor/outdoor signage',
        ]);
        $vinyl->inkConfigurations()->attach($standardInk->id);

        // 2. Banner
        $banner = PrintMaterial::create([
            'organization_id' => $organizationId,
            'name' => 'Standard Banner',
            'material_type' => 'banner',
            'roll_width' => 1.0,
            'roll_length' => 40.0,
            'material_cost' => 1700.00,
            'off_cut_cost' => 7.00,
            'is_active' => true,
            'notes' => 'Standard PVC banner material',
        ]);
        $banner->inkConfigurations()->attach($standardInk->id);

        // 3. Banner Flex
        $bannerFlex = PrintMaterial::create([
            'organization_id' => $organizationId,
            'name' => 'Banner Flex',
            'material_type' => 'banner_flex',
            'roll_width' => 0.9,
            'roll_length' => 40.0,
            'material_cost' => 1700.00,
            'off_cut_cost' => 7.00,
            'is_active' => true,
            'notes' => 'Flexible banner material for curved surfaces',
        ]);
        $bannerFlex->inkConfigurations()->attach($standardInk->id);

        // 4. Contra Vision
        $contraVision = PrintMaterial::create([
            'organization_id' => $organizationId,
            'name' => 'Contra Vision',
            'material_type' => 'contra_vision',
            'roll_width' => 1.2,
            'roll_length' => 50.0,
            'material_cost' => 2600.00,
            'off_cut_cost' => 0.00,
            'is_active' => true,
            'notes' => 'One-way vision material for windows',
        ]);
        $contraVision->inkConfigurations()->attach($specialInk->id);

        // 5. Clear Vinyl
        $clearVinyl = PrintMaterial::create([
            'organization_id' => $organizationId,
            'name' => 'Clear Vinyl',
            'material_type' => 'clear_vinyl',
            'roll_width' => 1.2,
            'roll_length' => 50.0,
            'material_cost' => 1900.00,
            'off_cut_cost' => 0.00,
            'is_active' => true,
            'notes' => 'Transparent vinyl for glass applications',
        ]);
        $clearVinyl->inkConfigurations()->attach($specialInk->id);

        // Create Standard Pricing Rule (K150/sqm)
        PricingRule::create([
            'organization_id' => $organizationId,
            'rule_name' => 'Standard Pricing (K150/sqm)',
            'markup_type' => 'fixed_price',
            'markup_value' => 150.00,
            'is_default' => true,
            'is_active' => true,
            'priority' => 1,
        ]);

        // Create Premium Materials Pricing (K350/sqm) for Contra Vision & Clear Vinyl
        PricingRule::create([
            'organization_id' => $organizationId,
            'print_material_id' => $contraVision->id,
            'rule_name' => 'Contra Vision Premium (K350/sqm)',
            'markup_type' => 'fixed_price',
            'markup_value' => 350.00,
            'is_default' => false,
            'is_active' => true,
            'priority' => 2,
        ]);

        PricingRule::create([
            'organization_id' => $organizationId,
            'print_material_id' => $clearVinyl->id,
            'rule_name' => 'Clear Vinyl Premium (K300/sqm)',
            'markup_type' => 'fixed_price',
            'markup_value' => 300.00,
            'is_default' => false,
            'is_active' => true,
            'priority' => 2,
        ]);

        // Create Volume-based pricing (Bulk Discount)
        PricingRule::create([
            'organization_id' => $organizationId,
            'rule_name' => 'Bulk Discount (10+ sqm)',
            'markup_type' => 'fixed_price',
            'markup_value' => 140.00,
            'min_area' => 10.0,
            'is_default' => false,
            'is_active' => true,
            'priority' => 3,
        ]);

        // Create Large Order Discount
        PricingRule::create([
            'organization_id' => $organizationId,
            'rule_name' => 'Large Order Discount (50+ sqm)',
            'markup_type' => 'fixed_price',
            'markup_value' => 120.00,
            'min_area' => 50.0,
            'is_default' => false,
            'is_active' => true,
            'priority' => 4,
        ]);

        $this->command->info('Print Shop seeder completed successfully!');
        $this->command->info('Created:');
        $this->command->info('- 2 ink configurations');
        $this->command->info('- 5 print materials');
        $this->command->info('- 5 pricing rules');
    }
}

