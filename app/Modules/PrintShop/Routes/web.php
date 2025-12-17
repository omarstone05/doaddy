<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrintShop\PrintShopController;
use App\Http\Controllers\PrintShop\PrintMaterialController;
use App\Http\Controllers\PrintShop\InkConfigurationController;
use App\Http\Controllers\PrintShop\PricingRuleController;
use App\Http\Controllers\PrintShop\PrintJobController;

/*
|--------------------------------------------------------------------------
| PrintShop Module Routes
|--------------------------------------------------------------------------
|
| Routes for the print shop cost calculator module.
| Access is controlled by the 'module:PrintShop' middleware.
|
*/

Route::middleware(['auth', 'module:PrintShop'])->prefix('print-shop')->name('print-shop.')->group(function () {
    // Dashboard
    Route::get('/', [PrintShopController::class, 'index'])->name('index');
    
    // Materials
    Route::resource('materials', PrintMaterialController::class);
    
    // Ink Configurations
    Route::resource('ink-configs', InkConfigurationController::class);
    Route::post('ink-configs/{inkConfig}/set-default', [InkConfigurationController::class, 'setDefault'])->name('ink-configs.set-default');
    
    // Pricing Rules
    Route::resource('pricing-rules', PricingRuleController::class);
    
    // Print Jobs
    Route::resource('jobs', PrintJobController::class);
    Route::post('jobs/calculate', [PrintJobController::class, 'calculate'])->name('jobs.calculate');
    Route::post('jobs/compare-materials', [PrintJobController::class, 'compareMaterials'])->name('jobs.compare-materials');
    Route::post('jobs/{job}/approve', [PrintJobController::class, 'approve'])->name('jobs.approve');
    Route::post('jobs/{job}/complete', [PrintJobController::class, 'complete'])->name('jobs.complete');
    Route::post('jobs/{job}/status', [PrintJobController::class, 'updateStatus'])->name('jobs.update-status');
    Route::post('jobs/{job}/convert-to-quotation', [PrintJobController::class, 'convertToQuotation'])->name('jobs.convert-to-quotation');
    Route::post('jobs/{job}/convert-to-invoice', [PrintJobController::class, 'convertToInvoice'])->name('jobs.convert-to-invoice');
});

