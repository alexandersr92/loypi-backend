<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendOtpRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Models\Customer;
use App\Models\CustomerCampaign;
use App\Models\Otp;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @group 📱 OTP
 * 
 * Endpoints para envío y verificación de códigos OTP usando Twilio Verify.
 * 
 * El sistema utiliza Twilio Verify para enviar y verificar códigos OTP vía SMS.
 * Estos endpoints están disponibles únicamente para Customers (no para Owners/Users).
 * 
 * **Modo Desarrollo:**
 * En modo desarrollo (APP_ENV=local o APP_DEBUG=true), el sistema OTP está desactivado:
 * - No se envían SMS reales
 * - El código OTP siempre es: **123456**
 * - Usa este código para todas las verificaciones en desarrollo
 * 
 * **Flujo de autenticación:**
 * 1. Envía un OTP con `/api/v1/otp/send`
 * 2. Recibe el código en tu teléfono vía SMS (o usa 123456 en desarrollo)
 * 3. Verifica el código con `/api/v1/otp/verify`
 * 4. Una vez verificado, puedes registrar o hacer login del cliente
 */
class OtpController extends Controller
{
    /**
     * Envía un código OTP usando Twilio Verify (solo para customers)
     * 
     * Este endpoint envía un código OTP vía SMS usando Twilio Verify al número de teléfono proporcionado.
     * El código se enviará automáticamente al teléfono del cliente.
     * 
     * **Requisitos:**
     * - El número de teléfono debe estar registrado como Customer
     * - Se requiere tener las credenciales de Twilio configuradas
     * 
     * **Flujo:**
     * 1. Llama a este endpoint para enviar el OTP
     * 2. Recibirás el código OTP en tu teléfono vía SMS (o usa 123456 en desarrollo)
     * 3. Usa el código recibido en el endpoint `/api/v1/otp/verify`
     * 
     * **Nota:** En modo desarrollo, el código siempre es **123456** y no se envía SMS.
     * 
     * @unauthenticated
     * @bodyParam phone string required El número de teléfono del cliente (formato internacional, ej: +521234567890). Example: +521234567890
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Código OTP enviado exitosamente.",
     *   "data": {
     *     "expires_at": "2025-01-15T10:10:00Z"
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "El número de teléfono no está registrado como cliente."
     * }
     * @response 500 {
     *   "success": false,
     *   "message": "Error al enviar el código OTP. Por favor intenta nuevamente."
     * }
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

        // Modo desarrollo: usar código fijo 123456
        if (app()->environment('local') || config('app.debug')) {
            $otp = Otp::create([
                'phone' => $phone,
                'code' => '123456', // Código fijo en desarrollo
                'type' => 'sms',
                'status' => 'pending',
                'expires_at' => now()->addMinutes(3),
                'ip_address' => $request->ip(),
                'meta' => [
                    'development_mode' => true,
                    'note' => 'OTP desactivado en modo desarrollo. Usar código: 123456',
                ],
            ]);

            Log::info("OTP generado en modo desarrollo", [
                'phone' => $phone,
                'otp_id' => $otp->id,
                'code' => '123456',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Código OTP enviado exitosamente. (Modo desarrollo: usar código 123456)',
                'data' => [
                    'expires_at' => $otp->expires_at->toIso8601String(),
                ],
            ], 200);
        }

        try {
            // Usar Twilio Verify para enviar OTP (solo en producción)
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
                'expires_at' => now()->addMinutes(3), // Expira en 10 minutos
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
     * Este endpoint verifica el código OTP recibido vía SMS.
     * El código debe ser el que recibiste después de llamar al endpoint `/api/v1/otp/send`.
     * 
     * **Importante:**
     * - El código OTP expira en 10 minutos
     * - Solo puedes verificar un código una vez
     * - Después de verificar el OTP, puedes proceder a registrar o hacer login del cliente
     * 
     * @unauthenticated
     * @bodyParam phone string required El número de teléfono del cliente (formato internacional, ej: +521234567890). Example: +521234567890
     * @bodyParam code string required El código OTP recibido vía SMS. Example: 123456
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Código OTP verificado exitosamente.",
     *   "data": {
     *     "verified_at": "2025-01-15T10:05:00Z"
     *   }
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "Código OTP inválido o expirado."
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "Este endpoint solo está disponible para clientes."
     * }
     * @response 500 {
     *   "success": false,
     *   "message": "Error al verificar el código OTP. Por favor intenta nuevamente."
     * }
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $phone = $validated['phone'];
        $code = $validated['otp'];

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

        // Modo desarrollo: aceptar siempre 123456
        if (app()->environment('local') || config('app.debug')) {
            // Verificar que el código sea 123456
            if ($code !== '123456') {
                return response()->json([
                    'success' => false,
                    'message' => 'Código OTP inválido. En modo desarrollo, usar código: 123456',
                ], 400);
            }

            // Marcar como verificado
            $otp->update([
                'code' => $code,
                'status' => 'verified',
                'verified_at' => now(),
                'meta' => array_merge($otp->meta ?? [], [
                    'development_mode' => true,
                    'verified_in_dev' => true,
                ]),
            ]);

            Log::info("OTP verificado en modo desarrollo", [
                'phone' => $phone,
                'otp_id' => $otp->id,
                'code' => $code,
            ]);

            // Actualizar customer_campaign si existe uno pendiente
            $this->updateCustomerCampaignStatus($phone);

            return response()->json([
                'success' => true,
                'message' => 'Código OTP verificado exitosamente.',
                'data' => [
                    'verified_at' => $otp->verified_at->toIso8601String(),
                ],
            ], 200);
        }

        try {
            // Verificar código usando Twilio Verify (solo en producción)
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

            // Actualizar customer_campaign si existe uno pendiente
            $this->updateCustomerCampaignStatus($phone);

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

    /**
     * Actualiza el status del customer_campaign más reciente con status='pending' para el customer
     */
    private function updateCustomerCampaignStatus(string $phone): void
    {
        $customer = Customer::where('phone', $phone)->first();
        
        if (!$customer) {
            return;
        }

        // Buscar el customer_campaign más reciente con status='pending' para este customer
        $customerCampaign = CustomerCampaign::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($customerCampaign) {
            $customerCampaign->update([
                'status' => 'validated',
                'validated_at' => now(),
            ]);

            Log::info("Customer campaign validado después de verificar OTP", [
                'customer_id' => $customer->id,
                'customer_campaign_id' => $customerCampaign->id,
                'campaign_id' => $customerCampaign->campaign_id,
            ]);
        }
    }
}

