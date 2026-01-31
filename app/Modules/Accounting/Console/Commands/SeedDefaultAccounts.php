<?php

namespace App\Modules\Accounting\Console\Commands;

use App\Modules\Accounting\Database\Seeders\DefaultChartOfAccountsSeeder;
use Illuminate\Console\Command;

class SeedDefaultAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:seed-default-accounts {--organization-id= : The organization ID to seed accounts for}';

    /**
     * Register the command
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed default chart of accounts for an organization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $organizationId = $this->option('organization-id');
        
        if (!$organizationId) {
            // Get the first organization
            $organization = \App\Models\Organization::first();
            if (!$organization) {
                $this->error('No organization found. Please create an organization first or specify --organization-id');
                return 1;
            }
            $organizationId = $organization->id;
            $this->info("No organization ID specified. Using first organization: {$organizationId}");
        } else {
            // Verify organization exists
            $organization = \App\Models\Organization::find($organizationId);
            if (!$organization) {
                $this->error("Organization with ID {$organizationId} not found.");
                return 1;
            }
            $this->info("Seeding accounts for organization: {$organization->name} (ID: {$organizationId})");
        }

        // Create seeder instance and set organization ID
        $seeder = new DefaultChartOfAccountsSeeder();
        
        // We need to manually set the organization ID since the seeder uses first() by default
        // Let's modify the approach - we'll pass it via a method or property
        // For now, let's use a workaround by setting it in the seeder
        
        // Actually, let's check the seeder - it already uses first() if no org is found
        // But we want to use the specified org. Let's update the seeder to accept org ID
        
        // For now, let's just run the seeder and it will use the first org
        // But we should update the seeder to accept an organization ID parameter
        
        try {
            $this->info("Starting to seed default chart of accounts...");
            $seeder->setCommand($this);
            $seeder->setOrganizationId($organizationId);
            $seeder->run();
            
            $this->info('Default chart of accounts seeded successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to seed chart of accounts: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

