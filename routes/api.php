<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\OtpController;
use App\Http\Controllers\Api\V1\StaffAuthController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;


// API Versionada - V1
Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'message' => 'API v1',
            'version' => '1.0.0',
        ]);
    });
    // Rutas públicas (sin autenticación)
    Route::group(['middleware' => []], function () {
        // Autenticación
        Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
        Route::post('/auth/login-with-otp', [AuthController::class, 'loginWithOtp'])->name('api.v1.auth.login-with-otp');

        // OTP
        Route::prefix('otp')->group(function () {
            Route::post('/send', [OtpController::class, 'send'])->name('api.v1.otp.send');
            Route::post('/verify', [OtpController::class, 'verify'])->name('api.v1.otp.verify');
        });

        // Registro de usuarios
        Route::post('/users', [UserController::class, 'store'])->name('api.v1.users.store');

        // Autenticación de Staff
        Route::post('/staff/login', [StaffAuthController::class, 'login'])->name('api.v1.staff.login');
    });

    // Rutas protegidas por token de staff (DEBEN IR ANTES de las rutas de usuario)
    Route::middleware(\App\Http\Middleware\EnsureStaffIsAuthenticated::class)->group(function () {
        // Autenticación de staff
        Route::post('/staff/logout', [StaffAuthController::class, 'logout'])->name('api.v1.staff.logout');
        Route::get('/staff/me', [StaffAuthController::class, 'me'])->name('api.v1.staff.me');
        
        // Aquí irán todas las rutas que el staff puede usar
        // Por ejemplo: operaciones de punto de venta, escaneo de códigos, etc.
    });

    // Rutas protegidas por token de usuario (owners/admins)
    Route::middleware('auth:sanctum')->group(function () {
        // Autenticación de usuarios
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('api.v1.auth.me');

        // Usuarios
        Route::prefix('users')->group(function () {
            Route::get('/{id}', [UserController::class, 'show'])->name('api.v1.users.show');
            Route::put('/{id}', [UserController::class, 'update'])->name('api.v1.users.update');
            Route::patch('/{id}', [UserController::class, 'update'])->name('api.v1.users.update.patch');
        });

        // Negocios
        Route::prefix('businesses')->group(function () {
            Route::post('/', [BusinessController::class, 'store'])->name('api.v1.businesses.store');
            Route::get('/{id}', [BusinessController::class, 'show'])->name('api.v1.businesses.show');
            Route::get('/slug/{slug}', [BusinessController::class, 'showBySlug'])->name('api.v1.businesses.show-by-slug');
            Route::put('/{id}', [BusinessController::class, 'update'])->name('api.v1.businesses.update');
            Route::patch('/{id}', [BusinessController::class, 'update'])->name('api.v1.businesses.update.patch');
        });

        // CRUD de Staff (solo owners - requiere token de usuario)
        Route::prefix('staff')->group(function () {
            Route::get('/', [StaffController::class, 'index'])->name('api.v1.staff.index');
            Route::post('/', [StaffController::class, 'store'])->name('api.v1.staff.store');
            Route::get('/{id}', [StaffController::class, 'show'])->name('api.v1.staff.show');
            Route::put('/{id}', [StaffController::class, 'update'])->name('api.v1.staff.update');
            Route::patch('/{id}', [StaffController::class, 'update'])->name('api.v1.staff.update.patch');
            Route::delete('/{id}', [StaffController::class, 'destroy'])->name('api.v1.staff.destroy');
            Route::post('/{id}/unlock', [StaffController::class, 'unlock'])->name('api.v1.staff.unlock');
        });
    });
});
