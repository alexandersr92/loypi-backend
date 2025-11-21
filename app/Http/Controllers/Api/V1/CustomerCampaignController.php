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
     * El flujo es:
     * 1. Customer escanea QR → obtiene campaign_code
     * 2. Si el customer es nuevo: requiere phone, name, business_slug, otp_code
     * 3. Si el customer ya existe: solo requiere phone, business_slug
     * 4. Se registra automáticamente en la campaign
     * 5. Se guardan los valores de custom fields si se proporcionan
     * 
     * @unauthenticated
     */
    public function register(RegisterCustomerToCampaignRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $campaignCode = strtoupper($validated['campaign_code']);
        $phone = $validated['phone'];
        $businessSlug = $validated['business_slug'];
        $name = $validated['name'] ?? null;
        $otpCode = $validated['otp_code'] ?? null;
        $fieldValues = $validated['field_values'] ?? [];

        // Buscar campaign por código
        $campaign = Campaign::where('code', $campaignCode)
            ->with(['business', 'customFields.options', 'customFields.validations'])
            ->firstOrFail();

        // Verificar que la campaign pertenezca al business
        $business = Business::where('slug', $businessSlug)->firstOrFail();
        if ($campaign->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'La campaign no pertenece a este negocio.',
            ], 422);
        }

        // Verificar si el customer ya existe
        $customer = Customer::where('business_id', $business->id)
            ->where('phone', $phone)
            ->first();

        $isNewCustomer = !$customer;

        DB::beginTransaction();
        try {
            $token = null;
            
            // Si es un customer nuevo, crearlo
            if ($isNewCustomer) {
                if (!$name || !$otpCode) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Para nuevos customers se requiere name y otp_code.',
                    ], 422);
                }

                // Verificar OTP
                $otp = Otp::where('phone', $phone)
                    ->where('code', $otpCode)
                    ->where('status', 'pending')
                    ->where('expires_at', '>', now())
                    ->latest()
                    ->first();

                if (!$otp) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Código OTP inválido o expirado.',
                    ], 400);
                }

                // Crear customer
                $customer = Customer::create([
                    'business_id' => $business->id,
                    'phone' => $phone,
                    'name' => $name,
                ]);

                // Marcar OTP como verificado
                $otp->markAsVerified();

                // Generar token
                $token = $customer->createToken('customer-token')->plainTextToken;

                // Guardar token en customer_tokens
                \App\Models\CustomerToken::create([
                    'customer_id' => $customer->id,
                    'business_id' => $business->id,
                    'token' => explode('|', $token)[1],
                    'expires_at' => null,
                    'active' => true,
                ]);
            }

            // Verificar si ya está registrado en la campaign
            $existingRegistration = CustomerCampaign::where('customer_id', $customer->id)
                ->where('campaign_id', $campaign->id)
                ->first();

            if ($existingRegistration) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'El customer ya está registrado en esta campaign.',
                ], 422);
            }

            // Registrar customer en la campaign
            $customerCampaign = CustomerCampaign::create([
                'customer_id' => $customer->id,
                'campaign_id' => $campaign->id,
                'stamps' => 0,
            ]);

            // Guardar custom field values si se proporcionaron
            if (!empty($fieldValues)) {
                foreach ($fieldValues as $fieldValue) {
                    // Verificar que el custom field pertenezca a la campaign
                    $customField = $campaign->customFields()
                        ->where('custom_fields.id', $fieldValue['custom_field_id'])
                        ->first();

                    if (!$customField) {
                        throw new \Exception("El custom field no pertenece a esta campaign.");
                    }

                    // Verificar si ya existe un valor para este campo
                    $existingValue = CustomerFieldValue::where('customer_id', $customer->id)
                        ->where('campaign_id', $campaign->id)
                        ->where('custom_field_id', $fieldValue['custom_field_id'])
                        ->first();

                    if ($existingValue) {
                        // Actualizar valor existente
                        $existingValue->update([
                            'string_value' => $fieldValue['string_value'] ?? null,
                            'number_value' => $fieldValue['number_value'] ?? null,
                            'date_value' => $fieldValue['date_value'] ?? null,
                            'boolean_value' => $fieldValue['boolean_value'] ?? null,
                        ]);
                    } else {
                        // Crear nuevo valor
                        CustomerFieldValue::create([
                            'business_id' => $business->id,
                            'customer_id' => $customer->id,
                            'campaign_id' => $campaign->id,
                            'custom_field_id' => $fieldValue['custom_field_id'],
                            'string_value' => $fieldValue['string_value'] ?? null,
                            'number_value' => $fieldValue['number_value'] ?? null,
                            'date_value' => $fieldValue['date_value'] ?? null,
                            'boolean_value' => $fieldValue['boolean_value'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            // Cargar relaciones para la respuesta
            $customer->load('business');
            $campaign->load(['business', 'rewards', 'customFields.options']);

            return response()->json([
                'success' => true,
                'message' => $isNewCustomer 
                    ? 'Customer creado y registrado en la campaign exitosamente.' 
                    : 'Customer registrado en la campaign exitosamente.',
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'short_code' => $customer->short_code,
                        'phone' => $customer->phone,
                        'name' => $customer->name,
                    ],
                    'campaign' => [
                        'id' => $campaign->id,
                        'code' => $campaign->code,
                        'name' => $campaign->name,
                        'type' => $campaign->type,
                    ],
                    'registration' => [
                        'id' => $customerCampaign->id,
                        'stamps' => $customerCampaign->stamps,
                        'created_at' => $customerCampaign->created_at->toIso8601String(),
                    ],
                    'token' => $token,
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
}
