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
        Route::get('tasks/create', [TaskController::class, 'create'])->name('projects.tasks.create');
        Route::post('tasks', [TaskController::class, 'store'])->name('projects.tasks.store');
        Route::get('tasks/{task}', [TaskController::class, 'show'])->name('projects.tasks.show');
        Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('projects.tasks.edit');
        Route::put('tasks/{task}', [TaskController::class, 'update'])->name('projects.tasks.update');
        Route::patch('tasks/{task}/mark-done', [TaskController::class, 'markAsDone'])->name('projects.tasks.mark-done');
        Route::patch('tasks/{task}/toggle-status', [TaskController::class, 'toggleStatus'])->name('projects.tasks.toggle-status');
        Route::patch('tasks/{task}/due-date', [TaskController::class, 'updateDueDate'])->name('projects.tasks.update-due-date');
        Route::post('tasks/{task}/comments', [TaskController::class, 'addComment'])->name('projects.tasks.comments.store');
        Route::post('tasks/{task}/steps', [TaskController::class, 'addStep'])->name('projects.tasks.steps.store');
        Route::patch('tasks/{task}/steps/{step}/toggle', [TaskController::class, 'toggleStep'])->name('projects.tasks.steps.toggle');
        Route::post('tasks/{task}/toggle-follower', [TaskController::class, 'toggleFollower'])->name('projects.tasks.toggle-follower');
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('projects.tasks.destroy');
    });
});

