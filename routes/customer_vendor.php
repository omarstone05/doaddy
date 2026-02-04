<?php

use App\Http\Controllers\CustomerVendorController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\ProspectController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\BillController;
use Illuminate\Support\Facades\Route;

// Customer & Vendor Management Overview
Route::get('/customer-vendor', [CustomerVendorController::class, 'overview'])->name('customer-vendor.overview');

// Customers
Route::prefix('customers')->name('customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::get('/create', [CustomerController::class, 'create'])->name('create');
    Route::post('/', [CustomerController::class, 'store'])->name('store');
    
    // Customer Personas - must be before /{customer} to avoid route conflicts
    Route::get('/personas', [CustomerController::class, 'personas'])->name('personas');
    Route::post('/personas', [CustomerController::class, 'storePersona'])->name('personas.store');
    
    // Customer show/edit/update/delete routes
    Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
    Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
    Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
    Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
});

// Vendors
Route::prefix('vendors')->name('vendors.')->group(function () {
    Route::get('/', [VendorController::class, 'index'])->name('index');
    Route::get('/create', [VendorController::class, 'create'])->name('create');
    Route::post('/', [VendorController::class, 'store'])->name('store');
    Route::get('/{vendor}', [VendorController::class, 'show'])->name('show');
    Route::get('/{vendor}/edit', [VendorController::class, 'edit'])->name('edit');
    Route::put('/{vendor}', [VendorController::class, 'update'])->name('update');
    Route::delete('/{vendor}', [VendorController::class, 'destroy'])->name('destroy');
});

// Prospects
Route::prefix('prospects')->name('prospects.')->group(function () {
    Route::get('/', [ProspectController::class, 'index'])->name('index');
    Route::get('/create', [ProspectController::class, 'create'])->name('create');
    Route::post('/', [ProspectController::class, 'store'])->name('store');
    Route::get('/{prospect}', [ProspectController::class, 'show'])->name('show');
    Route::get('/{prospect}/edit', [ProspectController::class, 'edit'])->name('edit');
    Route::put('/{prospect}', [ProspectController::class, 'update'])->name('update');
    Route::delete('/{prospect}', [ProspectController::class, 'destroy'])->name('destroy');
    
    // Prospect Actions
    Route::post('/{prospect}/convert', [ProspectController::class, 'convertToCustomer'])->name('convert');
    Route::put('/{prospect}/stage', [ProspectController::class, 'updateStage'])->name('update-stage');
});

// Quotations
Route::prefix('quotations')->name('quotations.')->group(function () {
    Route::get('/search-products', [QuotationController::class, 'searchProducts'])->name('search-products');
    Route::get('/', [QuotationController::class, 'index'])->name('index');
    Route::get('/create', [QuotationController::class, 'create'])->name('create');
    Route::post('/', [QuotationController::class, 'store'])->name('store');
    Route::get('/{quotation}', [QuotationController::class, 'show'])->name('show');
    Route::get('/{quotation}/edit', [QuotationController::class, 'edit'])->name('edit');
    Route::get('/{quotation}/download-pdf', [QuotationController::class, 'downloadPdf'])->name('download-pdf');
    Route::put('/{quotation}', [QuotationController::class, 'update'])->name('update');
    Route::put('/{quotation}/status', [QuotationController::class, 'updateStatus'])->name('update-status');
    Route::post('/{quotation}/convert', [QuotationController::class, 'convertToInvoice'])->name('convert');
    Route::delete('/{quotation}', [QuotationController::class, 'destroy'])->name('destroy');
});

// Bills
Route::prefix('bills')->name('bills.')->group(function () {
    Route::get('/', [BillController::class, 'index'])->name('index');
    Route::get('/create', [BillController::class, 'create'])->name('create');
    Route::post('/', [BillController::class, 'store'])->name('store');
    Route::get('/{bill}', [BillController::class, 'show'])->name('show');
    Route::get('/{bill}/edit', [BillController::class, 'edit'])->name('edit');
    Route::put('/{bill}', [BillController::class, 'update'])->name('update');
    Route::delete('/{bill}', [BillController::class, 'destroy'])->name('destroy');
    
    // Bill Actions
    Route::post('/{bill}/approve', [BillController::class, 'approve'])->name('approve');
    Route::post('/{bill}/payment', [BillController::class, 'recordPayment'])->name('payment');
    Route::post('/{bill}/cancel', [BillController::class, 'cancel'])->name('cancel');
});
