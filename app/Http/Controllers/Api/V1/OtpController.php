<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendOtpRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class OtpController extends Controller
{
    /**
     * Envía un código OTP por WhatsApp
     * Por ahora siempre devuelve 123456
     */
    public function send(SendOtpRequest $request): JsonResponse
    {
        $phone = $request->validated()['phone'];

        // Validar que el número exista en la base de datos (users o clientes)
        $userExists = User::where('phone', $phone)->exists();

        // TODO: Cuando tengamos tabla de clientes, agregar validación aquí
        // $customerExists = Customer::where('phone', $phone)->exists();

        if (! $userExists) {
            return response()->json([
                'success' => false,
                'message' => 'El número de teléfono no está registrado en el sistema.',
            ], 404);
        }

        // Invalidar OTPs anteriores pendientes del mismo teléfono
        Otp::where('phone', $phone)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        // Crear nuevo OTP (por ahora siempre 123456)
        $otp = Otp::create([
            'phone' => $phone,
            'code' => '123456', // Hardcoded por ahora
            'type' => 'whatsapp',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10), // Expira en 10 minutos
            'ip_address' => $request->ip(),
        ]);

        // TODO: Aquí irá la integración real con WhatsApp
        // Por ahora solo logueamos (en producción no deberíamos loguear el código)
        Log::info("OTP enviado por WhatsApp", [
            'phone' => $phone,
            'otp_id' => $otp->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Código OTP enviado exitosamente por WhatsApp.',
            'data' => [
                'expires_at' => $otp->expires_at->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Verifica un código OTP
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $phone = $validated['phone'];
        $code = $validated['code'];

        // Buscar OTP pendiente y no expirado
        $otp = Otp::where('phone', $phone)
            ->where('code', $code)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return response()->json([
                'success' => false,
                'message' => 'Código OTP inválido o expirado.',
            ], 400);
        }

        // Marcar como verificado
        $otp->markAsVerified();

        return response()->json([
            'success' => true,
            'message' => 'Código OTP verificado exitosamente.',
            'data' => [
                'verified_at' => $otp->verified_at->toIso8601String(),
            ],
        ], 200);
    }
}

