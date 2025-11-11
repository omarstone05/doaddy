<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\Addy\AddyCoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RegenerateAddyInsights implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Organization $organization;

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }

    public function handle(): void
    {
        try {
            $addyCore = new AddyCoreService($this->organization);
            $addyCore->regenerateInsights();
            
            Log::debug("Regenerated Addy insights for organization: {$this->organization->name}");
        } catch (\Exception $e) {
            Log::error("Failed to regenerate Addy insights", [
                'organization' => $this->organization->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

