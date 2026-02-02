<?php

use App\Modules\SmartInvoice\Http\Controllers\DigitaxCredentialController;

Route::middleware(['auth:api', 'organization'])->group(function () {
    
    // Digitax Credentials Management
    Route::prefix('digitax')->name('digitax.')->group(function () {
        
        // List credentials
        Route::get('credentials', [DigitaxCredentialController::class, 'index'])
            ->name('credentials.index');
        
        // Store new credentials
        Route::post('credentials', [DigitaxCredentialController::class, 'store'])
            ->name('credentials.store');
        
        // Get specific credential
        Route::get('credentials/{credential}', [DigitaxCredentialController::class, 'show'])
            ->name('credentials.show');
        
        // Update credentials
        Route::put('credentials/{credential}', [DigitaxCredentialController::class, 'update'])
            ->name('credentials.update');
        
        // Delete credentials
        Route::delete('credentials/{credential}', [DigitaxCredentialController::class, 'destroy'])
            ->name('credentials.destroy');
        
        // Test connection
        Route::post('credentials/{credential}/test', [DigitaxCredentialController::class, 'testConnection'])
            ->name('credentials.test');
    });
});
