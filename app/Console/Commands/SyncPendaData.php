<?php

namespace App\Console\Commands;

use App\Services\PendaSyncService;
use Illuminate\Console\Command;

class SyncPendaData extends Command
{
    protected $signature = 'penda:sync 
                            {--org= : Sync specific organization ID}
                            {--modules : Sync modules registry only}
                            {--branding : Sync branding only}
                            {--subscriptions : Sync subscriptions only}';

    protected $description = 'Sync data from Penda Cloud (subscriptions, branding, modules)';

    public function handle(PendaSyncService $syncService): int
    {
        $this->info('Starting Penda Cloud sync...');

        $orgId = $this->option('org');
        $modulesOnly = $this->option('modules');
        $brandingOnly = $this->option('branding');
        $subscriptionsOnly = $this->option('subscriptions');

        // Sync modules registry
        if (!$brandingOnly && !$subscriptionsOnly) {
            $this->info('Syncing modules registry...');
            $moduleCount = $syncService->syncModulesRegistry();
            $this->info("  Synced {$moduleCount} modules.");
        }

        if ($modulesOnly) {
            $this->info('Done! (modules only)');
            return Command::SUCCESS;
        }

        // Sync specific organization
        if ($orgId) {
            $this->info("Syncing organization: {$orgId}");
            
            if (!$brandingOnly) {
                $success = $syncService->syncSubscription($orgId);
                $this->info($success ? '  ✓ Subscription synced' : '  ✗ Subscription sync failed');
            }

            if (!$subscriptionsOnly) {
                $success = $syncService->syncBranding($orgId);
                $this->info($success ? '  ✓ Branding synced' : '  ✗ Branding sync failed');
            }

            $this->info('Done!');
            return Command::SUCCESS;
        }

        // Sync all organizations
        $this->info('Syncing all organizations...');
        
        $bar = $this->output->createProgressBar();
        $bar->start();

        $syncedCount = 0;
        $organizations = \DB::table('organizations')->get();
        
        foreach ($organizations as $org) {
            if (!$brandingOnly) {
                $syncService->syncSubscription($org->id);
            }
            if (!$subscriptionsOnly) {
                $syncService->syncBranding($org->id);
            }
            $syncedCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Synced {$syncedCount} organizations.");
        $this->info('Done!');

        return Command::SUCCESS;
    }
}



