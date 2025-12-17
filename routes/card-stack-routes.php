<?php

use App\Http\Controllers\CardStackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Card Stack Routes
|--------------------------------------------------------------------------
|
| Routes for the card stack feature - swipeable cards for Addy insights
| and notifications
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Addy Insight Cards
    Route::get('/cards/insights', [CardStackController::class, 'insights'])
        ->name('cards.insights');
    Route::post('/cards/insights/{insight}/dismiss', [CardStackController::class, 'dismissInsight'])
        ->name('cards.insights.dismiss');
    
    // Notification Cards
    Route::get('/cards/notifications', [CardStackController::class, 'notifications'])
        ->name('cards.notifications');
    Route::post('/cards/notifications/{notification}/dismiss', [CardStackController::class, 'dismissNotification'])
        ->name('cards.notifications.dismiss');
    
    // Statistics API
    Route::get('/cards/statistics', [CardStackController::class, 'statistics'])
        ->name('cards.statistics');
});
