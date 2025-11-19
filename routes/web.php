<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Settings routes
Route::middleware('auth')->group(function () {
    Route::get('/settings/profile', [\App\Http\Controllers\Settings\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/settings/profile', [\App\Http\Controllers\Settings\ProfileController::class, 'update'])->name('profile.update');
});