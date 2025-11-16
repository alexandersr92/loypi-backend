<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerToken;
use App\Services\OtpService;
use App\Services\ShortCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    public function __construct(
        private OtpService $otpService
    ) {}

    public function requestOtp(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|min:10|max:15',
        ]);

        $business = Business::where('slug', $slug)->firstOrFail();

        try {
            $otp = $this->otpService->generateOtp($request->phone);
            
            return response()->json([
                'message' => 'Código OTP enviado',
                'expires_in' => 600, // 10 minutos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 429);
        }
    }

    public function verifyOtp(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|min:10|max:15',
            'otp' => 'required|string|size:6',
        ]);

        $business = Business::where('slug', $slug)->firstOrFail();

        if (!$this->otpService->verifyOtp($request->phone, $request->otp)) {
            throw ValidationException::withMessages([
                'otp' => ['El código OTP es inválido o ha expirado.'],
            ]);
        }

        return DB::transaction(function () use ($request, $business) {
            // Buscar o crear cliente
            $customer = Customer::firstOrCreate(
                ['phone' => $request->phone],
                [
                    'short_code' => ShortCodeService::generate(),
                    'name' => null,
                ]
            );

            // Invalidar tokens anteriores del cliente para este negocio
            CustomerToken::where('customer_id', $customer->id)
                ->where('business_id', $business->id)
                ->update(['active' => false]);

            // Crear nuevo token
            $token = CustomerToken::create([
                'customer_id' => $customer->id,
                'business_id' => $business->id,
                'token' => CustomerToken::generateToken(),
                'expires_at' => now()->addMonths(6),
                'active' => true,
            ]);

            // Crear token Sanctum para el cliente
            $sanctumToken = $customer->createToken('customer-token', ['*'], now()->addMonths(6))->plainTextToken;

            return response()->json([
                'customer' => $customer->makeHidden(['created_at', 'updated_at']),
                'token' => $sanctumToken,
                'customer_token' => $token->token,
                'expires_at' => $token->expires_at->toIso8601String(),
            ]);
        });
    }

    public function logout(Request $request): JsonResponse
    {
        $customer = $request->user('customer');
        
        if ($customer) {
            $customer->currentAccessToken()?->delete();
        }

        return response()->json(['message' => 'Sesión cerrada exitosamente']);
    }

    public function me(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        $customer = $request->user('customer');

        if (!$customer) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // Verificar token del negocio
        $customerToken = CustomerToken::where('customer_id', $customer->id)
            ->where('business_id', $business->id)
            ->where('active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (!$customerToken) {
            return response()->json(['message' => 'Token inválido para este negocio'], 403);
        }

        return response()->json([
            'customer' => $customer,
            'business' => $business->makeHidden(['created_at', 'updated_at']),
        ]);
    }
}

