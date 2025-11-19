<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Retail\Http\Controllers\POSController;
use App\Modules\Retail\Http\Controllers\SaleController;
use App\Modules\Retail\Http\Controllers\RegisterSessionController;
use App\Modules\Retail\Http\Controllers\SaleReturnController;

/*
|--------------------------------------------------------------------------
| Retail Module Routes
|--------------------------------------------------------------------------
|
| Point of Sale (POS) and retail management routes
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // POS
    Route::get('/pos', [POSController::class, 'index'])->name('retail.pos.index');
    Route::get('/pos/products/search', [POSController::class, 'searchProducts'])->name('retail.pos.products.search');
    Route::get('/pos/products/barcode/{barcode}', [POSController::class, 'findByBarcode'])->name('retail.pos.products.barcode');
    Route::get('/pos/customers/search', [POSController::class, 'searchCustomers'])->name('retail.pos.customers.search');
    
    // Sales
    Route::post('/pos/sales', [SaleController::class, 'store'])->name('retail.pos.sales.store');
    Route::get('/pos/sales/{sale}', [SaleController::class, 'show'])->name('retail.pos.sales.show');
    Route::get('/sales/search', [SaleController::class, 'search'])->name('retail.sales.search');
    
    // Register Sessions
    Route::get('/register-sessions', [RegisterSessionController::class, 'index'])->name('retail.register.index');
    Route::post('/register-sessions/open', [RegisterSessionController::class, 'open'])->name('retail.register.open');
    Route::post('/register-sessions/{session}/close', [RegisterSessionController::class, 'close'])->name('retail.register.close');
    
    // Sale Returns
    Route::resource('sale-returns', SaleReturnController::class)->names([
        'index' => 'retail.sale-returns.index',
        'create' => 'retail.sale-returns.create',
        'store' => 'retail.sale-returns.store',
        'show' => 'retail.sale-returns.show',
    ]);
});

