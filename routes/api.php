<?php

use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerProgressController;
use App\Http\Controllers\Api\CustomerRewardController;
use App\Http\Controllers\Api\OwnerAuthController;
use App\Http\Controllers\Api\OwnerCampaignController;
use App\Http\Controllers\Api\RedemptionController;
use App\Http\Controllers\Api\RewardController;
use App\Http\Controllers\Api\StaffAuthController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\StampController;
use Illuminate\Support\Facades\Route;

// ====== Autenticación de Owners ======
Route::post('/owner/auth/login', [OwnerAuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/owner/auth/me', [OwnerAuthController::class, 'me']);
    Route::post('/owner/auth/logout', [OwnerAuthController::class, 'logout']);
});

// ====== Rutas de Owners (requieren autenticación) ======
Route::prefix('{slug}/owner')->middleware(['auth:sanctum', \App\Http\Middleware\EnsureOwnerBelongsToBusiness::class])->group(function () {
    // Campañas
    Route::get('/campaigns', [OwnerCampaignController::class, 'index']);
    Route::get('/campaigns/{id}', [OwnerCampaignController::class, 'show']);
    
    // Clientes en una campaña
    Route::get('/campaigns/{campaignId}/customers', [OwnerCampaignController::class, 'customers']);
});

// ====== Autenticación de Clientes ======
Route::prefix('{slug}/customer')->group(function () {
    Route::post('/auth/request-otp', [CustomerAuthController::class, 'requestOtp']);
    Route::post('/auth/verify-otp', [CustomerAuthController::class, 'verifyOtp']);
    
    Route::middleware('auth:customer')->group(function () {
        Route::get('/auth/me', [CustomerAuthController::class, 'me']);
        Route::post('/auth/logout', [CustomerAuthController::class, 'logout']);
    });
});

// ====== Autenticación de Staff ======
Route::prefix('{slug}/staff')->group(function () {
    Route::post('/auth/login', [StaffAuthController::class, 'login']);
    
    Route::middleware('auth:staff')->group(function () {
        Route::get('/auth/me', [StaffAuthController::class, 'me']);
        Route::post('/auth/logout', [StaffAuthController::class, 'logout']);
    });
});

// ====== Rutas de Staff (requieren autenticación) ======
Route::prefix('{slug}/staff')->middleware(['auth:staff'])->group(function () {
    // Gestión de Staff
    Route::apiResource('staff', StaffController::class);
    
    // Campañas
    Route::apiResource('campaigns', CampaignController::class);
    
    // Premios
    Route::prefix('campaigns/{campaignId}')->group(function () {
        Route::apiResource('rewards', RewardController::class);
    });
    
    // Sellos
    Route::post('/stamps', [StampController::class, 'addStamp']);
    Route::get('/stamps', [StampController::class, 'index']);
    
    // Canjes
    Route::post('/redemptions', [RedemptionController::class, 'redeem']);
    Route::get('/redemptions', [RedemptionController::class, 'index']);
    
    // Clientes
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::put('/customers/{id}', [CustomerController::class, 'update']);
});

// ====== Rutas de Clientes (requieren autenticación) ======
Route::prefix('{slug}/customer')->middleware(['auth:customer'])->group(function () {
    // Ver campañas activas
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::get('/campaigns/{id}', [CampaignController::class, 'show']);
    
    // Ver mis premios desbloqueados
    Route::get('/rewards/unlocked', [CustomerRewardController::class, 'unlocked']);
    
    // Ver mi progreso
    Route::get('/progress', [CustomerProgressController::class, 'index']);
});
