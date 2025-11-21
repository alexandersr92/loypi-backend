<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Home route (stub for frontend build - API-only project)
Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Dashboard route (stub for frontend build - API-only project)
Route::get('/dashboard', function () {
    return Inertia::render('dashboard');
})->middleware('auth')->name('dashboard');

// Settings routes
Route::middleware('auth')->group(function () {
    Route::get('/settings/profile', [\App\Http\Controllers\Settings\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/settings/profile', [\App\Http\Controllers\Settings\ProfileController::class, 'update'])->name('profile.update');
    
    Route::get('/user/password', [\App\Http\Controllers\Settings\PasswordController::class, 'edit'])->name('user-password.edit');
    Route::put('/user/password', [\App\Http\Controllers\Settings\PasswordController::class, 'update'])->name('user-password.update');
    
    // Appearance route (stub for frontend build - API-only project)
    Route::get('/settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance.edit');
    
    // Two-factor authentication route (stub for frontend build - API-only project)
    Route::get('/user/two-factor-authentication', [\App\Http\Controllers\Settings\TwoFactorAuthenticationController::class, 'show'])->name('two-factor.show');
});