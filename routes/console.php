<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run Addy's thought cycle daily at 6 AM
Schedule::call(function () {
    $organizations = \App\Models\Organization::all();
    foreach ($organizations as $org) {
        \App\Jobs\RunAddyDecisionLoop::dispatch($org);
    }
})->dailyAt('06:00');

// Generate predictions daily at 7 AM (after thought cycle)
Schedule::call(function () {
    $organizations = \App\Models\Organization::all();
    foreach ($organizations as $org) {
        \App\Jobs\GenerateAddyPredictions::dispatch($org);
    }
})->dailyAt('07:00');
