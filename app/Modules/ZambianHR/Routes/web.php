<?php

use Illuminate\Support\Facades\Route;
use App\Modules\ZambianHR\Http\Controllers\ZambianHRDashboardController;
use App\Modules\ZambianHR\Http\Controllers\FuneralGrantController;
use App\Modules\ZambianHR\Http\Controllers\GratuityController;
use App\Modules\ZambianHR\Http\Controllers\GrievanceController;
use App\Modules\ZambianHR\Http\Controllers\TerminationController;
use App\Modules\ZambianHR\Http\Controllers\ContractRenewalController;
use App\Modules\ZambianHR\Http\Controllers\ConflictOfInterestController;

Route::middleware(['auth', 'verified'])->prefix('zambian-hr')->name('zambian-hr.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [ZambianHRDashboardController::class, 'index'])->name('dashboard');
    
    // Funeral Grants
    Route::resource('funeral-grants', FuneralGrantController::class);
    
    // Gratuity
    Route::get('/gratuity', [GratuityController::class, 'index'])->name('gratuity.index');
    Route::get('/gratuity/{employee}', [GratuityController::class, 'calculate'])->name('gratuity.calculate');
    Route::post('/gratuity/{employee}/calculate', [GratuityController::class, 'store'])->name('gratuity.store');
    Route::get('/gratuity/calculations/{calculation}', [GratuityController::class, 'show'])->name('gratuity.show');
    
    // Grievances
    Route::resource('grievances', GrievanceController::class);
    Route::post('/grievances/{grievance}/meetings', [GrievanceController::class, 'storeMeeting'])->name('grievances.meetings.store');
    
    // Terminations
    Route::get('/terminations', [TerminationController::class, 'index'])->name('terminations.index');
    Route::get('/terminations/create/{employee}', [TerminationController::class, 'create'])->name('terminations.create');
    Route::post('/terminations', [TerminationController::class, 'store'])->name('terminations.store');
    Route::get('/terminations/{termination}', [TerminationController::class, 'show'])->name('terminations.show');
    Route::post('/terminations/{termination}/approve', [TerminationController::class, 'approve'])->name('terminations.approve');
    
    // Contract Renewals
    Route::resource('contract-renewals', ContractRenewalController::class);
    
    // Conflict of Interest
    Route::resource('conflict-of-interest', ConflictOfInterestController::class);
});

