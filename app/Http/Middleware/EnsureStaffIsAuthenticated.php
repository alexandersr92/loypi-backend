<?php

namespace App\Http\Middleware;

use App\Models\Staff;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffIsAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log temporal para debug
        \Log::info('EnsureStaffIsAuthenticated middleware ejecutado', [
            'path' => $request->path(),
            'method' => $request->method(),
            'bearerToken' => $request->bearerToken() ? 'present' : 'missing',
            'authorization_header' => $request->header('Authorization') ? 'present' : 'missing',
        ]);

        // Paso 1: Obtener el token del header
        $token = $request->bearerToken();
        
        // Si no está en bearerToken, intentar obtenerlo del header Authorization directamente
        if (! $token) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            }
        }

        if (! $token) {
            \Log::info('No token provided', [
                'bearerToken' => $request->bearerToken(),
                'authorization_header' => $request->header('Authorization'),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. No token provided.',
                'debug' => [
                    'bearerToken' => $request->bearerToken(),
                    'authorization_header' => $request->header('Authorization'),
                ],
            ], 401);
        }
        
        \Log::info('Token recibido', ['token_preview' => substr($token, 0, 20) . '...']);

        // Paso 2: Buscar el token usando el método de Sanctum
        try {
            $accessToken = PersonalAccessToken::findToken($token);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error validating token: ' . $e->getMessage(),
                'debug' => [
                    'token_received' => substr($token, 0, 20) . '...',
                    'exception' => $e->getMessage(),
                ],
            ], 401);
        }

        if (! $accessToken) {
            // Intentar buscar manualmente para debug
            $parts = explode('|', $token);
            $manualSearch = null;
            if (count($parts) === 2) {
                [$id, $hash] = $parts;
                $manualSearch = PersonalAccessToken::where('id', $id)->first();
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid token. Token not found in database.',
                'debug' => [
                    'token_format' => count($parts) === 2 ? 'valid' : 'invalid',
                    'token_id' => $parts[0] ?? 'N/A',
                    'manual_search_found' => $manualSearch ? 'yes' : 'no',
                ],
            ], 401);
        }

        // Paso 3: Verificar que el token pertenece a un Staff
        if ($accessToken->tokenable_type !== Staff::class) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint requires staff authentication.',
                'debug' => [
                    'tokenable_type' => $accessToken->tokenable_type,
                    'expected_type' => Staff::class,
                ],
            ], 403);
        }

        // Paso 4: Cargar el modelo Staff
        $staff = Staff::find($accessToken->tokenable_id);

        if (! $staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff not found.',
                'debug' => [
                    'tokenable_id' => $accessToken->tokenable_id,
                ],
            ], 404);
        }

        // Paso 5: Establecer el staff como usuario autenticado
        Auth::guard('staff')->setUser($staff);
        
        // También establecerlo en la request para que $request->user() funcione
        $request->setUserResolver(function () use ($staff) {
            return $staff;
        });
        
        // Establecer el token en la request para que currentAccessToken() funcione
        $request->attributes->set('sanctum_token', $accessToken);

        return $next($request);
    }
}
