<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckPhoneRequest;
use App\Http\Requests\Api\V1\CustomerLoginRequest;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Otp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @group 🔐 Autenticación Customer
 * 
 * Endpoints para autenticación de customers (clientes)
 * 
 * @unauthenticated
 */
class CustomerAuthController extends Controller
{
    /**
     * Verificar si el teléfono ya está registrado
     * 
     * Verifica si un número de teléfono ya está registrado en un negocio específico.
     * Útil para mostrar mensajes como "Este número ya está registrado" o "Número disponible para registro".
     * 
     * @unauthenticated
     */
    public function checkPhone(CheckPhoneRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $phone = $validated['phone'];
        $businessSlug = $validated['business_slug'];

        $business = Business::where('slug', $businessSlug)->firstOrFail();
        
        $exists = Customer::where('business_id', $business->id)
            ->where('phone', $phone)
            ->exists();

        return response()->json([
            'success' => true,
            'exists' => $exists,
            'message' => $exists 
                ? 'Este número ya está registrado.' 
                : 'Número disponible para registro.',
        ], 200);
    }

    /**
     * Registro de customer (después de verificar OTP)
     * 
     * Registra un nuevo customer en el sistema. Requiere:
     * 1. Enviar OTP usando `/api/v1/otp/send`
     * 2. Verificar OTP usando `/api/v1/otp/verify`
     * 3. Llamar a este endpoint con el código OTP verificado
     * 
     * El customer recibirá un token de autenticación y un short_code único de 6 caracteres.
     * 
     * @unauthenticated
     */
    public function register(StoreCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $phone = $validated['phone'];
        $name = $validated['name'];
        $businessSlug = $validated['business_slug'];
        $otpCode = $validated['otp_code'];

        $business = Business::where('slug', $businessSlug)->firstOrFail();

        // Verificar OTP
        $otp = Otp::where('phone', $phone)
            ->where('code', $otpCode)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Código OTP inválido o expirado.',
            ], 400);
        }

        // Verificar que el teléfono no exista ya en este business
        $existingCustomer = Customer::where('business_id', $business->id)
            ->where('phone', $phone)
            ->first();

        if ($existingCustomer) {
            return response()->json([
                'success' => false,
                'message' => 'Este número ya está registrado en este negocio.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Crear customer
            $customer = Customer::create([
                'business_id' => $business->id,
                'phone' => $phone,
                'name' => $name,
            ]);

            // Marcar OTP como verificado
            $otp->markAsVerified();

            // Generar token
            $token = $customer->createToken('customer-token')->plainTextToken;

            // Guardar token en customer_tokens
            \App\Models\CustomerToken::create([
                'customer_id' => $customer->id,
                'business_id' => $business->id,
                'token' => explode('|', $token)[1],
                'expires_at' => null,
                'active' => true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer registered successfully.',
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'short_code' => $customer->short_code,
                        'phone' => $customer->phone,
                        'name' => $customer->name,
                    ],
                    'token' => $token,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to register customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login de customer (después de verificar OTP)
     * 
     * Autentica un customer existente. Requiere:
     * 1. Enviar OTP usando `/api/v1/otp/send`
     * 2. Verificar OTP usando `/api/v1/otp/verify`
     * 3. Llamar a este endpoint con el código OTP verificado
     * 
     * El customer recibirá un nuevo token de autenticación.
     * 
     * @unauthenticated
     */
    public function login(CustomerLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $phone = $validated['phone'];
        $businessSlug = $validated['business_slug'];
        $otpCode = $validated['otp_code'];

        $business = Business::where('slug', $businessSlug)->firstOrFail();

        // Verificar OTP
        $otp = Otp::where('phone', $phone)
            ->where('code', $otpCode)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Código OTP inválido o expirado.',
            ], 400);
        }

        // Buscar customer
        $customer = Customer::where('business_id', $business->id)
            ->where('phone', $phone)
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Marcar OTP como verificado
            $otp->markAsVerified();

            // Generar nuevo token
            $token = $customer->createToken('customer-token')->plainTextToken;

            // Guardar token en customer_tokens
            \App\Models\CustomerToken::create([
                'customer_id' => $customer->id,
                'business_id' => $business->id,
                'token' => explode('|', $token)[1],
                'expires_at' => null,
                'active' => true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'short_code' => $customer->short_code,
                        'phone' => $customer->phone,
                        'name' => $customer->name,
                    ],
                    'token' => $token,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to login: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout de customer
     * 
     * Cierra la sesión del customer actual, invalidando su token de autenticación.
     * 
     * @authenticated
     * @guard customer
     */
    public function logout(Request $request): JsonResponse
    {
        $customer = $request->user();

        // Obtener el token actual
        $token = $request->attributes->get('sanctum_token') 
            ?? $request->user()->currentAccessToken()
            ?? null;

        if ($token) {
            // Buscar en customer_tokens y desactivar
            $tokenHash = explode('|', $token->token ?? '')[1] ?? null;
            if ($tokenHash) {
                \App\Models\CustomerToken::where('token', $tokenHash)
                    ->where('customer_id', $customer->id)
                    ->update(['active' => false]);
            }

            // Eliminar token de Sanctum
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ], 200);
    }

    /**
     * Obtener customer autenticado con sus campaigns y stamps
     * 
     * Devuelve la información del customer autenticado, incluyendo:
     * - Datos básicos (id, short_code, phone, name)
     * - Información del negocio
     * - Lista de campaigns con stamps
     * 
     * @authenticated
     * @guard customer
     */
    public function me(Request $request): JsonResponse
    {
        $customer = $request->user()->load(['business', 'customerCampaigns.campaign.business', 'customerCampaigns.campaign.rewards']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'short_code' => $customer->short_code,
                'phone' => $customer->phone,
                'name' => $customer->name,
                'business' => [
                    'id' => $customer->business->id,
                    'slug' => $customer->business->slug,
                    'name' => $customer->business->name,
                ],
                'campaigns' => $customer->customerCampaigns->map(function ($customerCampaign) {
                    return [
                        'id' => $customerCampaign->id,
                        'stamps' => $customerCampaign->stamps,
                        'redeemed_at' => $customerCampaign->redeemed_at?->toIso8601String(),
                        'campaign' => [
                            'id' => $customerCampaign->campaign->id,
                            'code' => $customerCampaign->campaign->code,
                            'name' => $customerCampaign->campaign->name,
                            'type' => $customerCampaign->campaign->type,
                            'description' => $customerCampaign->campaign->description,
                        ],
                        'created_at' => $customerCampaign->created_at->toIso8601String(),
                    ];
                }),
            ],
        ], 200);
    }
}
