<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendOtpRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Models\Customer;
use App\Models\Otp;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @group 📱 OTP
 * 
 * Endpoints para envío y verificación de códigos OTP
 */
class OtpController extends Controller
{
    /**
     * Envía un código OTP usando Twilio Verify (solo para customers)
     * 
     * @unauthenticated
     */
    public function send(SendOtpRequest $request): JsonResponse
    {
        $phone = $request->validated()['phone'];

        // Validar que el número exista como customer (no como owner/user)
        $customerExists = Customer::where('phone', $phone)->exists();

        if (! $customerExists) {
            return response()->json([
                'success' => false,
                'message' => 'El número de teléfono no está registrado como cliente.',
            ], 404);
        }

        // Invalidar OTPs anteriores pendientes del mismo teléfono
        Otp::where('phone', $phone)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        try {
            // Usar Twilio Verify para enviar OTP
            $twilioSid = config('services.twilio.account_sid');
            $twilioAuthToken = config('services.twilio.auth_token');
            $twilioServiceSid = config('services.twilio.verify_service_sid');

            if (! $twilioSid || ! $twilioAuthToken || ! $twilioServiceSid) {
                Log::error('Twilio credentials not configured');
                return response()->json([
                    'success' => false,
                    'message' => 'Error de configuración. Por favor contacte al administrador.',
                ], 500);
            }

            // Enviar OTP usando Twilio Verify
            $twilio = new \Twilio\Rest\Client($twilioSid, $twilioAuthToken);
            $verification = $twilio->verify->v2->services($twilioServiceSid)
                ->verifications
                ->create($phone, 'sms');

            // Guardar referencia del OTP en la base de datos
            $otp = Otp::create([
                'phone' => $phone,
                'code' => null, // Twilio maneja el código
                'type' => 'sms', // Usando SMS vía Twilio Verify
                'status' => 'pending',
                'expires_at' => now()->addMinutes(10), // Expira en 10 minutos
                'ip_address' => $request->ip(),
                'meta' => [
                    'twilio_sid' => $verification->sid,
                    'twilio_status' => $verification->status,
                ],
            ]);

            Log::info("OTP enviado vía Twilio Verify", [
                'phone' => $phone,
                'otp_id' => $otp->id,
                'twilio_sid' => $verification->sid,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Código OTP enviado exitosamente.',
                'data' => [
                    'expires_at' => $otp->expires_at->toIso8601String(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error sending OTP via Twilio', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el código OTP. Por favor intenta nuevamente.',
            ], 500);
        }
    }

    /**
     * Verifica un código OTP usando Twilio Verify (solo para customers)
     * 
     * @unauthenticated
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $phone = $validated['phone'];
        $code = $validated['code'];

        // Buscar OTP pendiente y no expirado
        $otp = Otp::where('phone', $phone)
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

        // Verificar que sea un customer
        $customerExists = Customer::where('phone', $phone)->exists();
        if (! $customerExists) {
            return response()->json([
                'success' => false,
                'message' => 'Este endpoint solo está disponible para clientes.',
            ], 403);
        }

        try {
            // Verificar código usando Twilio Verify
            $twilioSid = config('services.twilio.account_sid');
            $twilioAuthToken = config('services.twilio.auth_token');
            $twilioServiceSid = config('services.twilio.verify_service_sid');

            if (! $twilioSid || ! $twilioAuthToken || ! $twilioServiceSid) {
                Log::error('Twilio credentials not configured');
                return response()->json([
                    'success' => false,
                    'message' => 'Error de configuración. Por favor contacte al administrador.',
                ], 500);
            }

            $twilio = new \Twilio\Rest\Client($twilioSid, $twilioAuthToken);
            
            // Obtener el SID de Twilio del OTP
            $twilioSidFromOtp = $otp->meta['twilio_sid'] ?? null;
            
            // Verificar el código con Twilio
            $verificationCheck = $twilio->verify->v2->services($twilioServiceSid)
                ->verificationChecks
                ->create([
                    'to' => $phone,
                    'code' => $code,
                ]);

            if ($verificationCheck->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Código OTP inválido.',
                ], 400);
            }

            // Marcar como verificado
            $otp->update([
                'code' => $code, // Guardar el código usado
                'status' => 'verified',
                'verified_at' => now(),
                'meta' => array_merge($otp->meta ?? [], [
                    'twilio_verification_check_sid' => $verificationCheck->sid,
                    'twilio_verification_status' => $verificationCheck->status,
                ]),
            ]);

            Log::info("OTP verificado vía Twilio Verify", [
                'phone' => $phone,
                'otp_id' => $otp->id,
                'twilio_check_sid' => $verificationCheck->sid,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Código OTP verificado exitosamente.',
                'data' => [
                    'verified_at' => $otp->verified_at->toIso8601String(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error verifying OTP via Twilio', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el código OTP. Por favor intenta nuevamente.',
            ], 500);
        }
    }
}

