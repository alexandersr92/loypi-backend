<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterCustomerToCampaignRequest;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\CustomerCampaign;
use App\Models\CustomerFieldValue;
use App\Models\Otp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @group 👥 Customers - Campaigns
 * 
 * Endpoints para gestionar la relación entre customers y campaigns
 */
class CustomerCampaignController extends Controller
{
    /**
     * Registrar customer a campaign (con QR)
     * 
     * Este endpoint se usa cuando un customer escanea un QR de una campaign.
     * 
     * **Flujo completo:**
     * 1. Customer escanea QR → obtiene campaign_code
     * 2. Frontend muestra formulario con campos (name, phone, + custom fields)
     * 3. Se envía todo en field_values (name y phone deben venir aquí)
     * 4. Se crea/actualiza customer, se crea customer_campaign con status='pending'
     * 5. Se guardan todos los field_values
     * 6. Se genera y envía OTP (expira en 3 minutos)
     * 7. Se crea token de customer
     * 8. Se retorna token, mensaje OTP, customer_id, campaign_id, status
     * 9. Frontend muestra ventana OTP
     * 10. Frontend envía OTP a /api/v1/otp/verify
     * 11. Al verificar OTP, se actualiza customer_campaign status='validated'
     * 
     * **Importante:**
     * - name y phone deben venir en field_values (identificados por el key del custom field)
     * - El customer se crea/actualiza automáticamente
     * - El customer_campaign se crea con status='pending'
     * - Después de verificar el OTP, el status cambia a 'validated'
     * 
     * @unauthenticated
     */
    public function register(RegisterCustomerToCampaignRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $campaignCode = strtoupper($validated['campaign_code']);
        $businessSlug = $validated['business_slug'];
        $fieldValues = $validated['field_values'];

        // Buscar campaign por código
        $campaign = Campaign::where('code', $campaignCode)
            ->with(['business', 'customFields'])
            ->firstOrFail();

        // Verificar que la campaign pertenezca al business
        $business = Business::where('slug', $businessSlug)->firstOrFail();
        if ($campaign->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'La campaign no pertenece a este negocio.',
            ], 422);
        }

        // Asegurar que la campaña tenga campos name y phone (crearlos si no existen)
        $nameField = $this->ensureDefaultField($campaign, $business, 'name', 'Nombre', 'text', true);
        $phoneField = $this->ensureDefaultField($campaign, $business, 'phone', 'Teléfono', 'text', true);
        
        // Recargar custom fields de la campaña
        $campaign->load('customFields');
        $customFieldsMap = $campaign->customFields->keyBy('id');
        $customFieldsMapByKey = $campaign->customFields->keyBy('key');

        // Extraer name y phone de field_values (soporta UUID o "default-name-field"/"default-phone-field")
        $name = null;
        $phone = null;
        $processedFieldValues = [];
        
        foreach ($fieldValues as $fieldValue) {
            $customFieldId = $fieldValue['custom_field_id'];
            $customField = null;
            
            // Detectar si es un campo default
            if ($customFieldId === 'default-name-field') {
                $customField = $nameField;
            } elseif ($customFieldId === 'default-phone-field') {
                $customField = $phoneField;
            } elseif (isset($customFieldsMap[$customFieldId])) {
                $customField = $customFieldsMap[$customFieldId];
            }
            
            if ($customField) {
                // Extraer name y phone
                if ($customField->key === 'name') {
                    $name = $fieldValue['string_value'] ?? null;
                } elseif ($customField->key === 'phone') {
                    $phone = $fieldValue['string_value'] ?? null;
                }
                
                // Agregar al array procesado con el UUID real
                $processedFieldValues[] = array_merge($fieldValue, [
                    'custom_field_id' => $customField->id,
                ]);
            } else {
                // Si no se encontró el campo, mantener el valor original (puede ser un error)
                $processedFieldValues[] = $fieldValue;
            }
        }

        // Validar que name y phone estén presentes
        if (!$name || !$phone) {
            return response()->json([
                'success' => false,
                'message' => 'Los campos name y phone son requeridos en field_values.',
            ], 422);
        }
        
        // Usar field_values procesados (con UUIDs reales)
        $fieldValues = $processedFieldValues;

        DB::beginTransaction();
        try {
            // Buscar o crear customer
            $customer = Customer::where('business_id', $business->id)
                ->where('phone', $phone)
                ->first();

            $isNewCustomer = !$customer;

            if ($isNewCustomer) {
                // Crear customer
                $customer = Customer::create([
                    'business_id' => $business->id,
                    'phone' => $phone,
                    'name' => $name,
                ]);
            } else {
                // Actualizar name si cambió
                if ($customer->name !== $name) {
                    $customer->update(['name' => $name]);
                }
            }

            // Verificar si ya existe customer_campaign
            $existingCustomerCampaign = CustomerCampaign::where('customer_id', $customer->id)
                ->where('campaign_id', $campaign->id)
                ->first();

            if ($existingCustomerCampaign) {
                if ($existingCustomerCampaign->status === 'validated') {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'El customer ya está registrado y validado en esta campaign.',
                    ], 422);
                }
                // Si está pending, actualizar
                $customerCampaign = $existingCustomerCampaign;
            } else {
                // Crear customer_campaign con status='pending'
                $customerCampaign = CustomerCampaign::create([
                    'customer_id' => $customer->id,
                    'campaign_id' => $campaign->id,
                    'stamps' => 0,
                    'status' => 'pending',
                ]);
            }

            // Guardar todos los field_values
            foreach ($fieldValues as $fieldValue) {
                $customFieldId = $fieldValue['custom_field_id'];
                
                // Verificar que el custom field pertenezca a la campaign
                if (!isset($customFieldsMap[$customFieldId])) {
                    throw new \Exception("El custom field no pertenece a esta campaign.");
                }

                // Buscar o crear CustomerFieldValue
                $existingValue = CustomerFieldValue::where('customer_id', $customer->id)
                    ->where('campaign_id', $campaign->id)
                    ->where('custom_field_id', $customFieldId)
                    ->first();

                if ($existingValue) {
                    $existingValue->update([
                        'string_value' => $fieldValue['string_value'] ?? null,
                        'number_value' => $fieldValue['number_value'] ?? null,
                        'date_value' => $fieldValue['date_value'] ?? null,
                        'boolean_value' => $fieldValue['boolean_value'] ?? null,
                    ]);
                } else {
                    CustomerFieldValue::create([
                        'business_id' => $business->id,
                        'customer_id' => $customer->id,
                        'campaign_id' => $campaign->id,
                        'custom_field_id' => $customFieldId,
                        'string_value' => $fieldValue['string_value'] ?? null,
                        'number_value' => $fieldValue['number_value'] ?? null,
                        'date_value' => $fieldValue['date_value'] ?? null,
                        'boolean_value' => $fieldValue['boolean_value'] ?? null,
                    ]);
                }
            }

            // Generar y enviar OTP
            $otpResult = $this->sendOtp($phone);

            // Crear token de customer
            $token = $customer->createToken('customer-token')->plainTextToken;

            // Guardar token en customer_tokens
            \App\Models\CustomerToken::create([
                'customer_id' => $customer->id,
                'business_id' => $business->id,
                'token' => explode('|', $token)[1],
                'expires_at' => null,
                'active' => true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer registrado. Se ha enviado un código OTP.',
                'data' => [
                    'token' => $token,
                    'otp_sent' => $otpResult['success'] ?? false,
                    'otp_message' => $otpResult['message'] ?? 'Código OTP enviado.',
                    'customer_id' => $customer->id,
                    'campaign_id' => $campaign->id,
                    'status' => $customerCampaign->status,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar customer en la campaign: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener relación del customer autenticado con una campaign específica
     * 
     * Retorna toda la información de la relación entre el customer autenticado y una campaign,
     * incluyendo stamps, status, validated_at, y demás datos relevantes.
     * 
     * @authenticated
     * @guard customer
     */
    public function myCampaign(Request $request, string $campaignId): JsonResponse
    {
        $customer = $request->user();

        $customerCampaign = CustomerCampaign::where('customer_id', $customer->id)
            ->where('campaign_id', $campaignId)
            ->with(['campaign.business', 'campaign.rewards', 'stamps.staff'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customerCampaign->id,
                'status' => $customerCampaign->status,
                'validated_at' => $customerCampaign->validated_at?->toIso8601String(),
                'stamps' => $customerCampaign->stamps,
                'redeemed_at' => $customerCampaign->redeemed_at?->toIso8601String(),
                'created_at' => $customerCampaign->created_at->toIso8601String(),
                'updated_at' => $customerCampaign->updated_at->toIso8601String(),
                'customer' => [
                    'id' => $customer->id,
                    'short_code' => $customer->short_code,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                ],
                'campaign' => [
                    'id' => $customerCampaign->campaign->id,
                    'code' => $customerCampaign->campaign->code,
                    'name' => $customerCampaign->campaign->name,
                    'type' => $customerCampaign->campaign->type,
                    'description' => $customerCampaign->campaign->description,
                    'required_stamps' => $customerCampaign->campaign->required_stamps ?? null,
                    'business' => [
                        'id' => $customerCampaign->campaign->business->id,
                        'slug' => $customerCampaign->campaign->business->slug,
                        'name' => $customerCampaign->campaign->business->name,
                    ],
                ],
                'stamps_history' => $customerCampaign->stamps()->with('staff')->get()->map(function ($stamp) {
                    return [
                        'id' => $stamp->id,
                        'type' => $stamp->type,
                        'staff' => $stamp->staff ? [
                            'id' => $stamp->staff->id,
                            'code' => $stamp->staff->code,
                            'name' => $stamp->staff->name,
                        ] : null,
                        'meta' => $stamp->meta,
                        'created_at' => $stamp->created_at->toIso8601String(),
                    ];
                }),
            ],
        ], 200);
    }

    /**
     * Listar campaigns del customer autenticado
     * 
     * @authenticated
     * @guard customer
     */
    public function myCampaigns(Request $request): JsonResponse
    {
        $customer = $request->user();

        $customerCampaigns = CustomerCampaign::where('customer_id', $customer->id)
            ->with(['campaign.business', 'campaign.rewards', 'campaign.customFields'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customerCampaigns->map(function ($customerCampaign) {
                return [
                    'id' => $customerCampaign->id,
                    'stamps' => $customerCampaign->stamps,
                    'status' => $customerCampaign->status,
                    'validated_at' => $customerCampaign->validated_at?->toIso8601String(),
                    'redeemed_at' => $customerCampaign->redeemed_at?->toIso8601String(),
                    'campaign' => [
                        'id' => $customerCampaign->campaign->id,
                        'code' => $customerCampaign->campaign->code,
                        'name' => $customerCampaign->campaign->name,
                        'type' => $customerCampaign->campaign->type,
                        'description' => $customerCampaign->campaign->description,
                        'business' => [
                            'id' => $customerCampaign->campaign->business->id,
                            'slug' => $customerCampaign->campaign->business->slug,
                            'name' => $customerCampaign->campaign->business->name,
                        ],
                    ],
                    'created_at' => $customerCampaign->created_at->toIso8601String(),
                ];
            }),
        ], 200);
    }

    /**
     * Obtener relación completa entre customer y campaign
     * 
     * Retorna toda la información de la relación entre un customer específico y una campaign,
     * incluyendo stamps, status, validated_at, y demás datos relevantes.
     * 
     * @authenticated
     */
    public function show(Request $request, string $customerId, string $campaignId): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view customer campaign relationship.',
            ], 403);
        }

        $customerCampaign = CustomerCampaign::where('customer_id', $customerId)
            ->where('campaign_id', $campaignId)
            ->whereHas('customer', function ($query) use ($user) {
                $query->where('business_id', $user->business->id);
            })
            ->whereHas('campaign', function ($query) use ($user) {
                $query->where('business_id', $user->business->id);
            })
            ->with(['customer', 'campaign', 'stamps.staff'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customerCampaign->id,
                'status' => $customerCampaign->status,
                'validated_at' => $customerCampaign->validated_at?->toIso8601String(),
                'stamps' => $customerCampaign->stamps,
                'redeemed_at' => $customerCampaign->redeemed_at?->toIso8601String(),
                'created_at' => $customerCampaign->created_at->toIso8601String(),
                'updated_at' => $customerCampaign->updated_at->toIso8601String(),
                'customer' => [
                    'id' => $customerCampaign->customer->id,
                    'short_code' => $customerCampaign->customer->short_code,
                    'name' => $customerCampaign->customer->name,
                    'phone' => $customerCampaign->customer->phone,
                ],
                'campaign' => [
                    'id' => $customerCampaign->campaign->id,
                    'code' => $customerCampaign->campaign->code,
                    'name' => $customerCampaign->campaign->name,
                    'type' => $customerCampaign->campaign->type,
                    'description' => $customerCampaign->campaign->description,
                    'required_stamps' => $customerCampaign->campaign->required_stamps ?? null,
                ],
                'stamps_history' => $customerCampaign->stamps()->with('staff')->get()->map(function ($stamp) {
                    return [
                        'id' => $stamp->id,
                        'type' => $stamp->type,
                        'staff' => [
                            'id' => $stamp->staff->id,
                            'code' => $stamp->staff->code,
                            'name' => $stamp->staff->name,
                        ],
                        'meta' => $stamp->meta,
                        'created_at' => $stamp->created_at->toIso8601String(),
                    ];
                }),
            ],
        ], 200);
    }

    /**
     * Listar customers de una campaign (Owner)
     * 
     * @authenticated
     */
    public function campaignCustomers(Request $request, string $campaignId): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view campaign customers.',
            ], 403);
        }

        $campaign = Campaign::where('id', $campaignId)
            ->where('business_id', $user->business->id)
            ->firstOrFail();

        $customerCampaigns = CustomerCampaign::where('campaign_id', $campaign->id)
            ->with(['customer.business'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customerCampaigns->map(function ($customerCampaign) {
                return [
                    'id' => $customerCampaign->id,
                    'stamps' => $customerCampaign->stamps,
                    'redeemed_at' => $customerCampaign->redeemed_at?->toIso8601String(),
                    'customer' => [
                        'id' => $customerCampaign->customer->id,
                        'short_code' => $customerCampaign->customer->short_code,
                        'phone' => $customerCampaign->customer->phone,
                        'name' => $customerCampaign->customer->name,
                    ],
                    'created_at' => $customerCampaign->created_at->toIso8601String(),
                ];
            }),
        ], 200);
    }

    /**
     * Método privado para enviar OTP
     */
    private function sendOtp(string $phone): array
    {
        // Invalidar OTPs anteriores pendientes del mismo teléfono
        Otp::where('phone', $phone)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        // Modo desarrollo: usar código fijo 123456
        if (app()->environment('local') || config('app.debug')) {
            $otp = Otp::create([
                'phone' => $phone,
                'code' => '123456',
                'type' => 'sms',
                'status' => 'pending',
                'expires_at' => now()->addMinutes(3),
                'ip_address' => request()->ip(),
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

            return [
                'success' => true,
                'message' => 'Código OTP enviado exitosamente. (Modo desarrollo: usar código 123456)',
            ];
        }

        try {
            // Usar Twilio Verify para enviar OTP (solo en producción)
            $twilioSid = config('services.twilio.account_sid');
            $twilioAuthToken = config('services.twilio.auth_token');
            $twilioServiceSid = config('services.twilio.verify_service_sid');

            if (! $twilioSid || ! $twilioAuthToken || ! $twilioServiceSid) {
                Log::error('Twilio credentials not configured');
                return [
                    'success' => false,
                    'message' => 'Error de configuración. Por favor contacte al administrador.',
                ];
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
                'type' => 'sms',
                'status' => 'pending',
                'expires_at' => now()->addMinutes(3), // Expira en 3 minutos
                'ip_address' => request()->ip(),
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

            return [
                'success' => true,
                'message' => 'Código OTP enviado exitosamente.',
            ];
        } catch (\Exception $e) {
            Log::error('Error al enviar OTP', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar el código OTP. Por favor intenta nuevamente.',
            ];
        }
    }

    /**
     * Asegura que exista un custom field con el key especificado en la campaña
     * Si no existe, lo crea y lo asocia a la campaña
     */
    private function ensureDefaultField(Campaign $campaign, Business $business, string $key, string $label, string $type = 'text', bool $required = true): \App\Models\CustomField
    {
        // Buscar si ya existe un campo con este key en la campaña
        $existingField = $campaign->customFields()->where('key', $key)->first();
        
        if ($existingField) {
            return $existingField;
        }
        
        // Buscar si existe en el business pero no está asociado a la campaña
        $businessField = \App\Models\CustomField::where('business_id', $business->id)
            ->where('key', $key)
            ->first();
        
        if ($businessField) {
            // Asociar a la campaña
            $campaign->customFields()->attach($businessField->id, [
                'sort_order' => 0,
                'required_override' => null,
            ]);
            return $businessField;
        }
        
        // Crear nuevo campo
        $field = \App\Models\CustomField::create([
            'business_id' => $business->id,
            'key' => $key,
            'label' => $label,
            'description' => "Campo requerido para {$label}",
            'type' => $type,
            'required' => $required,
            'active' => true,
        ]);
        
        // Asociar a la campaña
        $campaign->customFields()->attach($field->id, [
            'sort_order' => 0,
            'required_override' => null,
        ]);
        
        return $field;
    }
}
