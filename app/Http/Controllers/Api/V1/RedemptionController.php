<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GenerateRedemptionPinRequest;
use App\Http\Requests\Api\V1\RedeemRewardRequest;
use App\Http\Requests\Api\V1\VerifyRedemptionPinRequest;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\CustomerCampaign;
use App\Models\Redemption;
use App\Models\RedemptionPin;
use App\Models\RewardUnlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group ✅ Redemptions
 * 
 * Endpoints para canjear premios desbloqueados
 * 
 * @authenticated
 */
class RedemptionController extends Controller
{
    /**
     * Generar PIN para canjear premio (Customer)
     * 
     * El cliente presiona "Canjear" en un premio desbloqueado.
     * Se genera un PIN de 4 dígitos válido por 3 minutos.
     * 
     * @authenticated
     * @guard customer
     */
    public function generatePin(GenerateRedemptionPinRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $unlockId = $validated['unlock_id'];
        $customer = $request->user();

        DB::beginTransaction();
        try {
            // Buscar el unlock
            $unlock = RewardUnlock::where('id', $unlockId)
                ->whereHas('customerCampaign', function ($query) use ($customer) {
                    $query->where('customer_id', $customer->id);
                })
                ->with(['customerCampaign', 'reward', 'campaignReward'])
                ->firstOrFail();

            // Verificar que el unlock esté disponible
            if (!$unlock->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este premio no está disponible para canjear.',
                ], 422);
            }

            // Verificar si ya existe un PIN activo para este unlock
            $existingRedemption = Redemption::where('reward_unlock_id', $unlock->id)
                ->whereHas('redemptionPin', function ($query) {
                    $query->where('expires_at', '>', now())
                        ->whereNull('verified_at');
                })
                ->first();

