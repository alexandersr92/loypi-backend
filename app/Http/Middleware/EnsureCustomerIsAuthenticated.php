<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerIsAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Paso 1: Obtener el token del header
        $token = $request->bearerToken();
        
        // Si no está en bearerToken, intentar obtenerlo del header Authorization directamente
        if (! $token) {
            $authHeader = $request->header('Authorization');
            if ($authHeader) {
                // Si tiene el prefijo "Bearer ", extraerlo
                if (str_starts_with($authHeader, 'Bearer ')) {
                    $token = substr($authHeader, 7);
                } else {
                    // Si no tiene prefijo, usar el valor directamente
                    $token = $authHeader;
                }
            }
        }

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. No token provided.',
            ], 401);
        }

        // Paso 2: Buscar el token usando el método de Sanctum
        try {
            $accessToken = PersonalAccessToken::findToken($token);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error validating token: ' . $e->getMessage(),
            ], 401);
        }

        if (! $accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token. Token not found in database.',
            ], 401);
        }

        // Paso 3: Verificar que el token pertenece a un Customer
        if ($accessToken->tokenable_type !== Customer::class) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint requires customer authentication.',
            ], 403);
        }

        // Paso 4: Cargar el modelo Customer
        $customer = Customer::find($accessToken->tokenable_id);

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
            ], 404);
        }

        // Paso 5: Establecer el customer como usuario autenticado
        Auth::guard('customer')->setUser($customer);
        
        // También establecerlo en la request para que $request->user() funcione
        $request->setUserResolver(function () use ($customer) {
            return $customer;
        });
        
        // Establecer el token en la request para que currentAccessToken() funcione
        $request->attributes->set('sanctum_token', $accessToken);

        return $next($request);
    }
}
