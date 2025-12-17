<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Tax\Http\Controllers\TaxController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/tax', [TaxController::class, 'index'])->name('tax.index');
});
