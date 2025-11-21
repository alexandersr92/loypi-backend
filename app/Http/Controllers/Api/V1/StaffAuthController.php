<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StaffLoginRequest;
use App\Models\Business;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @group 🔑 Autenticación Staff
 * 
 * Endpoints para autenticación de staff (empleados)
 * 
 * @authenticated
 * @header Authorization Bearer {staff_token} Requiere token de staff (obtenido con /staff/login)
 */
class StaffAuthController extends Controller
{
    /**
     * Login del staff
     * 
     * @unauthenticated
     */
    public function login(StaffLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $businessSlug = $validated['business_slug'];
        $code = $validated['code'];
        $pin = $validated['pin'];

        // Buscar el negocio por slug
        $business = Business::where('slug', $businessSlug)->first();

        if (! $business) {
            throw ValidationException::withMessages([
                'business_slug' => ['The business does not exist.'],
            ]);
        }

        // Buscar el staff por código dentro del negocio
        $staff = Staff::where('business_id', $business->id)
            ->where('code', $code)
            ->first();

        if (! $staff) {
            throw ValidationException::withMessages([
                'code' => ['Invalid staff code.'],
            ]);
        }

        // Verificar si el staff está activo
        if (! $staff->active) {
            return response()->json([
                'success' => false,
                'message' => 'Your staff account is not active.',
            ], 403);
        }

        // Verificar si el staff está bloqueado
        if ($staff->isLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is locked due to multiple failed login attempts. Please contact the owner to unlock it.',
                'data' => [
                    'locked_until' => $staff->locked_until->toIso8601String(),
                ],
            ], 403);
        }

        // Verificar el PIN
        if (! $staff->verifyPin($pin)) {
            // Incrementar intentos fallidos
            $staff->incrementFailedAttempts();

            $remainingAttempts = 5 - $staff->failed_login_attempts;
            
            if ($staff->isLocked()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed login attempts. Your account has been locked. Please contact the owner to unlock it.',
                    'data' => [
                        'locked_until' => $staff->locked_until->toIso8601String(),
                    ],
                ], 403);
            }

            throw ValidationException::withMessages([
                'pin' => ['Invalid PIN. ' . $remainingAttempts . ' attempt(s) remaining.'],
            ]);
        }

        // Login exitoso - resetear intentos fallidos
        $staff->resetFailedAttempts();
        $staff->update(['last_login_at' => now()]);

        // Crear token (usando Sanctum)
        $token = $staff->createToken('staff-auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'staff' => [
                    'id' => $staff->id,
                    'code' => $staff->code,
                    'name' => $staff->name,
                    'active' => $staff->active,
                ],
                'business' => [
                    'id' => $business->id,
                    'slug' => $business->slug,
                    'name' => $business->name,
                ],
                'token' => $token,
            ],
        ], 200);
    }

    /**
     * Logout del staff
     * 
     * @authenticated
     * @header Authorization Bearer {staff_token} Requiere token de staff
     */
    public function logout(Request $request): JsonResponse
    {
        $staff = $request->user();
        
        // Obtener el token desde los atributos de la request (establecido por el middleware)
        $token = $request->attributes->get('sanctum_token');
        
        // Si no está en los atributos, intentar obtenerlo con currentAccessToken
        if (! $token) {
            $token = $staff->currentAccessToken();
        }
        
        // Si aún no hay token, buscar el token actual por el bearer token
        if (! $token) {
            $bearerToken = $request->bearerToken();
            if ($bearerToken) {
                $token = \Laravel\Sanctum\PersonalAccessToken::findToken($bearerToken);
            }
        }
        
        if ($token) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], 200);
    }

    /**
     * Obtener el staff autenticado
     * 
     * @authenticated
     * @header Authorization Bearer {staff_token} Requiere token de staff
     */
    public function me(Request $request): JsonResponse
    {
 
        $staff = $request->user();
        $staff->load('business');

        return response()->json([
            'success' => true,
            'data' => [
                'staff' => [
                    'id' => $staff->id,
                    'code' => $staff->code,
                    'name' => $staff->name,
                    'active' => $staff->active,
                    'last_login_at' => $staff->last_login_at?->toIso8601String(),
                ],
                'business' => [
                    'id' => $staff->business->id,
                    'slug' => $staff->business->slug,
                    'name' => $staff->business->name,
                ],
            ],
        ], 200);
    }
}
