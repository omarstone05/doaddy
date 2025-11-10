<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\Addy\AddyPredictiveEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAddyPredictions implements ShouldQueue
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
            Log::info("Generating predictions for: {$this->organization->name}");

            $engine = new AddyPredictiveEngine($this->organization);
            $engine->generatePredictions();

            Log::info("Predictions generated successfully for: {$this->organization->name}");

        } catch (\Exception $e) {
            Log::error("Failed to generate predictions", [
                'organization' => $this->organization->name,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

