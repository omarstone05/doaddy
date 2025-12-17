<?php

namespace App\Console\Commands;

use App\Jobs\RunAddyDecisionLoop;
use App\Models\Organization;
use Illuminate\Console\Command;

class RunAddyThoughtCycle extends Command
{
    protected $signature = 'addy:think {--org= : Organization ID to process}';

    protected $description = 'Run Addy\'s thought cycle (decision loop)';

    public function handle(): int
    {
        $orgId = $this->option('org');

        if ($orgId) {
            $organization = Organization::find($orgId);
            if (!$organization) {
                $this->error("Organization with ID {$orgId} not found.");
                return self::FAILURE;
            }

            $this->info("Running Addy thought cycle for: {$organization->name}");
            RunAddyDecisionLoop::dispatch($organization);
            $this->info("✓ Decision loop queued for {$organization->name}");
        } else {
            $organizations = Organization::all();

            if ($organizations->isEmpty()) {
                $this->warn("No organizations found.");
                return self::SUCCESS;
            }

            $this->info("Running Addy thought cycle for {$organizations->count()} organization(s)...");

            foreach ($organizations as $organization) {
                RunAddyDecisionLoop::dispatch($organization);
                $this->info("✓ Decision loop queued for: {$organization->name}");
            }

            $this->info("\n✨ All decision loops queued successfully!");
        }

        return self::SUCCESS;
    }
}

