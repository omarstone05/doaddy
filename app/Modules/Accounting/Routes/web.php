<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Accounting\Http\Controllers\AccountController;
use App\Modules\Accounting\Http\Controllers\JournalEntryController;
use App\Modules\Accounting\Http\Controllers\AccountingReportController;

/*
|--------------------------------------------------------------------------
| Accounting Module Routes
|--------------------------------------------------------------------------
|
| Routes for the Accounting module (Chart of Accounts, Journal Entries, Reports).
| Access is controlled by the 'module:Accounting' middleware.
|
*/

Route::middleware(['auth', 'verified', 'module:Accounting'])->prefix('accounting')->name('accounting.')->group(function () {
    // Main accounting route - redirect to Chart of Accounts
    Route::get('/', function () {
        return redirect()->route('accounting.accounts.index');
    })->name('index');
    
    // Chart of Accounts
    Route::resource('accounts', AccountController::class);
    Route::get('accounts/type/{type}', [AccountController::class, 'getByType'])->name('accounts.by-type');
    Route::post('accounts/{id}/restore', [AccountController::class, 'restore'])->name('accounts.restore');
    
    // Journal Entries
    Route::resource('journal-entries', JournalEntryController::class);
    Route::post('journal-entries/{journalEntry}/post', [JournalEntryController::class, 'post'])->name('journal-entries.post');
    Route::post('journal-entries/{journalEntry}/reverse', [JournalEntryController::class, 'reverse'])->name('journal-entries.reverse');
    
    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('trial-balance', [AccountingReportController::class, 'trialBalance'])->name('trial-balance');
        Route::get('general-ledger', [AccountingReportController::class, 'generalLedger'])->name('general-ledger');
        Route::get('balance-sheet', [AccountingReportController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('income-statement', [AccountingReportController::class, 'incomeStatement'])->name('income-statement');
        Route::get('cash-flow', [AccountingReportController::class, 'cashFlow'])->name('cash-flow');
    });
});

