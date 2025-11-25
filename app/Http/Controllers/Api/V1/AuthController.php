<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group 🔐 Autenticación
 * 
 * Endpoints para autenticación de usuarios (owners/admins)
 */
class AuthController extends Controller
{
    /**
     * Login with email and password
     * 
     * @unauthenticated
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('business')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not active.',
            ], 403);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ], 200);
    }

    /**
     * Logout the authenticated user
     * 
     * @authenticated
     * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], 200);
    }

    /**
     * Get the authenticated user
     * 
     * @authenticated
     * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('business');
        
        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ], 200);
    }

    /**
     * Solicitar reset de contraseña
     * 
     * Envía un email con el link para resetear la contraseña.
     * 
     * @unauthenticated
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Siempre devolver éxito para evitar enumeración de emails
        if (!$user) {
            return response()->json([
                'success' => true,
                'message' => 'If that email address exists in our system, we have sent a password reset link.',
            ], 200);
        }

        // Usar Laravel Password Broker para enviar el email de reset
        $status = \Illuminate\Support\Facades\Password::sendResetLink(
            $request->only('email')
        );

        if ($status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email.',
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'If that email address exists in our system, we have sent a password reset link.',
        ], 200);
    }

    /**
     * Verificar token de autenticación
     * 
     * Verifica si el token de autenticación del cliente es válido.
     * Retorna el user_id y business_id si el token es válido.
     * 
     * Esta ruta se usa para verificar si el usuario está logueado al navegar.
     * 
     * @authenticated
     * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "user_id": "019aa527-9931-7349-a45d-18e6bf48aa1a",
     *     "business_id": "019aa527-a3dd-7342-b115-95e9fcbba615"
     *   }
     * }
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthenticated."
     * }
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user->load('business');

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'business_id' => $user->business?->id,
            ],
        ], 200);
    }

    /**
     * Resetear contraseña
     * 
     * Resetea la contraseña usando el token recibido por email.
     * 
     * **Requisitos:**
     * - `token`: Token recibido por email
     * - `email`: Email del usuario
     * - `password`: Nueva contraseña (mínimo 8 caracteres)
     * - `password_confirmation`: Confirmación de la nueva contraseña (debe coincidir con `password`)
     * 
     * @unauthenticated
     * @bodyParam token string required El token de reset recibido por email. Example: abc123def456
     * @bodyParam email string required El email del usuario. Example: user@example.com
     * @bodyParam password string required La nueva contraseña (mínimo 8 caracteres). Example: newpassword123
     * @bodyParam password_confirmation string required Confirmación de la nueva contraseña (debe coincidir con password). Example: newpassword123
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Password reset successfully."
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "Invalid or expired reset token."
     * }
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully.',
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired reset token.',
        ], 400);
    }

    /**
     * Verificar contraseña del usuario autenticado
     * 
     * Verifica si la contraseña proporcionada es correcta para el usuario autenticado.
     * 
     * @authenticated
     * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
     * @bodyParam password string required La contraseña a verificar. Example: mypassword123
     * 
     * @response 200 {
     *   "success": true,
     *   "valid": true,
     *   "message": "Password is correct."
     * }
     * @response 200 {
     *   "success": true,
     *   "valid": false,
     *   "message": "Password is incorrect."
     * }
     */
    public function verifyPassword(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();
        $isValid = Hash::check($request->password, $user->password);

        return response()->json([
            'success' => true,
            'valid' => $isValid,
            'message' => $isValid ? 'Password is correct.' : 'Password is incorrect.',
        ], 200);
    }

    /**
     * Obtener settings del dashboard del usuario autenticado
     * 
     * @authenticated
     * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "dashboard_settings": {}
     *   }
     * }
     */
    public function getDashboardSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'dashboard_settings' => $user->dashboard_settings ?? [],
            ],
        ], 200);
    }

    /**
     * Guardar/actualizar settings del dashboard del usuario autenticado
     * 
     * @authenticated
     * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
     * @bodyParam dashboard_settings object required JSON con los settings del dashboard. Example: {}
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Dashboard settings saved successfully.",
     *   "data": {
     *     "dashboard_settings": {}
     *   }
     * }
     */
    public function saveDashboardSettings(Request $request): JsonResponse
    {
        $request->validate([
            'dashboard_settings' => ['required', 'array'],
        ]);

        $user = $request->user();
        $user->dashboard_settings = $request->dashboard_settings;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard settings saved successfully.',
            'data' => [
                'dashboard_settings' => $user->dashboard_settings,
            ],
        ], 200);
    }
}
