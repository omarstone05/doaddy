<?php

namespace App\Console\Commands;

use App\Jobs\GenerateAddyPredictions;
use App\Models\Organization;
use Illuminate\Console\Command;

class GeneratePredictions extends Command
{
    protected $signature = 'addy:predict {--org= : Organization ID}';

    protected $description = 'Generate Addy predictions for organizations';

    public function handle(): int
    {
        $orgId = $this->option('org');

        if ($orgId) {
            $org = Organization::find($orgId);
            if (!$org) {
                $this->error("Organization {$orgId} not found.");
                return self::FAILURE;
            }

            $this->info("Generating predictions for: {$org->name}");
            GenerateAddyPredictions::dispatch($org);
            
        } else {
            $orgs = Organization::all();
            $this->info("Generating predictions for {$orgs->count()} organization(s)...");

            foreach ($orgs as $org) {
                GenerateAddyPredictions::dispatch($org);
                $this->info("✓ Queued: {$org->name}");
            }
        }

        $this->info("\n✨ Predictions generation queued!");
        return self::SUCCESS;
    }
}