            if ($existingRedemption && $existingRedemption->redemptionPin) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ya existe un PIN activo para este premio.',
                    'data' => [
                        'pin' => $existingRedemption->redemptionPin->pin,
                        'expires_at' => $existingRedemption->redemptionPin->expires_at->toIso8601String(),
                    ],
                ], 200);
            }

            // Generar PIN de 4 dígitos
            $pin = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

            // Crear redemption (temporal, se confirmará después)
            $redemption = Redemption::create([
                'reward_unlock_id' => $unlock->id,
                'customer_campaign_id' => $unlock->customer_campaign_id,
                'reward_id' => $unlock->reward_id,
                'staff_id' => null, // Se asignará cuando el staff confirme
                'pin_code' => $pin,
                'confirmed_at' => null, // Se confirmará cuando el staff canjee
            ]);

            // Crear redemption_pin
            $redemptionPin = RedemptionPin::create([
                'redemption_id' => $redemption->id,
                'pin' => $pin,
                'expires_at' => now()->addMinutes(3),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'PIN generado exitosamente. Válido por 3 minutos.',
                'data' => [
                    'pin' => $pin,
                    'expires_at' => $redemptionPin->expires_at->toIso8601String(),
                    'unlock' => [
                        'id' => $unlock->id,
                        'reward' => [
                            'id' => $unlock->reward->id,
                            'name' => $unlock->reward->name,
                            'description' => $unlock->reward->description,
                        ],
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al generar PIN: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar PIN y mostrar premio (Staff)
     * 
     * El staff escanea el QR del cliente y luego ingresa el PIN.
     * El sistema valida el PIN y muestra el premio ganado.
     * 
     * @authenticated
     * @guard staff
     */
    public function verifyPin(VerifyRedemptionPinRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $customerCode = strtoupper($validated['customer_code']);
        $campaignCode = strtoupper($validated['campaign_code']);
        $pin = $validated['pin'];
        $staff = $request->user();

        // Buscar customer por short_code en el business del staff
        $customer = Customer::where('business_id', $staff->business_id)
            ->where('short_code', $customerCode)
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer no encontrado.',
            ], 404);
        }

        // Buscar campaign por código
        $campaign = Campaign::where('code', $campaignCode)
            ->where('business_id', $staff->business_id)
            ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign no encontrada.',
            ], 404);
        }

        // Buscar customer_campaign
        $customerCampaign = CustomerCampaign::where('customer_id', $customer->id)
            ->where('campaign_id', $campaign->id)
            ->first();

        if (!$customerCampaign) {
            return response()->json([
                'success' => false,
                'message' => 'El customer no está registrado en esta campaign.',
            ], 404);
        }

        // Buscar redemption con PIN válido
        $redemption = Redemption::whereHas('redemptionPin', function ($query) use ($pin) {
            $query->where('pin', $pin)
                ->where('expires_at', '>', now())
                ->whereNull('verified_at');
        })
        ->where('customer_campaign_id', $customerCampaign->id)
        ->whereNull('confirmed_at') // Aún no confirmado
        ->with(['rewardUnlock.reward', 'redemptionPin'])
        ->first();

        if (!$redemption) {
            return response()->json([
                'success' => false,
                'message' => 'PIN inválido o expirado.',
            ], 404);
        }

        // Verificar que el unlock pertenezca a este customer_campaign
        if ($redemption->reward_unlock_id && $redemption->rewardUnlock->customer_campaign_id !== $customerCampaign->id) {
            return response()->json([
                'success' => false,
                'message' => 'El PIN no corresponde a este customer y campaign.',
            ], 422);
        }

        // Marcar PIN como verificado
        $redemption->redemptionPin->update([
            'verified_at' => now(),
            'attempts' => $redemption->redemptionPin->attempts + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PIN verificado exitosamente.',
            'data' => [
                'customer' => [
                    'short_code' => $customer->short_code,
                    'name' => $customer->name,
                ],
                'reward' => [
                    'id' => $redemption->reward->id,
                    'name' => $redemption->reward->name,
                    'description' => $redemption->reward->description,
                    'image_url' => $redemption->reward->image_url,
                ],
                'unlock_id' => $redemption->reward_unlock_id,
                'redemption_id' => $redemption->id,
            ],
        ], 200);
    }

    /**
     * Canjear premio (Staff)
     * 
     * Después de verificar el PIN, el staff confirma el canje.
     * Esto cambia el estado del unlock a 'redeemed' y actualiza contadores.
     * 
     * @authenticated
     * @guard staff
     */
    public function redeem(RedeemRewardRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $customerCode = strtoupper($validated['customer_code']);
        $campaignCode = strtoupper($validated['campaign_code']);
        $pin = $validated['pin'];
        $unlockId = $validated['unlock_id'];
        $staff = $request->user();

        DB::beginTransaction();
        try {
            // Buscar customer por short_code en el business del staff
            $customer = Customer::where('business_id', $staff->business_id)
                ->where('short_code', $customerCode)
                ->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer no encontrado.',
                ], 404);
            }

            // Buscar campaign por código
            $campaign = Campaign::where('code', $campaignCode)
                ->where('business_id', $staff->business_id)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign no encontrada.',
                ], 404);
            }

            // Buscar customer_campaign
            $customerCampaign = CustomerCampaign::where('customer_id', $customer->id)
                ->where('campaign_id', $campaign->id)
                ->first();

            if (!$customerCampaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'El customer no está registrado en esta campaign.',
                ], 404);
            }

            // Buscar unlock
            $unlock = RewardUnlock::where('id', $unlockId)
                ->where('customer_campaign_id', $customerCampaign->id)
                ->where('status', 'unlocked')
                ->with(['reward', 'campaignReward'])
                ->firstOrFail();

            // Verificar que el unlock esté disponible
            if (!$unlock->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este premio no está disponible para canjear.',
                ], 422);
            }

            // Buscar redemption con PIN verificado
            $redemption = Redemption::whereHas('redemptionPin', function ($query) use ($pin) {
                $query->where('pin', $pin)
                    ->whereNotNull('verified_at');
            })
            ->where('reward_unlock_id', $unlock->id)
            ->where('customer_campaign_id', $customerCampaign->id)
            ->whereNull('confirmed_at')
            ->with('redemptionPin')
            ->first();

            if (!$redemption) {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN no verificado o inválido.',
                ], 422);
            }

            // Validar límites del reward
            $limitValidation = $this->validateRewardLimits($unlock, $customerCampaign);
            if (!$limitValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $limitValidation['message'],
                ], 422);
            }

            // Confirmar canje
            $redemption->update([
                'staff_id' => $staff->id,
                'confirmed_at' => now(),
            ]);

            // Actualizar unlock
            $unlock->update([
                'status' => 'redeemed',
                'redeemed_at' => now(),
                'redemption_id' => $redemption->id,
            ]);

            // Actualizar contadores
            if ($unlock->campaignReward) {
                $unlock->campaignReward->increment('redeemed_count');
            }
            $campaign->increment('redeemed_count');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Premio canjeado exitosamente.',
                'data' => [
                    'customer' => [
                        'short_code' => $customer->short_code,
                        'name' => $customer->name,
                    ],
                    'reward' => [
                        'id' => $unlock->reward->id,
                        'name' => $unlock->reward->name,
                        'description' => $unlock->reward->description,
                    ],
                    'redemption' => [
                        'id' => $redemption->id,
                        'confirmed_at' => $redemption->confirmed_at->toIso8601String(),
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al canjear premio: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar premios desbloqueados del customer (Customer)
     * 
     * @authenticated
     * @guard customer
     */
    public function myUnlocks(Request $request): JsonResponse
    {
        $customer = $request->user();

        $unlocks = RewardUnlock::whereHas('customerCampaign', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id);
        })
        ->with(['reward', 'customerCampaign.campaign'])
        ->orderBy('unlocked_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $unlocks->map(function ($unlock) {
                return [
                    'id' => $unlock->id,
                    'status' => $unlock->status,
                    'unlocked_at' => $unlock->unlocked_at->toIso8601String(),
                    'expires_at' => $unlock->expires_at?->toIso8601String(),
                    'redeemed_at' => $unlock->redeemed_at?->toIso8601String(),
                    'is_available' => $unlock->isAvailable(),
                    'is_expired' => $unlock->isExpired(),
                    'reward' => [
                        'id' => $unlock->reward->id,
                        'name' => $unlock->reward->name,
                        'description' => $unlock->reward->description,
                        'image_url' => $unlock->reward->image_url,
                    ],
                    'campaign' => [
                        'id' => $unlock->customerCampaign->campaign->id,
                        'code' => $unlock->customerCampaign->campaign->code,
                        'name' => $unlock->customerCampaign->campaign->name,
                    ],
                ];
            }),
        ], 200);
    }

    /**
     * Validar límites del reward antes de canjear
     */
    private function validateRewardLimits(RewardUnlock $unlock, CustomerCampaign $customerCampaign): array
    {
        $campaignReward = $unlock->campaignReward;

        if (!$campaignReward) {
            return ['valid' => true];
        }

        // Validar per_customer_limit
        if ($campaignReward->per_customer_limit !== null) {
            $redeemedCount = RewardUnlock::where('customer_campaign_id', $customerCampaign->id)
                ->where('reward_id', $unlock->reward_id)
                ->where('status', 'redeemed')
                ->count();

            if ($redeemedCount >= $campaignReward->per_customer_limit) {
                return [
                    'valid' => false,
                    'message' => "Se ha alcanzado el límite de {$campaignReward->per_customer_limit} canjes de este premio por customer.",
                ];
            }
        }

        // Validar global_limit
        if ($campaignReward->global_limit !== null) {
            if ($campaignReward->redeemed_count >= $campaignReward->global_limit) {
                return [
                    'valid' => false,
                    'message' => "Se ha alcanzado el límite global de {$campaignReward->global_limit} canjes de este premio.",
                ];
            }
        }

        return ['valid' => true];
    }
}
