<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * Resetear contraseña
     * 
     * Resetea la contraseña usando el token recibido por email.
     * 
     * @unauthenticated
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
}
