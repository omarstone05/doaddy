<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Decisions\Http\Controllers\DecisionsController;
use App\Modules\Decisions\Http\Controllers\OKRController;
use App\Modules\Decisions\Http\Controllers\StrategicGoalController;
use App\Modules\Decisions\Http\Controllers\BusinessValuationController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Decisions Section
    Route::get('/decisions', [DecisionsController::class, 'index'])->name('decisions.index');

    // OKRs
    Route::resource('decisions/okrs', OKRController::class)->names([
        'index' => 'decisions.okrs.index',
        'create' => 'decisions.okrs.create',
        'store' => 'decisions.okrs.store',
        'show' => 'decisions.okrs.show',
        'edit' => 'decisions.okrs.edit',
        'update' => 'decisions.okrs.update',
        'destroy' => 'decisions.okrs.destroy',
    ]);
    
    Route::post('/decisions/okrs/{okr}/key-results', [OKRController::class, 'addKeyResult'])->name('decisions.okrs.key-results.store');
    Route::put('/decisions/okrs/{okr}/key-results/{keyResult}', [OKRController::class, 'updateKeyResult'])->name('decisions.okrs.key-results.update');

    // Strategic Goals
    Route::resource('decisions/goals', StrategicGoalController::class)->names([
        'index' => 'decisions.goals.index',
        'create' => 'decisions.goals.create',
        'store' => 'decisions.goals.store',
        'show' => 'decisions.goals.show',
        'edit' => 'decisions.goals.edit',
        'update' => 'decisions.goals.update',
        'destroy' => 'decisions.goals.destroy',
    ]);
    
    Route::post('/decisions/goals/{goal}/milestones', [StrategicGoalController::class, 'addMilestone'])->name('decisions.goals.milestones.store');

    // Business Valuation
    Route::resource('decisions/valuation', BusinessValuationController::class)->names([
        'index' => 'decisions.valuation.index',
        'create' => 'decisions.valuation.create',
        'store' => 'decisions.valuation.store',
        'show' => 'decisions.valuation.show',
        'edit' => 'decisions.valuation.edit',
        'update' => 'decisions.valuation.update',
        'destroy' => 'decisions.valuation.destroy',
    ]);
});
