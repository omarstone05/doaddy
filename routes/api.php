<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\BudgetTokenController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/budgets/token', BudgetTokenController::class);
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // NeuroCore API (Beta)
    Route::prefix('neuro')->group(function () {
        Route::post('/chat', [\App\Http\Controllers\Api\NeuroController::class, 'chat']);
        Route::get('/profile', [\App\Http\Controllers\Api\NeuroController::class, 'profile']);
        Route::get('/goals', [\App\Http\Controllers\Api\NeuroController::class, 'goals']);
        Route::post('/goals', [\App\Http\Controllers\Api\NeuroController::class, 'trackGoal']);
        Route::put('/goals/{goalId}/progress', [\App\Http\Controllers\Api\NeuroController::class, 'updateGoalProgress']);
        Route::get('/history', [\App\Http\Controllers\Api\NeuroController::class, 'history']);
        Route::post('/new-conversation', [\App\Http\Controllers\Api\NeuroController::class, 'newConversation']);
        Route::get('/insight', [\App\Http\Controllers\Api\NeuroController::class, 'insight']);
        Route::get('/export', [\App\Http\Controllers\Api\NeuroController::class, 'export']);
    });
});
