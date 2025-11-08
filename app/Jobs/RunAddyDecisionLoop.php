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

class RunAddyDecisionLoop implements ShouldQueue
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
            Log::info("Running Addy Decision Loop for organization: {$this->organization->name}");

            $addyCore = new AddyCoreService($this->organization);
            $state = $addyCore->runDecisionLoop();

            Log::info("Addy Decision Loop completed", [
                'organization' => $this->organization->name,
                'focus_area' => $state->focus_area,
                'urgency' => $state->urgency,
                'mood' => $state->mood,
            ]);
        } catch (\Exception $e) {
            Log::error("Addy Decision Loop failed", [
                'organization' => $this->organization->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

