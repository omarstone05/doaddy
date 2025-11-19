<?php

use Illuminate\Support\Facades\Route;
use App\Modules\HR\Http\Controllers\HRDashboardController;
use App\Modules\HR\Http\Controllers\EmployeeController;

Route::middleware(['auth', 'verified', 'organization'])->prefix('hr')->name('hr.')->group(function () {
    // HR Dashboard
    Route::get('/dashboard', [HRDashboardController::class, 'index'])->name('dashboard');
    
    // Employees
    Route::resource('employees', EmployeeController::class);
    
    // More routes will be added as we build out the module
});

