<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OtpService
{
    private const OTP_EXPIRY_MINUTES = 10;
    private const OTP_RATE_LIMIT_MINUTES = 1;

    public function generateOtp(string $phone): string
    {
        // ⚠️ TEMPORAL: OTP fijo para desarrollo - ELIMINAR EN PRODUCCIÓN
        // TODO: Eliminar esta línea y descomentar la siguiente cuando se integre WhatsApp
        $otp = '123456'; // OTP fijo para pruebas
        // $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        $cacheKey = "otp:{$phone}";
        $rateLimitKey = "otp_rate_limit:{$phone}";
        
        // Rate limiting
        if (Cache::has($rateLimitKey)) {
            throw new \Exception('Por favor espera un momento antes de solicitar otro código.');
        }
        
        Cache::put($cacheKey, $otp, now()->addMinutes(self::OTP_EXPIRY_MINUTES));
        Cache::put($rateLimitKey, true, now()->addMinutes(self::OTP_RATE_LIMIT_MINUTES));
        
        // ⚠️ TEMPORAL: Comentado para desarrollo - DESCOMENTAR cuando se integre WhatsApp
        // Aquí integrarías con tu servicio de WhatsApp
        // $this->sendWhatsAppOtp($phone, $otp);
        
        // ⚠️ TEMPORAL: Log para desarrollo - ELIMINAR EN PRODUCCIÓN
        Log::info("OTP generado para {$phone}: {$otp} (MODO DESARROLLO - OTP FIJO)");
        
        return $otp;
    }

    public function verifyOtp(string $phone, string $otp): bool
    {
        // ⚠️ TEMPORAL: Aceptar OTP fijo en desarrollo - ELIMINAR EN PRODUCCIÓN
        // TODO: Eliminar esta validación cuando se integre WhatsApp
        if ($otp === '123456') {
            $cacheKey = "otp:{$phone}";
            Cache::forget($cacheKey);
            Log::info("OTP verificado para {$phone} (MODO DESARROLLO - OTP FIJO)");
            return true;
        }
        
        $cacheKey = "otp:{$phone}";
        $storedOtp = Cache::get($cacheKey);
        
        if (!$storedOtp) {
            return false;
        }
        
        if ($storedOtp !== $otp) {
            return false;
        }
        
        Cache::forget($cacheKey);
        return true;
    }

    private function sendWhatsAppOtp(string $phone, string $otp): void
    {
        // TODO: Integrar con servicio de WhatsApp (Twilio, MessageBird, etc.)
        // Por ahora solo logueamos
        Log::info("OTP enviado a {$phone}: {$otp}");
        
        // Ejemplo de integración:
        // $client = new \Twilio\Rest\Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
        // $client->messages->create(
        //     "whatsapp:{$phone}",
        //     [
        //         'from' => 'whatsapp:' . env('TWILIO_WHATSAPP_NUMBER'),
        //         'body' => "Tu código de verificación es: {$otp}"
        //     ]
        // );
    }
}

