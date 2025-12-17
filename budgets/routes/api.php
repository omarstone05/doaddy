<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\BudgetController;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => ['status' => 'ok']);

    Route::middleware('budget.jwt')->group(function () {
        Route::apiResource('budgets', BudgetController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    });
});
