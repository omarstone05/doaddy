<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\Budget\BudgetServiceClient::class, function ($app) {
            $baseUrl = config('services.budgets.base_url');

            return new \App\Services\Budget\BudgetServiceClient(
                $app->make(\App\Services\Budget\BudgetTokenService::class),
                $baseUrl
            );
        });

        $this->app->bind(\App\Services\Digitax\DigitaxServiceClient::class, function ($app) {
            $baseUrl = config('services.digitax.base_url');

            return new \App\Services\Digitax\DigitaxServiceClient(
                $app->make(\App\Services\Digitax\DigitaxTokenService::class),
                $baseUrl
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
