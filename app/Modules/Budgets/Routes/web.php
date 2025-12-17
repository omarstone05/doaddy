<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Budgets\Http\Controllers\BudgetLineController;

Route::middleware(['auth', 'verified'])->name('budgets.')->group(function () {
    Route::resource('budgets', BudgetLineController::class);
});

