<?php

use App\Modules\Consulting\Http\Controllers\ProjectController;
use App\Modules\Consulting\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('consulting')->name('consulting.')->group(function () {
    // Projects
    Route::resource('projects', ProjectController::class);
    
    // Tasks (nested under projects)
    Route::prefix('projects/{project}')->group(function () {
        Route::get('tasks', [TaskController::class, 'index'])->name('projects.tasks.index');
        Route::post('tasks', [TaskController::class, 'store'])->name('projects.tasks.store');
        Route::put('tasks/{task}', [TaskController::class, 'update'])->name('projects.tasks.update');
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('projects.tasks.destroy');
    });
});

