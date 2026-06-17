<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\TrackedGameController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GameController::class, 'index']);

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/games/{appId}', [GameController::class, 'show']);
    Route::post('/games/{appId}/track', [TrackedGameController::class, 'store']);

    Route::delete('/tracked/{trackedGame}', [TrackedGameController::class, 'destroy']);
});

require __DIR__ . '/auth.php';
