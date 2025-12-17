<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Addy\AddyCacheManager;
use App\Models\Organization;

class AddyCacheCommand extends Command
{
    protected $signature = 'addy:cache 
                            {action : clear, warm, stats}
                            {--org= : Organization ID}';

    protected $description = 'Manage Addy cache';

    public function handle(): int
    {
        $action = $this->argument('action');
        $orgId = $this->option('org');

        match($action) {
            'clear' => $this->clearCache($orgId),
            'warm' => $this->warmCache($orgId),
            'stats' => $this->showStats(),
            default => $this->error("Unknown action: {$action}"),
        };

        return self::SUCCESS;
    }

    protected function clearCache(?int $orgId): void
    {
        if ($orgId) {
            AddyCacheManager::clearOrganization($orgId);
            $this->info("✅ Cleared cache for organization {$orgId}");
        } else {
            AddyCacheManager::clearAll();
            $this->info("✅ Cleared all Addy caches");
        }
    }

    protected function warmCache(?int $orgId): void
    {
        if ($orgId) {
            $this->info("Warming cache for organization {$orgId}...");
            AddyCacheManager::warmUp($orgId);
            $this->info("✅ Cache warmed for organization {$orgId}");
        } else {
            $orgs = Organization::all();
            $this->info("Warming cache for {$orgs->count()} organizations...");
            
            $bar = $this->output->createProgressBar($orgs->count());
            $bar->start();
            
            foreach ($orgs as $org) {
                AddyCacheManager::warmUp($org->id);
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            $this->info("✅ Cache warmed for all organizations");
        }
    }

    protected function showStats(): void
    {
        $stats = AddyCacheManager::getStats();
        
        $this->info("📊 Redis Cache Statistics:");
        $this->table(
            ['Metric', 'Value'],
            collect($stats)->map(fn($value, $key) => [$key, $value])->toArray()
        );
    }
}

