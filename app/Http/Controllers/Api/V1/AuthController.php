<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login with email and password
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
     * Login with OTP (after OTP verification)
     */
    public function loginWithOtp(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $phone = $validated['phone'];
        $code = $validated['code'];

        // Verify OTP
        $otp = Otp::where('phone', $phone)
            ->where('code', $code)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP code.',
            ], 400);
        }

        // Find user by phone
        $user = User::with('business')->where('phone', $phone)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not active.',
            ], 403);
        }

        // Mark OTP as verified
        $otp->markAsVerified();

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
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('business');
        
        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ], 200);
    }
}
