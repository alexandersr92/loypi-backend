<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffAuthController extends Controller
{
    public function login(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        $business = Business::where('slug', $slug)->firstOrFail();

        $staff = Staff::where('business_id', $business->id)
            ->where('active', true)
            ->get()
            ->first(function ($staff) use ($request) {
                return $staff->verifyPasscode($request->pin);
            });

        if (!$staff) {
            throw ValidationException::withMessages([
                'pin' => ['PIN inválido o staff inactivo.'],
            ]);
        }

        // Crear token Sanctum
        $token = $staff->createToken('staff-token', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'staff' => $staff->makeHidden(['passcode_hash', 'created_at', 'updated_at']),
            'token' => $token,
            'business' => $business->makeHidden(['created_at', 'updated_at']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $staff = $request->user('staff');
        
        if ($staff) {
            $staff->currentAccessToken()?->delete();
        }

        return response()->json(['message' => 'Sesión cerrada exitosamente']);
    }

    public function me(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        $staff = $request->user('staff');

        if (!$staff || $staff->business_id !== $business->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json([
            'staff' => $staff->makeHidden(['passcode_hash', 'created_at', 'updated_at']),
            'business' => $business->makeHidden(['created_at', 'updated_at']),
        ]);
    }
}

