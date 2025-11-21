<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ApplyStampRequest;
use App\Models\Campaign;
use App\Models\CampaignReward;
use App\Models\Customer;
use App\Models\CustomerCampaign;
use App\Models\RewardUnlock;
use App\Models\Stamp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group 🎫 Stamps
 * 
 * Endpoints para aplicar stamps (sellos/punches) y streaks (rachas) a customers
 * 
 * @authenticated
 * @header Authorization Bearer {staff_token} Requiere token de staff
 */
class StampController extends Controller
{
    /**
     * Aplicar stamp o streak a customer
     * 
     * Este endpoint se usa cuando el staff escanea un QR que contiene:
     * - customer_code (6 caracteres)
     * - campaign_code (4 caracteres)
     * 
     * El tipo puede ser "stamp" o "streak", cada uno con su lógica específica.
     * 
     * @authenticated
     * @guard staff
     */
    public function applyStamp(ApplyStampRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $customerCode = strtoupper($validated['customer_code']);
        $campaignCode = strtoupper($validated['campaign_code']);
        $type = $validated['type'];

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
                ->with('business')
                ->first();

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign no encontrada.',
                ], 404);
            }

            // Verificar que la campaign pertenezca al mismo business del staff
            if ($campaign->business_id !== $staff->business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'La campaign no pertenece a tu negocio.',
                ], 403);
            }

            // Verificar que el customer esté registrado en la campaign
            $customerCampaign = CustomerCampaign::where('customer_id', $customer->id)
                ->where('campaign_id', $campaign->id)
                ->first();

            if (!$customerCampaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'El customer no está registrado en esta campaign.',
                ], 404);
            }

            // Validar límites según la campaign
            $limitValidation = $this->validateLimits($customerCampaign, $campaign, $type);
            if (!$limitValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $limitValidation['message'],
                ], 422);
            }

            // Aplicar lógica según el tipo
            if ($type === 'stamp') {
                $result = $this->applyStampLogic($customerCampaign, $staff, $campaign);
            } else {
                $result = $this->applyStreakLogic($customerCampaign, $staff, $campaign);
            }

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $type === 'stamp' 
                    ? 'Stamp aplicado exitosamente.' 
                    : 'Streak aplicado exitosamente.',
                'data' => [
                    'customer' => [
                        'short_code' => $customer->short_code,
                        'name' => $customer->name,
                    ],
                    'campaign' => [
                        'code' => $campaign->code,
                        'name' => $campaign->name,
                    ],
                    'stamps' => $customerCampaign->fresh()->stamps,
                    'type' => $type,
                    'stamp_id' => $result['stamp_id'],
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al aplicar stamp: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lógica para aplicar stamp (punch)
     */
    private function applyStampLogic(CustomerCampaign $customerCampaign, $staff, Campaign $campaign): array
    {
        // Verificar que la campaign sea tipo "punch"
        if ($campaign->type !== 'punch') {
            return [
                'success' => false,
                'message' => 'Esta campaign no es de tipo "punch".',
            ];
        }

        // Incrementar stamps en customer_campaigns
        $customerCampaign->increment('stamps');

        // Crear registro en stamps
        $stamp = Stamp::create([
            'customer_campaign_id' => $customerCampaign->id,
            'staff_id' => $staff->id,
            'type' => 'stamp',
            'meta' => [
                'applied_at' => now()->toIso8601String(),
            ],
        ]);

        // Desbloquear rewards automáticamente si se alcanzaron los requisitos
        $this->unlockRewards($customerCampaign, $campaign, 'stamp');

        return [
            'success' => true,
            'stamp_id' => $stamp->id,
        ];
    }

    /**
     * Lógica para aplicar streak (racha)
     */
    private function applyStreakLogic(CustomerCampaign $customerCampaign, $staff, Campaign $campaign): array
    {
        // Verificar que la campaign sea tipo "streak"
        if ($campaign->type !== 'streak') {
            return [
                'success' => false,
                'message' => 'Esta campaign no es de tipo "streak".',
            ];
        }

        // TODO: Implementar lógica de streaks cuando se cree la tabla customer_streaks
        // Por ahora solo registramos el stamp
        // La lógica completa incluirá:
        // - Verificar streak_time_limit_hours
        // - Verificar streak_reset_time
        // - Actualizar customer_streaks (current_streak, longest_streak, last_event_date)

        // Crear registro en stamps
        $stamp = Stamp::create([
            'customer_campaign_id' => $customerCampaign->id,
            'staff_id' => $staff->id,
            'type' => 'streak',
            'meta' => [
                'applied_at' => now()->toIso8601String(),
            ],
        ]);

        // Por ahora incrementamos stamps también (se ajustará cuando se implemente customer_streaks)
        $customerCampaign->increment('stamps');

        // Desbloquear rewards automáticamente si se alcanzaron los requisitos
        $this->unlockRewards($customerCampaign, $campaign, 'streak');

        return [
            'success' => true,
            'stamp_id' => $stamp->id,
        ];
    }

    /**
     * Validar límites de la campaign
     */
    private function validateLimits(CustomerCampaign $customerCampaign, Campaign $campaign, string $type): array
    {
        // Validar per_customer_limit
        if ($campaign->per_customer_limit !== null) {
            $stampsCount = Stamp::where('customer_campaign_id', $customerCampaign->id)
                ->where('type', $type)
                ->count();

            if ($stampsCount >= $campaign->per_customer_limit) {
                return [
                    'valid' => false,
                    'message' => "Se ha alcanzado el límite de {$campaign->per_customer_limit} stamps por customer.",
                ];
            }
        }

        // Validar per_week_limit
        if ($campaign->per_week_limit !== null) {
            $weekStart = now()->startOfWeek();
            $stampsThisWeek = Stamp::where('customer_campaign_id', $customerCampaign->id)
                ->where('type', $type)
                ->where('created_at', '>=', $weekStart)
                ->count();

            if ($stampsThisWeek >= $campaign->per_week_limit) {
                return [
                    'valid' => false,
                    'message' => "Se ha alcanzado el límite semanal de {$campaign->per_week_limit} stamps.",
                ];
            }
        }

        // Validar per_month_limit
        if ($campaign->per_month_limit !== null) {
            $monthStart = now()->startOfMonth();
            $stampsThisMonth = Stamp::where('customer_campaign_id', $customerCampaign->id)
                ->where('type', $type)
                ->where('created_at', '>=', $monthStart)
                ->count();

            if ($stampsThisMonth >= $campaign->per_month_limit) {
                return [
                    'valid' => false,
                    'message' => "Se ha alcanzado el límite mensual de {$campaign->per_month_limit} stamps.",
                ];
            }
        }

        // Validar max_redemptions_per_day (para stamps del día)
        if ($campaign->max_redemptions_per_day !== null) {
            $todayStart = now()->startOfDay();
            $stampsToday = Stamp::where('customer_campaign_id', $customerCampaign->id)
                ->where('type', $type)
                ->where('created_at', '>=', $todayStart)
                ->count();

            if ($stampsToday >= $campaign->max_redemptions_per_day) {
                return [
                    'valid' => false,
                    'message' => "Se ha alcanzado el límite diario de {$campaign->max_redemptions_per_day} stamps.",
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Desbloquear rewards automáticamente cuando se alcanzan los requisitos
     */
    private function unlockRewards(CustomerCampaign $customerCampaign, Campaign $campaign, string $stampType): void
    {
        // Obtener todos los rewards activos de la campaign
        $campaignRewards = CampaignReward::where('campaign_id', $campaign->id)
            ->where('active', true)
            ->with('reward')
            ->get();

        $currentValue = $customerCampaign->stamps; // Para stamps, usar stamps count

        foreach ($campaignRewards as $campaignReward) {
            // Verificar que el tipo del reward coincida con el tipo de stamp
            if ($campaignReward->reward->type !== $stampType) {
                continue;
            }

            // Verificar si se alcanzó el threshold
            if ($currentValue < $campaignReward->threshold_int) {
                continue;
            }

            // Verificar si ya está desbloqueado
            $existingUnlock = RewardUnlock::where('customer_campaign_id', $customerCampaign->id)
                ->where('reward_id', $campaignReward->reward_id)
                ->first();

            if ($existingUnlock) {
                continue; // Ya está desbloqueado
            }

            // Calcular fecha de expiración si aplica
            $expiresAt = null;
            if ($campaignReward->expires_after_days !== null) {
                $expiresAt = now()->addDays($campaignReward->expires_after_days);
            }

            // Crear unlock
            RewardUnlock::create([
                'customer_campaign_id' => $customerCampaign->id,
                'reward_id' => $campaignReward->reward_id,
                'campaign_reward_id' => $campaignReward->id,
                'unlocked_at' => now(),
                'expires_at' => $expiresAt,
                'status' => 'unlocked',
            ]);
        }
    }

    /**
     * Historial de stamps de un customer en una campaign (Owner)
     * 
     * @authenticated
     */
    public function customerCampaignStamps(Request $request, string $customerId, string $campaignId): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view stamps.',
            ], 403);
        }

        $customerCampaign = CustomerCampaign::where('customer_id', $customerId)
            ->where('campaign_id', $campaignId)
            ->whereHas('customer', function ($query) use ($user) {
                $query->where('business_id', $user->business->id);
            })
            ->with(['customer', 'campaign', 'stamps.staff'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customerCampaign->customer->id,
                    'short_code' => $customerCampaign->customer->short_code,
                    'name' => $customerCampaign->customer->name,
                ],
                'campaign' => [
                    'id' => $customerCampaign->campaign->id,
                    'code' => $customerCampaign->campaign->code,
                    'name' => $customerCampaign->campaign->name,
                ],
                'total_stamps' => $customerCampaign->stamps,
                'stamps' => $customerCampaign->stamps()->with('staff')->get()->map(function ($stamp) {
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
     * Todos los stamps de una campaign (Owner)
     * 
     * @authenticated
     */
    public function campaignStamps(Request $request, string $campaignId): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view stamps.',
            ], 403);
        }

        $campaign = Campaign::where('id', $campaignId)
            ->where('business_id', $user->business->id)
            ->firstOrFail();

        $stamps = Stamp::whereHas('customerCampaign', function ($query) use ($campaignId) {
            $query->where('campaign_id', $campaignId);
        })
        ->with(['customerCampaign.customer', 'staff'])
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $stamps->map(function ($stamp) {
                return [
                    'id' => $stamp->id,
                    'type' => $stamp->type,
                    'customer' => [
                        'id' => $stamp->customerCampaign->customer->id,
                        'short_code' => $stamp->customerCampaign->customer->short_code,
                        'name' => $stamp->customerCampaign->customer->name,
                    ],
                    'staff' => [
                        'id' => $stamp->staff->id,
                        'code' => $stamp->staff->code,
                        'name' => $stamp->staff->name,
                    ],
                    'meta' => $stamp->meta,
                    'created_at' => $stamp->created_at->toIso8601String(),
                ];
            }),
        ], 200);
    }
}
