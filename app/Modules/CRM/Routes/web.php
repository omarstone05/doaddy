<?php

use Illuminate\Support\Facades\Route;
use App\Modules\CRM\Http\Controllers\CRMDashboardController;
use App\Modules\CRM\Http\Controllers\LeadController;

/*
|--------------------------------------------------------------------------
| CRM Module Routes
|--------------------------------------------------------------------------
|
| Customer Relationship Management routes
|
*/

Route::middleware(['auth', 'verified'])->prefix('crm')->name('crm.')->group(function () {
    // Dashboard
    Route::get('/', [CRMDashboardController::class, 'index'])->name('dashboard');
    
    // Leads
    Route::resource('leads', LeadController::class);
});


