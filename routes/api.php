<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\CampaignCustomFieldController;
use App\Http\Controllers\Api\V1\CustomFieldController;
use App\Http\Controllers\Api\V1\CustomerAuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\CustomerCampaignController;
use App\Http\Controllers\Api\V1\CustomerFieldValueController;
use App\Http\Controllers\Api\V1\OtpController;
use App\Http\Controllers\Api\V1\RedemptionController;
use App\Http\Controllers\Api\V1\RewardController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\RewardUnlockController;
use App\Http\Controllers\Api\V1\StaffAuthController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Controllers\Api\V1\StampController;
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
        Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.v1.auth.forgot-password');
        Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->name('api.v1.auth.reset-password');

        // OTP
        Route::prefix('otp')->group(function () {
            Route::post('/send', [OtpController::class, 'send'])->name('api.v1.otp.send');
            Route::post('/verify', [OtpController::class, 'verify'])->name('api.v1.otp.verify');
        });

        // Registro de usuarios
        Route::post('/users', [UserController::class, 'store'])->name('api.v1.users.store');

        // Autenticación de Staff
        Route::post('/staff/login', [StaffAuthController::class, 'login'])->name('api.v1.staff.login');

        // Autenticación de Customers
        Route::post('/customers/check-phone', [CustomerAuthController::class, 'checkPhone'])->name('api.v1.customers.check-phone');
        Route::post('/customers/register', [CustomerAuthController::class, 'register'])->name('api.v1.customers.register');
        Route::post('/customers/login', [CustomerAuthController::class, 'login'])->name('api.v1.customers.login');

        // Campaigns - obtener por código (público)
        Route::get('/campaigns/code/{code}', [CampaignController::class, 'getByCode'])->name('api.v1.campaigns.get-by-code');
        
        // Customer Campaigns - registro con QR (público)
        Route::post('/campaigns/register', [CustomerCampaignController::class, 'register'])->name('api.v1.campaigns.register');
    });

    // Rutas protegidas por token de staff (DEBEN IR ANTES de las rutas de usuario)
    Route::middleware(\App\Http\Middleware\EnsureStaffIsAuthenticated::class)->group(function () {
        // Autenticación de staff
        Route::post('/staff/logout', [StaffAuthController::class, 'logout'])->name('api.v1.staff.logout');
        Route::get('/staff/me', [StaffAuthController::class, 'me'])->name('api.v1.staff.me');
        
        // Stamps - aplicar stamp o streak
        Route::post('/staff/apply-stamp', [StampController::class, 'applyStamp'])->name('api.v1.staff.apply-stamp');
        
        // Redemptions - verificar PIN y canjear premio
        Route::post('/staff/verify-redemption-pin', [RedemptionController::class, 'verifyPin'])->name('api.v1.staff.verify-redemption-pin');
        Route::post('/staff/redeem-reward', [RedemptionController::class, 'redeem'])->name('api.v1.staff.redeem-reward');
    });

    // Rutas protegidas por token de customer
    Route::middleware(\App\Http\Middleware\EnsureCustomerIsAuthenticated::class)->group(function () {
        // Autenticación de customer
        Route::post('/customers/logout', [CustomerAuthController::class, 'logout'])->name('api.v1.customers.logout');
        Route::get('/customers/me', [CustomerAuthController::class, 'me'])->name('api.v1.customers.me');
        
        // Customer Campaigns
        Route::get('/customers/me/campaigns', [CustomerCampaignController::class, 'myCampaigns'])->name('api.v1.customers.me.campaigns');
        
        // Redemptions - generar PIN y listar unlocks
        Route::get('/customers/me/unlocks', [RedemptionController::class, 'myUnlocks'])->name('api.v1.customers.me.unlocks');
        Route::post('/customers/me/unlocks/generate-pin', [RedemptionController::class, 'generatePin'])->name('api.v1.customers.me.unlocks.generate-pin');
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

        // CRUD de Campaigns (solo owners - requiere token de usuario)
        Route::prefix('campaigns')->group(function () {
            Route::get('/', [CampaignController::class, 'index'])->name('api.v1.campaigns.index');
            Route::post('/', [CampaignController::class, 'store'])->name('api.v1.campaigns.store');
            Route::get('/{id}', [CampaignController::class, 'show'])->name('api.v1.campaigns.show');
            Route::put('/{id}', [CampaignController::class, 'update'])->name('api.v1.campaigns.update');
            Route::patch('/{id}', [CampaignController::class, 'update'])->name('api.v1.campaigns.update.patch');
            Route::delete('/{id}', [CampaignController::class, 'destroy'])->name('api.v1.campaigns.destroy');
            
            // Custom Fields de Campaigns
            Route::get('/{id}/custom-fields', [CampaignCustomFieldController::class, 'index'])->name('api.v1.campaigns.custom-fields.index');
            Route::post('/{id}/custom-fields', [CampaignCustomFieldController::class, 'store'])->name('api.v1.campaigns.custom-fields.store');
            Route::delete('/{id}/custom-fields/{fieldId}', [CampaignCustomFieldController::class, 'destroy'])->name('api.v1.campaigns.custom-fields.destroy');
            
            // Customer Field Values
            Route::post('/{id}/customers/{customerId}/field-values', [CustomerFieldValueController::class, 'store'])->name('api.v1.campaigns.customers.field-values.store');
            Route::get('/{id}/customers/{customerId}/field-values', [CustomerFieldValueController::class, 'index'])->name('api.v1.campaigns.customers.field-values.index');
            
            // Customers de Campaign (Owner)
            Route::get('/{id}/customers', [CustomerCampaignController::class, 'campaignCustomers'])->name('api.v1.campaigns.customers.index');
            
            // Stamps de Campaign (Owner)
            Route::get('/{id}/stamps', [StampController::class, 'campaignStamps'])->name('api.v1.campaigns.stamps.index');
            
            // Unlocks de Campaign (Owner)
            Route::get('/{id}/unlocks', [RewardUnlockController::class, 'campaignUnlocks'])->name('api.v1.campaigns.unlocks.index');
        });

        // CRUD de Rewards (solo owners - requiere token de usuario)
        Route::prefix('rewards')->group(function () {
            Route::get('/', [RewardController::class, 'index'])->name('api.v1.rewards.index');
            Route::post('/', [RewardController::class, 'store'])->name('api.v1.rewards.store');
            Route::get('/{id}', [RewardController::class, 'show'])->name('api.v1.rewards.show');
            Route::put('/{id}', [RewardController::class, 'update'])->name('api.v1.rewards.update');
            Route::patch('/{id}', [RewardController::class, 'update'])->name('api.v1.rewards.update.patch');
            Route::delete('/{id}', [RewardController::class, 'destroy'])->name('api.v1.rewards.destroy');
        });

        // CRUD de Custom Fields (solo owners - requiere token de usuario)
        Route::prefix('custom-fields')->group(function () {
            Route::get('/', [CustomFieldController::class, 'index'])->name('api.v1.custom-fields.index');
            Route::post('/', [CustomFieldController::class, 'store'])->name('api.v1.custom-fields.store');
            Route::get('/{id}', [CustomFieldController::class, 'show'])->name('api.v1.custom-fields.show');
            Route::put('/{id}', [CustomFieldController::class, 'update'])->name('api.v1.custom-fields.update');
            Route::patch('/{id}', [CustomFieldController::class, 'update'])->name('api.v1.custom-fields.update.patch');
            Route::delete('/{id}', [CustomFieldController::class, 'destroy'])->name('api.v1.custom-fields.destroy');
            Route::patch('/{id}/toggle', [CustomFieldController::class, 'toggle'])->name('api.v1.custom-fields.toggle');
        });

        // CRUD de Customers (solo owners - requiere token de usuario)
        Route::prefix('customers')->group(function () {
            Route::get('/', [CustomerController::class, 'index'])->name('api.v1.customers.index');
            Route::get('/code/{code}', [CustomerController::class, 'findByCode'])->name('api.v1.customers.find-by-code');
            Route::get('/{id}', [CustomerController::class, 'show'])->name('api.v1.customers.show');
            Route::put('/{id}', [CustomerController::class, 'update'])->name('api.v1.customers.update');
            Route::patch('/{id}', [CustomerController::class, 'update'])->name('api.v1.customers.update.patch');
            Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('api.v1.customers.destroy');
            
            // Stamps de Customer en Campaign (Owner)
            Route::get('/{customerId}/campaigns/{campaignId}/stamps', [StampController::class, 'customerCampaignStamps'])->name('api.v1.customers.campaigns.stamps.index');
            
            // Unlocks de Customer (Owner)
            Route::get('/{id}/unlocks', [RewardUnlockController::class, 'customerUnlocks'])->name('api.v1.customers.unlocks.index');
            
            // Unlocks de Customer en Campaign específica (Owner)
            Route::get('/{customerId}/campaigns/{campaignId}/unlocks', [RewardUnlockController::class, 'customerCampaignUnlocks'])->name('api.v1.customers.campaigns.unlocks.index');
        });
        
        // Reward Unlocks (Owner - lectura solamente)
        Route::prefix('reward-unlocks')->group(function () {
            Route::get('/{id}', [RewardUnlockController::class, 'show'])->name('api.v1.reward-unlocks.show');
        });
        
        // Audit Logs (Owner/Admin - lectura solamente)
        Route::prefix('audit-logs')->group(function () {
            Route::get('/', [AuditLogController::class, 'index'])->name('api.v1.audit-logs.index');
            Route::get('/{id}', [AuditLogController::class, 'show'])->name('api.v1.audit-logs.show');
        });
    });
});
